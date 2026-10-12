<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeaveRequestFormRequest;
use App\Models\ApprovalWorkflow;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\ApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LeaveRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = LeaveRequest::where('company_id', auth()->user()->company_id)
            ->with(['employee', 'leaveType']);

        // Filter for managers
        $user = auth()->user();
        $adminRoles = ['admin', 'hr-manager', 'payroll-manager'];
        if ($user->hasRole('manager') && ! $user->hasAnyRole($adminRoles)) {
            $employee = $user->employee;
            if ($employee) {
                $subordinateIds = Employee::where('manager_id', $employee->id)->pluck('id');
                $query->whereIn('employee_id', $subordinateIds);
            } else {
                $query->whereRaw('1 = 0'); // No results if no employee link
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('leave_type_id')) {
            $query->where('leave_type_id', $request->leave_type_id);
        }

        if ($request->filled('date_from')) {
            $query->where('start_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('end_date', '<=', $request->date_to);
        }

        $leaveRequests = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        $employees = Employee::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->orderBy('first_name')->orderBy('last_name')
            ->get();

        $leaveTypes = LeaveType::where('company_id', auth()->user()->company_id)
            ->active()
            ->orderBy('name')
            ->get();

        return view('leave-requests.index', compact('leaveRequests', 'employees', 'leaveTypes'));
    }

    public function create()
    {
        $employees = Employee::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->orderBy('first_name')->orderBy('last_name')
            ->get();

        $leaveTypes = LeaveType::where('company_id', auth()->user()->company_id)
            ->active()
            ->orderBy('name')
            ->get();

        return view('leave-requests.create', compact('employees', 'leaveTypes'));
    }

    public function store(LeaveRequestFormRequest $request)
    {
        $startDate = new \DateTime($request->start_date);
        $endDate = new \DateTime($request->end_date);
        $isHalfDay = $request->boolean('is_half_day');

        $totalDays = $isHalfDay ? 0.5 : ($startDate->diff($endDate)->days + 1);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('attachments/leave', 'public');
        }

        $leaveRequest = LeaveRequest::create([
            'company_id' => auth()->user()->company_id,
            'employee_id' => $request->employee_id,
            'leave_type_id' => $request->leave_type_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'total_days' => $totalDays,
            'is_half_day' => $isHalfDay,
            'half_day_type' => $isHalfDay ? $request->half_day_type : null,
            'reason' => $request->reason,
            'attachment' => $attachmentPath,
            'emergency_contact' => $request->emergency_contact,
            'status' => 'pending',
        ]);

        // Add to pending balance
        $balance = LeaveBalance::where('employee_id', $request->employee_id)
            ->where('leave_type_id', $request->leave_type_id)
            ->where('year', $startDate->format('Y'))
            ->first();

        if ($balance) {
            $balance->addPendingDays($totalDays);
        }

        // Initialize approval workflow if configured
        app(ApprovalService::class)->initializeWorkflow(
            $leaveRequest,
            ApprovalWorkflow::TYPE_LEAVE_REQUEST,
            auth()->user()->company_id
        );

        return redirect()->route('leave-requests.index')
            ->with('success', 'Pengajuan cuti berhasil dikirim.');
    }

    public function show(LeaveRequest $leaveRequest)
    {
        if ($leaveRequest->company_id !== auth()->user()->company_id) {
            abort(404);
        }

        $leaveRequest->load(['employee', 'leaveType', 'approvedBy', 'rejectedBy', 'cancelledBy']);

        $approvalService = app(ApprovalService::class);
        $approvalRecords = $leaveRequest->hasWorkflow()
            ? $approvalService->getApprovalRecords($leaveRequest)
            : collect();
        $canApproveCurrentStep = $leaveRequest->hasWorkflow() && $leaveRequest->isPending()
            ? $approvalService->canUserApprove($leaveRequest, auth()->user())
            : false;

        return view('leave-requests.show', compact('leaveRequest', 'approvalRecords', 'canApproveCurrentStep'));
    }

    public function edit(LeaveRequest $leaveRequest)
    {
        if ($leaveRequest->company_id !== auth()->user()->company_id) {
            abort(404);
        }

        if (! $leaveRequest->isPending()) {
            return redirect()->route('leave-requests.show', $leaveRequest)
                ->with('error', 'Pengajuan cuti yang sudah diproses tidak dapat diedit.');
        }

        $employees = Employee::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->orderBy('first_name')->orderBy('last_name')
            ->get();

        $leaveTypes = LeaveType::where('company_id', auth()->user()->company_id)
            ->active()
            ->orderBy('name')
            ->get();

        return view('leave-requests.edit', compact('leaveRequest', 'employees', 'leaveTypes'));
    }

    public function update(Request $request, LeaveRequest $leaveRequest)
    {
        if ($leaveRequest->company_id !== auth()->user()->company_id) {
            abort(404);
        }

        if (! $leaveRequest->isPending()) {
            return redirect()->route('leave-requests.show', $leaveRequest)
                ->with('error', 'Pengajuan cuti yang sudah diproses tidak dapat diedit.');
        }

        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'is_half_day' => ['nullable', 'boolean'],
            'half_day_type' => ['required_if:is_half_day,true', 'nullable', 'in:morning,afternoon'],
            'reason' => ['required', 'string', 'max:1000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'emergency_contact' => ['nullable', 'string', 'max:255'],
        ]);

        $startDate = new \DateTime($validated['start_date']);
        $endDate = new \DateTime($validated['end_date']);
        $isHalfDay = $request->boolean('is_half_day');
        $newTotalDays = $isHalfDay ? 0.5 : ($startDate->diff($endDate)->days + 1);
        $oldTotalDays = $leaveRequest->total_days;

        // Update balance if days changed
        if ($newTotalDays != $oldTotalDays) {
            $balance = LeaveBalance::where('employee_id', $leaveRequest->employee_id)
                ->where('leave_type_id', $leaveRequest->leave_type_id)
                ->where('year', $leaveRequest->start_date->year)
                ->first();

            if ($balance) {
                $balance->removePendingDays($oldTotalDays);
                $balance->addPendingDays($newTotalDays);
            }
        }

        if ($request->hasFile('attachment')) {
            if ($leaveRequest->attachment) {
                Storage::disk('public')->delete($leaveRequest->attachment);
            }
            $validated['attachment'] = $request->file('attachment')->store('attachments/leave', 'public');
        }

        $leaveRequest->update([
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'total_days' => $newTotalDays,
            'is_half_day' => $isHalfDay,
            'half_day_type' => $isHalfDay ? $validated['half_day_type'] : null,
            'reason' => $validated['reason'],
            'attachment' => $validated['attachment'] ?? $leaveRequest->attachment,
            'emergency_contact' => $validated['emergency_contact'],
        ]);

        return redirect()->route('leave-requests.show', $leaveRequest)
            ->with('success', 'Pengajuan cuti berhasil diperbarui.');
    }

    public function destroy(LeaveRequest $leaveRequest)
    {
        if ($leaveRequest->company_id !== auth()->user()->company_id) {
            abort(404);
        }

        // Restore balance if pending
        if ($leaveRequest->isPending()) {
            $balance = LeaveBalance::where('employee_id', $leaveRequest->employee_id)
                ->where('leave_type_id', $leaveRequest->leave_type_id)
                ->where('year', $leaveRequest->start_date->year)
                ->first();

            if ($balance) {
                $balance->removePendingDays($leaveRequest->total_days);
            }
        }

        if ($leaveRequest->attachment) {
            Storage::disk('public')->delete($leaveRequest->attachment);
        }

        $leaveRequest->delete();

        return redirect()->route('leave-requests.index')
            ->with('success', 'Pengajuan cuti berhasil dihapus.');
    }

    public function approve(Request $request, LeaveRequest $leaveRequest)
    {
        if ($leaveRequest->company_id !== auth()->user()->company_id) {
            abort(404);
        }

        if (! $leaveRequest->canBeApproved()) {
            return redirect()->back()
                ->with('error', 'Pengajuan cuti ini tidak dapat disetujui.');
        }

        if ($leaveRequest->hasWorkflow()) {
            $result = app(ApprovalService::class)->processApproval(
                $leaveRequest, auth()->user(), 'approve', $request->notes
            );

            if (! $result['success']) {
                return redirect()->back()->with('error', $result['message']);
            }

            return redirect()->back()->with('success', $result['message']);
        }

        // Legacy: no workflow
        $leaveRequest->approve(auth()->id(), $request->notes);

        return redirect()->back()
            ->with('success', 'Pengajuan cuti berhasil disetujui.');
    }

    public function reject(Request $request, LeaveRequest $leaveRequest)
    {
        if ($leaveRequest->company_id !== auth()->user()->company_id) {
            abort(404);
        }

        $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ], [
            'reason.required' => 'Alasan penolakan wajib diisi.',
        ]);

        if (! $leaveRequest->canBeRejected()) {
            return redirect()->back()
                ->with('error', 'Pengajuan cuti ini tidak dapat ditolak.');
        }

        if ($leaveRequest->hasWorkflow()) {
            $result = app(ApprovalService::class)->processApproval(
                $leaveRequest, auth()->user(), 'reject', $request->reason
            );

            if (! $result['success']) {
                return redirect()->back()->with('error', $result['message']);
            }

            return redirect()->back()->with('success', $result['message']);
        }

        // Legacy: no workflow
        $leaveRequest->reject(auth()->id(), $request->reason);

        return redirect()->back()
            ->with('success', 'Pengajuan cuti berhasil ditolak.');
    }

    public function cancel(Request $request, LeaveRequest $leaveRequest)
    {
        if ($leaveRequest->company_id !== auth()->user()->company_id) {
            abort(404);
        }

        if (! $leaveRequest->canBeCancelled()) {
            return redirect()->back()
                ->with('error', 'Pengajuan cuti ini tidak dapat dibatalkan.');
        }

        if ($leaveRequest->hasWorkflow()) {
            app(ApprovalService::class)->skipAllPending($leaveRequest);
        }

        $leaveRequest->cancel(auth()->id(), $request->reason);

        return redirect()->back()
            ->with('success', 'Pengajuan cuti berhasil dibatalkan.');
    }
}
