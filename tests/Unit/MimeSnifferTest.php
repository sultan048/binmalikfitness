<?php

namespace Tests\Unit;

use App\Support\Compat\MimeSniffer;
use PHPUnit\Framework\TestCase;

class MimeSnifferTest extends TestCase
{
    public function test_recognises_the_formats_the_app_accepts_by_magic_bytes(): void
    {
        $this->assertSame('image/jpeg', MimeSniffer::type("\xFF\xD8\xFF\xE0\x00\x10JFIF"));
        $this->assertSame('image/png', MimeSniffer::type("\x89PNG\r\n\x1A\n\x00\x00\x00\rIHDR"));
        $this->assertSame('image/webp', MimeSniffer::type("RIFF\x24\x00\x00\x00WEBPVP8 "));
        $this->assertSame('application/pdf', MimeSniffer::type("%PDF-1.7\n%\xE2\xE3"));
    }

    public function test_disguised_or_unknown_content_never_matches_an_allowed_type(): void
    {
        $this->assertSame('text/plain', MimeSniffer::type('<?php system($_GET["c"]);'));
        $this->assertSame('text/plain', MimeSniffer::type('GARBAGE'.str_repeat('a', 50)));
        $this->assertSame('application/octet-stream', MimeSniffer::type("\x00\x01\x02\x03binary"));
        $this->assertSame('application/x-empty', MimeSniffer::type(''));
        // A RIFF container that is not WebP, and a PDF header that is not at the start of the file
        $this->assertSame('application/octet-stream', MimeSniffer::type("RIFF\x24\x00\x00\x00WAVEfmt \x00"));
        $this->assertSame('text/plain', MimeSniffer::type("junk\n%PDF-1.7"));
    }

    public function test_describe_follows_finfo_flag_semantics(): void
    {
        $png = "\x89PNG\r\n\x1A\n\x00\x00\x00\rIHDR";

        $this->assertSame('image/png', MimeSniffer::describe($png, 16));
        $this->assertSame('binary', MimeSniffer::describe($png, 1024));
        $this->assertSame('image/png; charset=binary', MimeSniffer::describe($png, 1040));
        $this->assertSame('text/plain; charset=us-ascii', MimeSniffer::describe('hello', 1040));
        $this->assertSame('text/plain; charset=utf-8', MimeSniffer::describe("caf\xC3\xA9", 1040));
    }
}
