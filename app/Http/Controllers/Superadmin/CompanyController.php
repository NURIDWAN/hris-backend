<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(Request $request): View
    {
        $query = Company::withCount(['employees', 'users'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('email', 'like', '%'.$request->search.'%');
            });
        }

        $companies = $query->paginate(15)->withQueryString();

        // Statistics
        $stats = [
            'total' => Company::count(),
            'this_month' => Company::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'with_employees' => Company::has('employees')->count(),
        ];

        return view('superadmin.companies.index', compact('companies', 'stats'));
    }

    public function show(Company $company): View
    {
        $company->load(['employees' => function ($q) {
            $q->with('user')->limit(10);
        }, 'users' => function ($q) {
            $q->limit(10);
        }]);

        $company->loadCount(['employees', 'users', 'departments', 'positions']);

        return view('superadmin.companies.show', compact('company'));
    }
}
