<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\JalaliDate;
use App\Support\Persian;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class JalaliDateTest extends TestCase
{
    public function test_converts_known_gregorian_dates_to_jalali(): void
    {
        // Nowruz 1403
        $this->assertSame('۱۴۰۳/۰۱/۰۱', JalaliDate::date('2024-03-20 12:00:00'));
        // 1357/11/22 (Bahman 22, 1357)
        $this->assertSame('۱۳۵۷/۱۱/۲۲', JalaliDate::date('1979-02-11 12:00:00'));
    }

    public function test_date_time_uses_tehran_timezone(): void
    {
        // 2024-03-20 12:00 UTC is 15:30 in Tehran (UTC+3:30) — still 1403/01/01.
        $utc = Carbon::parse('2024-03-20 12:00:00', 'UTC');
        $this->assertSame('۱۴۰۳/۰۱/۰۱', JalaliDate::date($utc));

        // 2024-03-20 21:00 UTC is already 00:30 on 1403/01/02 in Tehran.
        $utcLate = Carbon::parse('2024-03-20 21:00:00', 'UTC');
        $this->assertSame('۱۴۰۳/۰۱/۰۲', JalaliDate::date($utcLate));
    }

    public function test_long_format(): void
    {
        $this->assertSame('۱ فروردین ۱۴۰۳', JalaliDate::long('2024-03-20 12:00:00'));
        $this->assertSame('۲۲ بهمن ۱۳۵۷', JalaliDate::long('1979-02-11 12:00:00'));
    }

    public function test_date_time_format(): void
    {
        $this->assertSame('۱۴۰۳/۰۱/۰۱ ۱۲:۰۰', JalaliDate::dateTime('2024-03-20 12:00:00'));
    }

    public function test_digits_are_persian(): void
    {
        $result = JalaliDate::date('2024-03-20 12:00:00');
        $this->assertMatchesRegularExpression('/^[۰-۹\/]+$/', $result);
    }
}
