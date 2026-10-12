<?php

namespace Database\Factories\Concerns;

use Carbon\Carbon;

/**
 * Trait untuk generate random data tanpa Faker
 * Digunakan di semua factories untuk kompatibilitas production
 */
trait GeneratesRandomData
{
    private static int $uniqueCounter = 0;

    private static array $firstNames = [
        'Andi', 'Budi', 'Citra', 'Dewi', 'Eko', 'Fitri', 'Gunawan', 'Hendra',
        'Indah', 'Joko', 'Kartika', 'Lukman', 'Maya', 'Nurul', 'Oscar', 'Putri',
        'Randi', 'Sari', 'Tono', 'Umi', 'Vina', 'Wawan', 'Yuni', 'Zainal',
        'Agus', 'Bambang', 'Clara', 'Dian', 'Erik', 'Fajar', 'Galih', 'Hesti',
        'Irwan', 'Joni', 'Kiki', 'Lina', 'Mira', 'Nanda', 'Oni', 'Pandu',
        'Rina', 'Sinta', 'Tuti', 'Udin', 'Vera', 'Wati', 'Yanto', 'Zaki',
    ];

    private static array $lastNames = [
        'Pratama', 'Wijaya', 'Kusuma', 'Santoso', 'Hidayat', 'Saputra', 'Putra',
        'Wibowo', 'Susanto', 'Nugroho', 'Suharto', 'Hartono', 'Suryadi', 'Setiawan',
        'Rahman', 'Permana', 'Atmaja', 'Firmansyah', 'Ramadhan', 'Hutapea',
        'Siregar', 'Nasution', 'Harahap', 'Lubis', 'Panjaitan', 'Simanjuntak',
    ];

    private static array $companyPrefixes = [
        'Abadi', 'Sejahtera', 'Makmur', 'Jaya', 'Sentosa', 'Mandiri', 'Maju',
        'Prima', 'Utama', 'Global', 'Nusantara', 'Indo', 'Mega', 'Multi',
    ];

    private static array $companySuffixes = [
        'Teknologi', 'Solusi', 'Digital', 'Karya', 'Cipta', 'Kreasi', 'Media',
        'Konsultan', 'Properti', 'Konstruksi', 'Trading', 'Logistik', 'Finance',
    ];

    private static array $streets = [
        'Jl. Sudirman', 'Jl. Thamrin', 'Jl. Gatot Subroto', 'Jl. HR Rasuna Said',
        'Jl. Kuningan', 'Jl. Menteng', 'Jl. Kemang', 'Jl. Senopati', 'Jl. Blok M',
        'Jl. Fatmawati', 'Jl. Cilandak', 'Jl. Pondok Indah', 'Jl. Kelapa Gading',
        'Jl. Sunter', 'Jl. Pluit', 'Jl. Pantai Indah Kapuk', 'Jl. Cempaka Putih',
    ];

    private static array $cities = [
        'Jakarta Selatan', 'Jakarta Pusat', 'Jakarta Barat', 'Jakarta Timur',
        'Jakarta Utara', 'Tangerang', 'Bekasi', 'Depok', 'Bogor', 'Bandung',
        'Surabaya', 'Semarang', 'Yogyakarta', 'Medan', 'Makassar', 'Palembang',
    ];

    protected function randomFirstName(): string
    {
        return self::$firstNames[array_rand(self::$firstNames)];
    }

    protected function randomLastName(): string
    {
        return self::$lastNames[array_rand(self::$lastNames)];
    }

    protected function randomFullName(): string
    {
        return $this->randomFirstName().' '.$this->randomLastName();
    }

    protected function randomCompanyName(): string
    {
        $prefix = self::$companyPrefixes[array_rand(self::$companyPrefixes)];
        $suffix = self::$companySuffixes[array_rand(self::$companySuffixes)];

        return $prefix.' '.$suffix;
    }

    protected function randomPhone(): string
    {
        $prefixes = ['0812', '0813', '0821', '0822', '0852', '0853', '0857', '0858', '0878', '0877'];
        $prefix = $prefixes[array_rand($prefixes)];
        $number = str_pad((string) rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);

        return $prefix.$number;
    }

    protected function randomAddress(): string
    {
        $street = self::$streets[array_rand(self::$streets)];
        $number = rand(1, 200);
        $city = self::$cities[array_rand(self::$cities)];
        $postalCode = rand(10000, 99999);

        return "{$street} No. {$number}, {$city} {$postalCode}";
    }

    protected function randomStreet(): string
    {
        $street = self::$streets[array_rand(self::$streets)];
        $number = rand(1, 200);

        return "{$street} No. {$number}";
    }

