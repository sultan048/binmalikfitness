<?php

/*
 * Stand-in for the PHP fileinfo extension on hosts that do not ship it (Symfony's mime guesser, Laravel's
 * `mimes:` validation and Flysystem all call finfo). Loaded by Composer's autoload "files" and inert when
 * the real extension is present. Detection lives in App\Support\Compat\MimeSniffer.
 *
 * Everything sits inside one runtime `if`: a top-level class declaration is hoisted at compile time and
 * would clash with the real finfo class.
 */

use App\Support\Compat\MimeSniffer;

if (! extension_loaded('fileinfo') && ! class_exists('finfo', false)) {
    foreach ([
        'FILEINFO_NONE' => 0,
        'FILEINFO_SYMLINK' => 2,
        'FILEINFO_DEVICES' => 8,
        'FILEINFO_MIME_TYPE' => 16,
        'FILEINFO_CONTINUE' => 32,
        'FILEINFO_PRESERVE_ATIME' => 128,
        'FILEINFO_RAW' => 256,
        'FILEINFO_MIME_ENCODING' => 1024,
        'FILEINFO_MIME' => 1040,
        'FILEINFO_EXTENSION' => 2097152,
    ] as $name => $value) {
        defined($name) || define($name, $value);
    }

    class finfo
    {
        private int $flags;

        public function __construct(int $flags = FILEINFO_NONE, ?string $magic_database = null)
        {
            $this->flags = $flags;
        }

        public function set_flags(int $flags): bool
        {
            $this->flags = $flags;

            return true;
        }

        public function file(string $filename, int $flags = FILEINFO_NONE, $context = null): string|false
        {
            $flags = $flags ?: $this->flags;

            if (is_dir($filename)) {
                return ($flags & FILEINFO_MIME_TYPE) ? 'inode/directory' : 'directory';
            }

            $handle = @fopen($filename, 'rb');

            if ($handle === false) {
                return false;
            }

            $bytes = (string) fread($handle, MimeSniffer::SAMPLE_BYTES);
            fclose($handle);

            return MimeSniffer::describe($bytes, $flags);
        }

        public function buffer(string $string, int $flags = FILEINFO_NONE, $context = null): string|false
        {
            return MimeSniffer::describe(substr($string, 0, MimeSniffer::SAMPLE_BYTES), $flags ?: $this->flags);
        }
    }

    function finfo_open(int $flags = FILEINFO_NONE, ?string $magic_database = null): finfo|false
    {
        return new finfo($flags, $magic_database);
    }

    function finfo_close(finfo $finfo): bool
    {
        return true;
    }

    function finfo_set_flags(finfo $finfo, int $flags): bool
    {
        return $finfo->set_flags($flags);
    }

    function finfo_file(finfo $finfo, string $filename, int $flags = FILEINFO_NONE, $context = null): string|false
    {
        return $finfo->file($filename, $flags, $context);
    }

    function finfo_buffer(finfo $finfo, string $string, int $flags = FILEINFO_NONE, $context = null): string|false
    {
        return $finfo->buffer($string, $flags, $context);
    }
}
