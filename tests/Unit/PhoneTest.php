<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Phone;
use PHPUnit\Framework\TestCase;

class PhoneTest extends TestCase
{
    public function test_normalize_accepts_common_formats(): void
    {
        $this->assertSame('09123456789', Phone::normalize('09123456789'));
        $this->assertSame('09123456789', Phone::normalize('9123456789'));
        $this->assertSame('09123456789', Phone::normalize('989123456789'));
        $this->assertSame('09123456789', Phone::normalize('+989123456789'));
        $this->assertSame('09123456789', Phone::normalize('00989123456789'));
        $this->assertSame('09123456789', Phone::normalize('+98 912 345 6789'));
    }

    public function test_normalize_accepts_persian_and_arabic_digits(): void
    {
        $this->assertSame('09123456789', Phone::normalize('۰۹۱۲۳۴۵۶۷۸۹'));
        $this->assertSame('09123456789', Phone::normalize('٠٩١٢٣٤٥٦٧٨٩'));
    }

    public function test_normalize_rejects_invalid_numbers(): void
    {
        $this->assertNull(Phone::normalize('0912345678'));   // too short
        $this->assertNull(Phone::normalize('091234567890')); // too long
        $this->assertNull(Phone::normalize('08123456789'));  // not mobile
        $this->assertNull(Phone::normalize('abc'));
        $this->assertNull(Phone::normalize(''));
    }

    public function test_mask(): void
    {
        $this->assertSame('۰۹۱۲ *** ۶۷۸۹', Phone::mask('09123456789'));
    }
}