    protected function randomCity(): string
    {
        return self::$cities[array_rand(self::$cities)];
    }

    protected function uniqueEmail(string $prefix = 'user'): string
    {
        self::$uniqueCounter++;
        $domains = ['example.com', 'test.com', 'mail.test'];

        return strtolower($prefix).self::$uniqueCounter.'@'.$domains[array_rand($domains)];
    }

    protected function randomNpwp(): string
    {
        $part1 = str_pad((string) rand(0, 99), 2, '0', STR_PAD_LEFT);
        $part2 = str_pad((string) rand(0, 999), 3, '0', STR_PAD_LEFT);
        $part3 = str_pad((string) rand(0, 999), 3, '0', STR_PAD_LEFT);
        $part4 = (string) rand(0, 9);
        $part5 = str_pad((string) rand(0, 999), 3, '0', STR_PAD_LEFT);
        $part6 = str_pad((string) rand(0, 999), 3, '0', STR_PAD_LEFT);

        return "{$part1}.{$part2}.{$part3}.{$part4}-{$part5}.{$part6}";
    }

    protected function randomNik(): string
    {
        $provinsi = '31';
        $kota = str_pad((string) rand(1, 75), 2, '0', STR_PAD_LEFT);
        $kecamatan = str_pad((string) rand(1, 10), 2, '0', STR_PAD_LEFT);
        $day = str_pad((string) rand(1, 28), 2, '0', STR_PAD_LEFT);
        $month = str_pad((string) rand(1, 12), 2, '0', STR_PAD_LEFT);
        $year = str_pad((string) rand(80, 99), 2, '0', STR_PAD_LEFT);
        $urut = str_pad((string) rand(1, 9999), 4, '0', STR_PAD_LEFT);

        return "{$provinsi}{$kota}{$kecamatan}{$day}{$month}{$year}{$urut}";
    }

    protected function randomDomainName(): string
    {
        $names = ['company', 'business', 'corp', 'enterprise', 'group', 'tech', 'digital'];
        $extensions = ['.com', '.co.id', '.id', '.net'];

        return $names[array_rand($names)].rand(1, 999).$extensions[array_rand($extensions)];
    }

    protected function randomElement(array $array): mixed
    {
        return $array[array_rand($array)];
    }

    protected function randomNumber(int $digits = 5): int
    {
        $min = (int) str_pad('1', $digits, '0');
        $max = (int) str_pad('9', $digits, '9');

        return rand($min, $max);
    }

    protected function randomFloat(float $min = 0, float $max = 100, int $decimals = 2): float
    {
        return round($min + mt_rand() / mt_getrandmax() * ($max - $min), $decimals);
    }

    protected function randomDate(string $startDate = '-1 year', string $endDate = 'now'): Carbon
    {
        $start = Carbon::parse($startDate)->timestamp;
        $end = Carbon::parse($endDate)->timestamp;

        return Carbon::createFromTimestamp(rand($start, $end));
    }

    protected function randomDateBetween(string $startDate, string $endDate): Carbon
    {
        return $this->randomDate($startDate, $endDate);
    }

    protected function randomBoolean(int $chanceOfTrue = 50): bool
    {
        return rand(1, 100) <= $chanceOfTrue;
    }

    protected function randomSentence(int $words = 6): string
    {
        $wordList = [
            'data', 'sistem', 'aplikasi', 'proses', 'layanan', 'produk', 'kualitas',
            'manajemen', 'bisnis', 'teknologi', 'solusi', 'inovasi', 'strategi',
            'pengembangan', 'implementasi', 'optimasi', 'integrasi', 'analisis',
        ];

        $sentence = [];
        for ($i = 0; $i < $words; $i++) {
            $sentence[] = $wordList[array_rand($wordList)];
        }

        return ucfirst(implode(' ', $sentence)).'.';
    }

    protected function randomParagraph(int $sentences = 3): string
    {
        $paragraph = [];
        for ($i = 0; $i < $sentences; $i++) {
            $paragraph[] = $this->randomSentence(rand(5, 10));
        }

        return implode(' ', $paragraph);
    }

    protected function randomBankAccount(): string
    {
        return str_pad((string) rand(1000000000, 9999999999), 10, '0', STR_PAD_LEFT);
    }

    protected function randomBankName(): string
    {
        $banks = ['BCA', 'BNI', 'BRI', 'Mandiri', 'CIMB Niaga', 'Danamon', 'Permata', 'BTN'];

        return $banks[array_rand($banks)];
    }

    protected function numerify(string $pattern): string
    {
        return preg_replace_callback('/#/', fn () => (string) rand(0, 9), $pattern);
    }
}
