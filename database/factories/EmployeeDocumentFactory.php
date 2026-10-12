<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\User;
use Database\Factories\Concerns\GeneratesRandomData;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EmployeeDocument>
 */
class EmployeeDocumentFactory extends Factory
{
    use GeneratesRandomData;

    protected $model = EmployeeDocument::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $extensions = ['pdf', 'jpg', 'png'];
        $extension = $this->randomElement($extensions);

        return [
            'company_id' => Company::factory(),
            'employee_id' => Employee::factory(),
            'document_type' => $this->randomElement(array_keys(EmployeeDocument::DOCUMENT_TYPES)),
            'document_number' => $this->numerify('################'),
            'document_name' => null,
            'file_path' => 'documents/'.\Illuminate\Support\Str::uuid()->toString().'.'.$extension,
            'file_name' => $this->randomElement(['document', 'file', 'scan']).'.'.$extension,
            'file_size' => rand(100000, 5000000),
            'mime_type' => $extension === 'pdf' ? 'application/pdf' : 'image/'.$extension,
            'issue_date' => $this->randomDateBetween('-5 years', '-1 year'),
            'expiry_date' => $this->randomBoolean(50) ? $this->randomDateBetween('+1 month', '+5 years') : null,
            'is_verified' => $this->randomBoolean(30),
            'verified_by' => null,
            'verified_at' => null,
            'uploaded_by' => null,
            'notes' => $this->randomBoolean(30) ? $this->randomSentence() : null,
        ];
    }

    /**
     * Document is verified.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => true,
            'verified_by' => User::factory(),
            'verified_at' => now(),
        ]);
    }

    /**
     * Document is not verified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => false,
            'verified_by' => null,
            'verified_at' => null,
        ]);
    }

    /**
     * KTP document type.
     */
    public function ktp(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => EmployeeDocument::TYPE_KTP,
            'document_number' => '320'.$this->numerify('#############'),
            'expiry_date' => null, // KTP seumur hidup
        ]);
    }

    /**
     * NPWP document type.
     */
    public function npwp(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => EmployeeDocument::TYPE_NPWP,
            'document_number' => $this->numerify('##.###.###.#-###.###'),
            'expiry_date' => null,
        ]);
    }

    /**
     * KK document type.
     */
    public function kk(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => EmployeeDocument::TYPE_KK,
            'document_number' => '320'.$this->numerify('#############'),
            'expiry_date' => null,
        ]);
    }

    /**
     * BPJS Kesehatan document type.
     */
    public function bpjsKesehatan(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => EmployeeDocument::TYPE_BPJS_KESEHATAN,
            'document_number' => $this->numerify('#############'),
            'expiry_date' => null,
        ]);
    }

    /**
     * BPJS Ketenagakerjaan document type.
     */
    public function bpjsKetenagakerjaan(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => EmployeeDocument::TYPE_BPJS_KETENAGAKERJAAN,
            'document_number' => $this->numerify('##############'),
            'expiry_date' => null,
        ]);
    }

    /**
     * Ijazah document type.
     */
    public function ijazah(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => EmployeeDocument::TYPE_IJAZAH,
            'document_number' => null,
            'document_name' => $this->randomElement(['S1 Teknik Informatika', 'S1 Manajemen', 'D3 Akuntansi', 'SMA/SMK']),
            'expiry_date' => null,
        ]);
    }

    /**
     * Sertifikat document type.
     */
    public function sertifikat(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => EmployeeDocument::TYPE_SERTIFIKAT,
            'document_number' => $this->randomBoolean() ? 'CERT-'.$this->numerify('####') : null,
            'document_name' => $this->randomElement(['AWS Certified', 'Google Analytics', 'PMP', 'CCNA']),
            'expiry_date' => $this->randomDateBetween('+1 month', '+3 years'),
        ]);
    }

    /**
     * Kontrak Kerja document type.
     */
    public function kontrakKerja(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => EmployeeDocument::TYPE_KONTRAK_KERJA,
            'document_number' => 'PKK/'.$this->numerify('####').'/'.date('Y'),
            'document_name' => 'Perjanjian Kerja',
            'issue_date' => $this->randomDateBetween('-1 year', 'now'),
            'expiry_date' => $this->randomDateBetween('+6 months', '+2 years'),
        ]);
    }

    /**
     * Expired document.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expiry_date' => $this->randomDateBetween('-1 year', '-1 day'),
        ]);
    }

    /**
     * Expiring soon (within 30 days).
     */
    public function expiringSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'expiry_date' => $this->randomDateBetween('+1 day', '+30 days'),
        ]);
    }

    /**
     * Image file type.
     */
    public function image(): static
    {
        $extension = $this->randomElement(['jpg', 'jpeg', 'png']);

        return $this->state(fn (array $attributes) => [
            'file_path' => 'documents/'.\Illuminate\Support\Str::uuid()->toString().'.'.$extension,
            'file_name' => $this->randomElement(['document', 'file', 'scan']).'.'.$extension,
            'mime_type' => 'image/'.$extension,
        ]);
    }

    /**
     * PDF file type.
     */
    public function pdf(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_path' => 'documents/'.\Illuminate\Support\Str::uuid()->toString().'.pdf',
            'file_name' => $this->randomElement(['document', 'file', 'scan']).'.pdf',
            'mime_type' => 'application/pdf',
        ]);
    }
}
