<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOfficeLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = app('tenant');

        // Get the office location from route - could be model or ID
        $officeLocation = $this->route('office_location') ?? $this->route('officeLocation');

        // Handle both implicit binding (model) and explicit (ID)
        if ($officeLocation instanceof \App\Models\OfficeLocation) {
            $officeLocationId = $officeLocation->id;
        } else {
            $officeLocationId = $officeLocation;
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('office_locations', 'code')
                    ->where('company_id', $tenant->id)
                    ->ignore($officeLocationId),
            ],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'radius' => ['nullable', 'integer', 'min:10', 'max:10000'],
            'is_active' => ['boolean'],
            'is_headquarters' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama lokasi kantor wajib diisi.',
            'code.required' => 'Kode lokasi wajib diisi.',
            'code.unique' => 'Kode lokasi sudah digunakan.',
            'latitude.between' => 'Latitude harus antara -90 dan 90.',
            'longitude.between' => 'Longitude harus antara -180 dan 180.',
            'radius.min' => 'Radius minimal 10 meter.',
            'radius.max' => 'Radius maksimal 10.000 meter.',
        ];
    }
}
