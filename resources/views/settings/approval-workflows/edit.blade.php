@extends('layouts.admin')

@section('title', 'Kelola Alur Persetujuan')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Pengaturan</span>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <a href="{{ route('settings.approval-workflows.index') }}" class="text-slate-500 hover:text-primary-600">Alur Persetujuan</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">{{ $approvalWorkflow->name }}</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">{{ $approvalWorkflow->name }}</h1>
            <p class="text-secondary-500 mt-1">{{ $approvalWorkflow->type_label }}</p>
        </div>
        <a href="{{ route('settings.approval-workflows.index') }}" class="btn btn-ghost">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Kembali
        </a>
    </div>
@endsection

@section('content')
    <div class="space-y-6">
        {{-- Workflow Info --}}
        <form action="{{ route('settings.approval-workflows.update', $approvalWorkflow) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Informasi Alur</h3>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-secondary-700 mb-1">
                                Nama Alur <span class="text-danger-500">*</span>
                            </label>
                            <input type="text" name="name" id="name"
                                   value="{{ old('name', $approvalWorkflow->name) }}"
                                   class="input w-full @error('name') border-danger-500 @enderror"
                                   required>
                            @error('name')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-secondary-700 mb-1">Status</label>
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1"
                                       {{ old('is_active', $approvalWorkflow->is_active) ? 'checked' : '' }}
                                       class="rounded border-secondary-300 text-primary-600 focus:ring-primary-500">
                                <span class="text-secondary-700">Aktifkan alur persetujuan</span>
                            </label>
                        </div>

                        <div class="md:col-span-2">
                            <label for="description" class="block text-sm font-medium text-secondary-700 mb-1">
                                Deskripsi
                            </label>
                            <textarea name="description" id="description" rows="2"
                                      class="input w-full @error('description') border-danger-500 @enderror">{{ old('description', $approvalWorkflow->description) }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="card-footer flex justify-end">
                    <button type="submit" class="btn btn-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Simpan
                    </button>
                </div>
            </div>
        </form>

        {{-- Steps --}}
        <div class="card">
            <div class="card-header flex items-center justify-between">
                <h3 class="card-title">Langkah Persetujuan</h3>
            </div>
            <div class="card-body">
                @if($approvalWorkflow->steps->count() > 0)
                    <div class="space-y-4">
                        @foreach($approvalWorkflow->steps as $step)
                            <div class="flex items-center gap-4 p-4 bg-secondary-50 rounded-lg">
                                <div class="w-8 h-8 rounded-full bg-primary-100 text-primary-600 flex items-center justify-center font-semibold">
                                    {{ $step->step_order }}
                                </div>
                                <div class="flex-1">
                                    <div class="font-medium text-secondary-900">{{ $step->approver_description }}</div>
                                    <div class="text-sm text-secondary-500">
                                        {{ $step->approver_type_label }}
                                        @if($step->can_skip)
                                            <span class="text-warning-600">(Dapat dilewati)</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button"
                                        onclick="editStep({{ $step->id }}, '{{ $step->approver_type }}', '{{ $step->approver_role }}', {{ $step->approver_user_id ?? 'null' }}, {{ $step->can_skip ? 'true' : 'false' }})"
                                        class="btn btn-ghost btn-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <button type="button"
                                        @click="$dispatch('confirm-dialog', {
                                            title: 'Hapus Langkah',
                                            message: 'Apakah Anda yakin ingin menghapus langkah ini?',
                                            confirmText: 'Ya, Hapus',
                                            type: 'danger',
                                            formAction: '{{ route('settings.approval-workflows.steps.destroy', [$approvalWorkflow, $step]) }}'
                                        })"
                                        class="btn btn-ghost btn-sm text-danger-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg class="w-12 h-12 mx-auto text-secondary-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <p class="text-secondary-500">Belum ada langkah persetujuan.</p>
                    </div>
                @endif

                {{-- Add Step Form --}}
                <div class="mt-6 pt-6 border-t border-secondary-200">
                    <h4 class="font-medium text-secondary-900 mb-4">Tambah Langkah Baru</h4>
                    <form action="{{ route('settings.approval-workflows.steps.store', $approvalWorkflow) }}" method="POST">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="approver_type" class="block text-sm font-medium text-secondary-700 mb-1">
                                    Tipe Approver <span class="text-danger-500">*</span>
                                </label>
                                <select name="approver_type" id="approver_type"
                                        class="input w-full @error('approver_type') border-danger-500 @enderror"
                                        onchange="toggleApproverFields(this.value)"
                                        required>
                                    <option value="">Pilih Tipe</option>
                                    <option value="role" {{ old('approver_type') == 'role' ? 'selected' : '' }}>Berdasarkan Role</option>
                                    <option value="specific_user" {{ old('approver_type') == 'specific_user' ? 'selected' : '' }}>Pengguna Tertentu</option>
                                    <option value="department_head" {{ old('approver_type') == 'department_head' ? 'selected' : '' }}>Kepala Departemen</option>
                                    <option value="direct_manager" {{ old('approver_type') == 'direct_manager' ? 'selected' : '' }}>Atasan Langsung</option>
                                </select>
                                @error('approver_type')
                                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div id="role_field" style="display: none;">
                                <label for="approver_role" class="block text-sm font-medium text-secondary-700 mb-1">
                                    Role <span class="text-danger-500">*</span>
                                </label>
                                <select name="approver_role" id="approver_role"
                                        class="input w-full @error('approver_role') border-danger-500 @enderror">
                                    <option value="">Pilih Role</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->name }}" {{ old('approver_role') == $role->name ? 'selected' : '' }}>
                                            {{ ucfirst(str_replace('-', ' ', $role->name)) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('approver_role')
                                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div id="user_field" style="display: none;">
                                <label for="approver_user_id" class="block text-sm font-medium text-secondary-700 mb-1">
                                    Pengguna <span class="text-danger-500">*</span>
                                </label>
                                <select name="approver_user_id" id="approver_user_id"
                                        class="input w-full @error('approver_user_id') border-danger-500 @enderror">
                                    <option value="">Pilih Pengguna</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ old('approver_user_id') == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }} ({{ $user->email }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('approver_user_id')
                                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-secondary-700 mb-1">Opsi</label>
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input type="hidden" name="can_skip" value="0">
                                    <input type="checkbox" name="can_skip" value="1"
                                           {{ old('can_skip') ? 'checked' : '' }}
                                           class="rounded border-secondary-300 text-primary-600 focus:ring-primary-500">
                                    <span class="text-secondary-700">Dapat dilewati</span>
                                </label>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                Tambah Langkah
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Edit Step Modal --}}
    <div id="editStepModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 99999;">
        <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0, 0, 0, 0.5);" onclick="closeEditModal()"></div>
        <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; display: flex; align-items: center; justify-content: center; padding: 1rem; pointer-events: none;">
            <div style="pointer-events: auto; width: 100%; max-width: 28rem; background-color: white; border-radius: 1rem; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-secondary-900 mb-4">Edit Langkah Persetujuan</h3>
                    <form id="editStepForm" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="space-y-4">
                            <div>
                                <label for="edit_approver_type" class="block text-sm font-medium text-secondary-700 mb-1">
                                    Tipe Approver <span class="text-danger-500">*</span>
                                </label>
                                <select name="approver_type" id="edit_approver_type"
                                        class="input w-full"
                                        onchange="toggleEditApproverFields(this.value)"
                                        required>
                                    <option value="">Pilih Tipe</option>
                                    <option value="role">Berdasarkan Role</option>
                                    <option value="specific_user">Pengguna Tertentu</option>
                                    <option value="department_head">Kepala Departemen</option>
                                    <option value="direct_manager">Atasan Langsung</option>
                                </select>
                            </div>

                            <div id="edit_role_field" style="display: none;">
                                <label for="edit_approver_role" class="block text-sm font-medium text-secondary-700 mb-1">
                                    Role <span class="text-danger-500">*</span>
                                </label>
                                <select name="approver_role" id="edit_approver_role" class="input w-full">
                                    <option value="">Pilih Role</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->name }}">{{ ucfirst(str_replace('-', ' ', $role->name)) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div id="edit_user_field" style="display: none;">
                                <label for="edit_approver_user_id" class="block text-sm font-medium text-secondary-700 mb-1">
                                    Pengguna <span class="text-danger-500">*</span>
                                </label>
                                <select name="approver_user_id" id="edit_approver_user_id" class="input w-full">
                                    <option value="">Pilih Pengguna</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input type="hidden" name="can_skip" value="0">
                                    <input type="checkbox" name="can_skip" id="edit_can_skip" value="1"
                                           class="rounded border-secondary-300 text-primary-600 focus:ring-primary-500">
                                    <span class="text-secondary-700">Dapat dilewati</span>
                                </label>
                            </div>
                        </div>
                        <div class="mt-6 flex justify-end gap-3">
                            <button type="button" onclick="closeEditModal()" class="btn btn-ghost">Batal</button>
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleApproverFields(type) {
            document.getElementById('role_field').style.display = type === 'role' ? 'block' : 'none';
            document.getElementById('user_field').style.display = type === 'specific_user' ? 'block' : 'none';
        }

        function toggleEditApproverFields(type) {
            document.getElementById('edit_role_field').style.display = type === 'role' ? 'block' : 'none';
            document.getElementById('edit_user_field').style.display = type === 'specific_user' ? 'block' : 'none';
        }

        function editStep(stepId, approverType, approverRole, approverUserId, canSkip) {
            const form = document.getElementById('editStepForm');
            form.action = '/settings/approval-workflows/{{ $approvalWorkflow->id }}/steps/' + stepId;

            document.getElementById('edit_approver_type').value = approverType;
            document.getElementById('edit_approver_role').value = approverRole || '';
            document.getElementById('edit_approver_user_id').value = approverUserId || '';
            document.getElementById('edit_can_skip').checked = canSkip;

            toggleEditApproverFields(approverType);

            document.getElementById('editStepModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editStepModal').style.display = 'none';
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            const type = document.getElementById('approver_type').value;
            if (type) {
                toggleApproverFields(type);
            }
        });
    </script>
@endsection
