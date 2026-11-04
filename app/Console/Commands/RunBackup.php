<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Symfony\Component\Process\Process;
use ZipArchive;

/**
 * Dumps the MySQL database (gzip) and zips the private uploads into storage/app/backups,
 * then deletes backups older than BACKUP_KEEP_DAYS. Uses the server's mysqldump binary,
 * so it works on cPanel and XAMPP without extra packages.
 */
class RunBackup extends Command
{
    protected $signature = 'backup:run {--no-files : Back up the database only}';

    protected $description = 'Back up the database and private uploads to storage/app/backups';

    public function handle(): int
    {
        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir, 0750);

        $stamp = now()->format('Y-m-d_His');

        try {
            $dbFile = $this->dumpDatabase($dir, $stamp);
            $this->info('Database: '.basename($dbFile).' ('.$this->size($dbFile).')');

            if (! $this->option('no-files')) {
                $zipFile = $this->zipPrivateFiles($dir, $stamp);
                if ($zipFile) {
                    $this->info('Files:    '.basename($zipFile).' ('.$this->size($zipFile).')');
                }
            }
        } catch (RuntimeException $e) {
            $this->error('Backup failed: '.$e->getMessage());
            report($e);

            return self::FAILURE;
        }

        $removed = $this->removeOldBackups($dir, (int) config('app.backup_keep_days'));
        $this->line("Removed {$removed} backup file(s) older than ".config('app.backup_keep_days').' day(s).');

        return self::SUCCESS;
    }

    private function dumpDatabase(string $dir, string $stamp): string
    {
        $connection = config('database.connections.'.config('database.default'));

        if (($connection['driver'] ?? null) !== 'mysql' && ($connection['driver'] ?? null) !== 'mariadb') {
            throw new RuntimeException('Only MySQL/MariaDB databases are supported.');
        }

        $sqlFile = "{$dir}/db_{$stamp}.sql";

        $process = new Process([
            config('app.mysqldump_path'),
            '--host='.$connection['host'],
            '--port='.$connection['port'],
            '--user='.$connection['username'],
            '--single-transaction',
            '--quick',
            '--routines',
            '--default-character-set=utf8mb4',
            '--result-file='.$sqlFile,
            $connection['database'],
        ], null, [
            // Password via environment so it never appears in the process list
            'MYSQL_PWD' => (string) $connection['password'],
        ]);
        $process->setTimeout(1800);
        $process->run();

        if (! $process->isSuccessful()) {
            File::delete($sqlFile);

            throw new RuntimeException(trim($process->getErrorOutput()) ?: 'mysqldump exited with code '.$process->getExitCode());
        }

        $gzFile = $sqlFile.'.gz';
        $in = fopen($sqlFile, 'rb');
        $out = gzopen($gzFile, 'wb6');
        while (! feof($in)) {
            gzwrite($out, fread($in, 1024 * 512));
        }
        fclose($in);
        gzclose($out);
        File::delete($sqlFile);

        return $gzFile;
    }

    private function zipPrivateFiles(string $dir, string $stamp): ?string
    {
        $source = storage_path('app/private');

        if (! is_dir($source)) {
            return null;
        }

        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('The PHP zip extension is required to back up private files.');
        }

        $zipFile = "{$dir}/files_{$stamp}.zip";
        $zip = new ZipArchive;

        if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Cannot create {$zipFile}.");
        }

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($files as $file) {
            if ($file->isFile()) {
                $zip->addFile($file->getPathname(), ltrim(str_replace('\\', '/', substr($file->getPathname(), strlen($source))), '/'));
            }
        }

        $zip->close();

        return is_file($zipFile) ? $zipFile : null;
    }

    private function removeOldBackups(string $dir, int $keepDays): int
    {
        if ($keepDays <= 0) {
            return 0;
        }

        $cutoff = now()->subDays($keepDays)->getTimestamp();
        $removed = 0;

        foreach (File::files($dir) as $file) {
            if (preg_match('/^(db|files)_\d{4}-\d{2}-\d{2}_\d{6}\./', $file->getFilename()) && $file->getMTime() < $cutoff) {
                File::delete($file->getPathname());
                $removed++;
            }
        }

        return $removed;
    }

    private function size(string $path): string
    {
        return number_format(filesize($path) / 1024, 1).' KB';
    }
}
