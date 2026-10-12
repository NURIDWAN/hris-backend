<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeSalary;
use Illuminate\Database\Seeder;

class EmployeeSalarySeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();

        $paymentMethods = ['transfer', 'cash'];
        $banks = ['BCA', 'Mandiri', 'BNI', 'BRI', 'CIMB Niaga'];

        foreach ($companies as $company) {
            $employees = Employee::where('company_id', $company->id)
                ->where('is_active', true)
                ->get();

            foreach ($employees as $employee) {
                // Skip if already has active salary
                if ($employee->currentSalary) {
                    continue;
                }

                // Generate random salary based on position level
                $baseSalary = $this->getBaseSalary($employee);

                EmployeeSalary::create([
                    'company_id' => $company->id,
                    'employee_id' => $employee->id,
                    'basic_salary' => $baseSalary,
                    'effective_date' => $employee->join_date ?? now()->startOfYear(),
                    'payment_method' => $paymentMethods[array_rand($paymentMethods)],
                    'bank_name' => $banks[array_rand($banks)],
                    'bank_account_number' => str_pad((string) rand(1000000000, 9999999999), 10, '0', STR_PAD_LEFT),
                    'bank_account_name' => $employee->full_name,
                    'is_active' => true,
                ]);
            }
        }
    }

    private function getBaseSalary(Employee $employee): int
    {
        // Base salary ranges based on position
        $positionName = strtolower($employee->position?->name ?? '');

        if (str_contains($positionName, 'director') || str_contains($positionName, 'ceo') || str_contains($positionName, 'cto')) {
            return rand(25000000, 50000000);
        }

        if (str_contains($positionName, 'manager') || str_contains($positionName, 'head')) {
            return rand(15000000, 25000000);
        }

        if (str_contains($positionName, 'senior') || str_contains($positionName, 'lead')) {
            return rand(10000000, 18000000);
        }

        if (str_contains($positionName, 'supervisor') || str_contains($positionName, 'spv')) {
            return rand(8000000, 12000000);
        }

        // Default for staff level
        return rand(4500000, 8000000);
    }
}
