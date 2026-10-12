<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Create roles for a specific company with proper team context.
 *
 * @param  int|null  $companyId  The company ID to scope roles to
 * @param  array  $roleNames  Array of role names to create
 */
function createCompanyRoles(?int $companyId, array $roleNames = ['admin', 'hr-manager', 'employee']): void
{
    setPermissionsTeamId($companyId);

    foreach ($roleNames as $roleName) {
        \Spatie\Permission\Models\Role::firstOrCreate([
            'name' => $roleName,
            'guard_name' => 'web',
        ]);
    }
}

/**
 * Create standard company roles (admin, hr-manager, employee) for a company.
 */
function createStandardRoles(int $companyId): void
{
    createCompanyRoles($companyId, ['admin', 'hr-manager', 'employee']);
}
