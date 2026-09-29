<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Persian;
use PHPUnit\Framework\TestCase;

class PersianTest extends TestCase
{
    public function test_digits_converts_english_to_persian(): void
    {
        $this->assertSame('۰۱۲۳۴۵۶۷۸۹', Persian::digits('0123456789'));
        $this->assertSame('۱۴۰۴', Persian::digits(1404));
    }

    public function test_to_english_converts_persian_and_arabic_digits(): void
    {
        $this->assertSame('09123456789', Persian::toEnglish('۰۹۱۲۳۴۵۶۷۸۹'));
        $this->assertSame('09123456789', Persian::toEnglish('٠٩١٢٣٤٥٦٧٨٩'));
        $this->assertSame('123', Persian::toEnglish('123'));
    }

    public function test_number_formats_with_thousands_separator(): void
    {
        $this->assertSame('۲۰۰٬۰۰۰', Persian::number(200000));
        $this->assertSame('۱٬۰۰۰', Persian::number(1000));
        $this->assertSame('۰', Persian::number(0));
        $this->assertSame('۹۹۹', Persian::number(999));
        $this->assertSame('۱٬۲۳۴٬۵۶۷٬۸۹۰', Persian::number(1234567890));
    }

    public function test_number_accepts_persian_digit_input(): void
    {
        $this->assertSame('۲۰۰٬۰۰۰', Persian::number('۲۰۰۰۰۰'));
    }

    public function test_price_adds_toman_unit(): void
    {
        $this->assertSame('۲۰۰٬۰۰۰ تومان', Persian::price(200000));
        $this->assertSame('۲۰۰٬۰۰۰', Persian::price(200000, false));
    }

    public function test_words_basic_numbers(): void
    {
        $this->assertSame('صفر', Persian::words(0));
        $this->assertSame('یک', Persian::words(1));
        $this->assertSame('نوزده', Persian::words(19));
        $this->assertSame('بیست', Persian::words(20));
        $this->assertSame('بیست و یک', Persian::words(21));
        $this->assertSame('صد', Persian::words(100));
        $this->assertSame('صد و یک', Persian::words(101));
        $this->assertSame('نهصد و نود و نه', Persian::words(999));
    }

    public function test_words_thousands_and_millions(): void
    {
        $this->assertSame('یک هزار', Persian::words(1000));
        $this->assertSame('دویست هزار', Persian::words(200000));
        $this->assertSame('دویست و پنج هزار و پانصد', Persian::words(205500));
        $this->assertSame('یک میلیون', Persian::words(1000000));
        $this->assertSame('یک میلیون و دویست و سی و چهار هزار و پانصد و شصت و هفت', Persian::words(1234567));
    }

    public function test_words_billions(): void
    {
        $this->assertSame('یک میلیارد', Persian::words(1000000000));
        $this->assertSame('دو میلیارد', Persian::words(2000000000));
        $this->assertSame('یک میلیارد و یک', Persian::words(1000000001));
        $this->assertSame(
            'دوازده میلیارد و سیصد و چهل و پنج میلیون و ششصد و هفتاد و هشت هزار و نهصد',
            Persian::words(12345678900)
        );
    }

    public function test_words_supports_negative_and_persian_input(): void
    {
        $this->assertSame('پنج', Persian::words(-5));
        $this->assertSame('دویست هزار', Persian::words('۲۰۰۰۰۰'));
    }

    public function test_price_words(): void
    {
        $this->assertSame('دویست هزار تومان', Persian::priceWords(200000));
    }
}
