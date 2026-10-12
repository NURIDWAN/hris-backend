<?php

namespace App\Http\Controllers\EmployeePortal;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $employee = Employee::with(['department', 'position', 'workSchedule'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        return view('portal.profile', compact('employee'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'emergency_contact_name' => 'nullable|string|max:100',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'emergency_contact_relationship' => 'nullable|string|max:50',
        ]);

        $user = auth()->user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        $employee->update($validated);

        return redirect()->route('portal.profile.index')
            ->with('success', 'Profil berhasil diperbarui.');
    }
}
