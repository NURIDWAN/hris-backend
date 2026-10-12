<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class EmployeeOfficeLocation extends Pivot
{
    protected $table = 'employee_office_locations';

    protected $casts = [
        'is_primary' => 'boolean',
    ];
}
