<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\AttendanceClockIn;
use App\Events\AttendanceClockOut;
use App\Models\Attendance;
use App\Models\FaceVerificationLog;
use App\Services\FaceRecognitionService;
use App\Services\GpsValidationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AttendanceController extends Controller
{
    public function __construct(
        protected GpsValidationService $gpsValidationService,
        protected FaceRecognitionService $faceRecognitionService
    ) {}

    #[OA\Get(
        path: '/attendance/today',
        summary: 'Mendapatkan data kehadiran hari ini',
        description: 'Mengembalikan data kehadiran karyawan untuk hari ini beserta jadwal kerja',
        tags: ['Attendance'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Data kehadiran hari ini',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            nullable: true,
                            properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'date', type: 'string', format: 'date'),
                                new OA\Property(property: 'clock_in', type: 'string', example: '08:00'),
                                new OA\Property(property: 'clock_out', type: 'string', nullable: true),
                                new OA\Property(property: 'status', type: 'string', enum: ['present', 'late', 'absent', 'leave']),
                                new OA\Property(property: 'status_label', type: 'string'),
                                new OA\Property(property: 'late_minutes', type: 'integer'),
                                new OA\Property(property: 'working_minutes', type: 'integer', nullable: true),
                                new OA\Property(
                                    property: 'schedule',
                                    type: 'object',
                                    nullable: true,
                                    properties: [
                                        new OA\Property(property: 'start_time', type: 'string', example: '08:00'),
                                        new OA\Property(property: 'end_time', type: 'string', example: '17:00'),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'office_location',
                                    type: 'object',
                                    nullable: true,
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 1),
                                        new OA\Property(property: 'name', type: 'string', example: 'Kantor Pusat'),
                                    ]
                                ),
                            ]
                        ),
                        new OA\Property(
                            property: 'schedule',
                            type: 'object',
                            nullable: true,
                            properties: [
                                new OA\Property(property: 'name', type: 'string'),
                                new OA\Property(property: 'start_time', type: 'string'),
                                new OA\Property(property: 'end_time', type: 'string'),
                            ]
                        ),
                        new OA\Property(
                            property: 'timezone',
                            type: 'object',
                            description: 'Company timezone information for client-side time handling',
                            properties: [
                                new OA\Property(property: 'name', type: 'string', example: 'Asia/Jakarta', description: 'IANA timezone name'),
                                new OA\Property(property: 'offset', type: 'string', example: '+07:00', description: 'UTC offset'),
                                new OA\Property(property: 'current_time', type: 'string', example: '2026-02-18 15:30:00', description: 'Current server time in company timezone'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function today(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $company = $user->company;
            $employee = $user->employee;

            if (! $company) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data perusahaan tidak ditemukan atau akun perusahaan tidak aktif.',
                ], 422);
            }

            if (! $employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data karyawan tidak ditemukan. Akun Anda belum ditautkan dengan data pegawai aktif.',
                ], 422);
            }

            $today = $company->today();
            $schedule = $employee->resolveScheduleForDate(\Carbon\Carbon::parse($today));

            // Check today's attendance first, then check yesterday for overnight shifts
            $attendance = Attendance::with('officeLocation')
                ->where('employee_id', $employee->id)
                ->whereDate('date', $today)
                ->first();

            // If no attendance today, check for an active overnight shift from yesterday
            if (! $attendance) {
                $yesterday = $today->copy()->subDay();
                $attendance = Attendance::with('officeLocation')
                    ->where('employee_id', $employee->id)
                    ->whereDate('date', $yesterday)
                    ->whereNotNull('clock_in')
                    ->whereNull('clock_out')
                    ->first();

                // Only use yesterday's attendance if the schedule is overnight
                if ($attendance && (! $attendance->workSchedule || ! $attendance->workSchedule->is_overnight)) {
                    $attendance = null;
                }
            }

            $timezone = $company->timezone ?? 'Asia/Jakarta';

            $data = null;
            if ($attendance) {
                // Recalculate late_minutes if it's 0 but status is 'late' or clock_in is after schedule start
                $lateMinutes = $attendance->late_minutes ?? 0;
                if ($lateMinutes === 0 && $schedule && $attendance->clock_in) {
                    $clockInTime = $attendance->clock_in->setTimezone($timezone);
                    $scheduleStart = $clockInTime->copy()
                        ->setTimeFromTimeString($schedule->start_time->format('H:i:s'));

                    if ($clockInTime->gt($scheduleStart)) {
                        $lateMinutes = $scheduleStart->diffInMinutes($clockInTime);
                        // Update the database record for future consistency
                        if ($lateMinutes > 0) {
                            $attendance->update([
                                'late_minutes' => $lateMinutes,
                                'status' => 'late',
                            ]);
                        }
                    }
                }

                $data = [
                    'id' => $attendance->id,
                    'date' => $attendance->date->toDateString(),
                    'clock_in' => $attendance->clock_in?->setTimezone($timezone)->format('H:i'),
                    'clock_out' => $attendance->clock_out?->setTimezone($timezone)->format('H:i'),
                    'status' => $attendance->status,
                    'status_label' => $attendance->status_label,
                    'late_minutes' => $lateMinutes,
                    'working_minutes' => $attendance->working_minutes,
                    'schedule' => $schedule ? [
                        'start_time' => Carbon::parse($schedule->start_time)->format('H:i'),
                        'end_time' => Carbon::parse($schedule->end_time)->format('H:i'),
                    ] : null,
                    'office_location' => $attendance->officeLocation ? [
                        'id' => $attendance->officeLocation->id,
                        'name' => $attendance->officeLocation->name,
                    ] : null,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $data,
                'schedule' => $schedule ? [
                    'name' => $schedule->name,
                    'start_time' => Carbon::parse($schedule->start_time)->format('H:i'),
                    'end_time' => Carbon::parse($schedule->end_time)->format('H:i'),
                ] : null,
                'timezone' => [
                    'name' => $company->timezone,
                    'offset' => $company->timezone_offset,
                    'current_time' => $company->now()->format('Y-m-d H:i:s'),
                ],
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error fetching today attendance: '.$e->getMessage(), [
                'user_id' => $request->user()?->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data kehadiran hari ini. Silakan coba sesaat lagi.',
            ], 500);
        }
    }

    #[OA\Post(
        path: '/attendance/clock-in',
        summary: 'Clock in / Absen masuk',
        description: 'Melakukan absen masuk dengan lokasi GPS dan verifikasi wajah. Mendukung client-side validation untuk GPS dan face: kirim gps_verified=true + office_location_id untuk GPS client-side, dan face_verified=true untuk face client-side.',
        tags: ['Attendance'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['latitude', 'longitude'],
                    properties: [
                        new OA\Property(property: 'latitude', type: 'number', format: 'double', example: -6.200000),
                        new OA\Property(property: 'longitude', type: 'number', format: 'double', example: 106.816666),
                        new OA\Property(property: 'photo', type: 'string', format: 'binary', description: 'Foto selfie (jpg, jpeg, png)'),
                        new OA\Property(property: 'notes', type: 'string', example: 'Clock in dari kantor'),
                        new OA\Property(
                            property: 'face_verified',
                            type: 'boolean',
                            example: true,
                            description: 'Flag verifikasi wajah dari client (true jika sudah match di device lokal).'
                        ),
                        new OA\Property(
                            property: 'face_confidence',
                            type: 'number',
                            format: 'float',
                            example: 0.85,
                            description: 'Confidence score dari verifikasi wajah di client (0-1). Opsional, untuk audit log.'
                        ),
                        new OA\Property(
                            property: 'face_descriptors',
                            type: 'array',
                            items: new OA\Items(type: 'number', format: 'float'),
                            minItems: 128,
                            maxItems: 128,
                            description: '[LEGACY] Array 128 nilai face descriptor untuk server-side matching.'
                        ),
                        new OA\Property(
                            property: 'gps_verified',
                            type: 'boolean',
                            example: true,
                            description: 'Flag validasi GPS dari client (true jika sudah divalidasi di device lokal menggunakan assigned_offices dari login/profile).'
                        ),
                        new OA\Property(
                            property: 'office_location_id',
                            type: 'integer',
                            example: 1,
                            description: 'ID lokasi kantor yang tervalidasi di client. Wajib jika gps_verified=true.'
                        ),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Clock in berhasil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Clock in berhasil.'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'date', type: 'string', format: 'date'),
                                new OA\Property(property: 'clock_in', type: 'string', example: '08:05'),
                                new OA\Property(property: 'status', type: 'string'),
                                new OA\Property(property: 'late_minutes', type: 'integer'),
                                new OA\Property(property: 'face_verified', type: 'boolean', example: true),
                                new OA\Property(
                                    property: 'office_location',
                                    type: 'object',
                                    nullable: true,
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 1),
                                        new OA\Property(property: 'name', type: 'string', example: 'Kantor Pusat'),
                                    ]
                                ),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Sudah clock in atau lokasi terlalu jauh',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function clockIn(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $employee = $user->employee;
            $company = $user->company;

            if (! $company) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data perusahaan tidak ditemukan atau akun perusahaan tidak aktif.',
                ], 422);
            }

            if (! $employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data karyawan tidak ditemukan. Akun Anda belum ditautkan dengan data pegawai aktif.',
                ], 422);
            }

            $schedule = $employee->resolveScheduleForDate(\Carbon\Carbon::parse($company->today()));

        // Decode face_descriptors if sent as JSON string (e.g. from multipart form-data)
        if ($request->has('face_descriptors') && is_string($request->input('face_descriptors'))) {
            $decoded = json_decode($request->input('face_descriptors'), true);
            if (is_array($decoded)) {
                $request->merge(['face_descriptors' => $decoded]);
            }
        }

        // Build validation rules
        $rules = [
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'notes' => 'nullable|string|max:500',
            'face_verified' => 'nullable|boolean',
            'face_confidence' => 'nullable|numeric|min:0|max:1',
            'face_descriptors' => ['nullable', 'array', 'min:128', 'max:192'],
            'face_descriptors.*' => ['nullable', 'numeric'],
            'gps_verified' => 'nullable|boolean',
            'office_location_id' => 'nullable|integer|exists:office_locations,id',
        ];

        $request->validate($rules);

        // Check if soft-deleted record exists for today - force delete it
        $companyToday = $company->today();
        $softDeleted = Attendance::onlyTrashed()
            ->where('employee_id', $employee->id)
            ->whereDate('date', $companyToday)
            ->first();

        if ($softDeleted) {
            // Log to audit before force delete
            activity()
                ->performedOn($softDeleted)
                ->causedBy($user)
                ->withProperties([
                    'old_data' => $softDeleted->toArray(),
                    'reason' => 'Force deleted to allow new clock in',
                ])
                ->log('attendance_force_deleted');

            $softDeleted->forceDelete();
        }

        // Check if already clocked in today
        $existing = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $companyToday)
            ->first();

        if ($existing) {
            // Smart logic: allow replace if within 5 minutes and no clock out yet (handle double-tap)
            $companyNow = $company->now();
            $withinReplaceWindow = $existing->clock_in->diffInMinutes($companyNow) <= 5;
            $notClockedOut = is_null($existing->clock_out);

            if ($withinReplaceWindow && $notClockedOut) {
                // Will update existing record instead of creating new one
                $existingToUpdate = $existing;
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah melakukan clock in hari ini.',
                ], 422);
            }
        }

        // Check GPS if enabled
        // Always capture office_location_id if provided, even when GPS validation is disabled
        $officeLocationId = $request->office_location_id;
        if ($company->enable_gps_validation) {
            // Determine validation mode: client-side (gps_verified) or server-side
            $hasClientGpsVerification = $request->has('gps_verified');

            if ($hasClientGpsVerification) {
                // Client-side GPS validation flow - trust the client's verification result
                if (! $request->boolean('gps_verified')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validasi GPS gagal.',
                    ], 422);
                }

                // Validate office_location_id is provided and belongs to employee's assigned offices
                if (! $request->office_location_id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Office location ID diperlukan untuk validasi GPS client-side.',
                    ], 422);
                }

                // Verify the office is assigned to employee
                $isAssigned = $employee->officeLocations()
                    ->where('office_location_id', $request->office_location_id)
                    ->exists();

                if (! $isAssigned) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Office location tidak di-assign ke karyawan ini.',
                    ], 422);
                }

                $officeLocationId = $request->office_location_id;
            } else {
                // Server-side GPS validation (legacy flow)
                $gpsResult = $this->gpsValidationService->validateEmployeeLocation(
                    $employee,
                    $request->latitude,
                    $request->longitude
                );

                if (! $gpsResult['valid']) {
                    $message = match ($gpsResult['reason']) {
                        'no_assigned_offices' => 'Tidak ada lokasi kantor yang ditugaskan.',
                        'no_active_offices' => 'Tidak ada lokasi kantor aktif yang ditugaskan.',
                        'outside_radius' => 'Lokasi Anda terlalu jauh dari kantor.',
                        default => 'Validasi lokasi gagal.',
                    };

                    return response()->json([
                        'success' => false,
                        'message' => $message,
                    ], 422);
                }

                $officeLocationId = $gpsResult['office_location_id'];
            }
        }

        // Face recognition verification if enabled
        $faceVerified = false;
        $faceConfidence = null;
        if ($company->enable_face_recognition) {
            // Check if employee has face enrolled
            if (! $employee->hasFaceEnrolled()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Wajah belum terdaftar. Silakan daftarkan wajah Anda terlebih dahulu.',
                ], 422);
            }

            // Determine verification mode: client-side (face_verified) or server-side (face_descriptors)
            $hasClientVerification = $request->has('face_verified');
            $hasServerDescriptors = $request->has('face_descriptors')
                && is_array($request->face_descriptors)
                && count($request->face_descriptors) >= 128
                && count($request->face_descriptors) <= 192;

            if ($hasClientVerification) {
                // Client-side verification flow - trust the client's verification result
                if (! $request->boolean('face_verified')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Verifikasi wajah gagal.',
                    ], 422);
                }

                $faceVerified = true;
                $faceConfidence = $request->face_confidence;
            } elseif ($hasServerDescriptors) {
                // Legacy server-side verification flow
                $faceResult = $this->faceRecognitionService->verifyFace(
                    $employee,
                    $request->face_descriptors,
                    $company->face_match_threshold ?? 0.6,
                    'clock_in'
                );

                if (! $faceResult['matched']) {
                    $message = match ($faceResult['error'] ?? null) {
                        'no_face_enrolled' => 'Wajah belum terdaftar. Silakan daftarkan wajah Anda terlebih dahulu.',
                        default => 'Verifikasi wajah gagal. Wajah tidak cocok.',
                    };

                    return response()->json([
                        'success' => false,
                        'message' => $message,
                    ], 422);
                }

                $faceVerified = true;
                $faceConfidence = $faceResult['confidence'];
            } else {
                // No face verification provided when required
                return response()->json([
                    'success' => false,
                    'message' => 'Verifikasi wajah diperlukan untuk absensi.',
                ], 422);
            }
        }

        // Calculate late status using company timezone
        $now = $company->now();
        $status = 'present';
        $lateMinutes = 0;

        if ($schedule) {
            $scheduleStart = $now->copy()
                ->setTimeFromTimeString($schedule->start_time->format('H:i:s'));
            $tolerance = $schedule->late_tolerance ?? 15;

            if ($now->gt($scheduleStart->copy()->addMinutes($tolerance))) {
                $lateMinutes = $scheduleStart->diffInMinutes($now);
                $status = $lateMinutes > 30 ? 'late' : 'late';
            }
        }

        // Upload photo if provided
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')
                ->store('attendance-photos/'.$company->id, 'public');
        }

        // Create or update attendance record
        // Store clock_in in UTC for multi-timezone support
        $attendanceData = [
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'office_location_id' => $officeLocationId,
            'work_schedule_id' => $schedule?->id,
            'date' => $company->today(),
            'clock_in' => $company->toUtc($now),
            'clock_in_latitude' => $request->latitude,
            'clock_in_longitude' => $request->longitude,
            'clock_in_photo' => $photoPath,
            'clock_in_notes' => $request->notes,
            'face_verified' => $faceVerified,
            'face_confidence' => $faceConfidence,
            'status' => $status,
            'late_minutes' => $lateMinutes,
            'scheduled_start' => $schedule?->start_time,
            'scheduled_end' => $schedule?->end_time,
        ];

        try {
            if (isset($existingToUpdate)) {
                // Update existing record (within 5 minute window)
                $existingToUpdate->update($attendanceData);
                $attendance = $existingToUpdate;
            } else {
                // Create new record
                $attendance = Attendance::create($attendanceData);
            }
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            // Race condition: another request already created the attendance
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah melakukan clock in hari ini.',
            ], 422);
        }

        // Load office location for response
        $attendance->load('officeLocation');

        // Log face verification for audit purposes
        if ($company->enable_face_recognition && $faceVerified) {
            FaceVerificationLog::create([
                'employee_id' => $employee->id,
                'attendance_id' => $attendance->id,
                'verification_type' => 'clock_in',
                'is_successful' => true,
                'confidence_score' => $faceConfidence,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);
        }

        // Dispatch event for push notification
        AttendanceClockIn::dispatch($attendance);

        $responseData = [
            'id' => $attendance->id,
            'date' => $attendance->date->toDateString(),
            'clock_in' => $attendance->clock_in->setTimezone($company->timezone)->format('H:i'),
            'status' => $attendance->status,
            'late_minutes' => $attendance->late_minutes,
            'face_verified' => $attendance->face_verified,
        ];

        if ($attendance->officeLocation) {
            $responseData['office_location'] = [
                'id' => $attendance->officeLocation->id,
                'name' => $attendance->officeLocation->name,
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Clock in berhasil.',
            'data' => $responseData,
        ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            \Log::error('Error during clock-in: '.$e->getMessage(), [
                'user_id' => $request->user()?->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memproses absen masuk: '.$e->getMessage(),
            ], 500);
        }
    }

    #[OA\Post(
        path: '/attendance/clock-out',
        summary: 'Clock out / Absen pulang',
        description: 'Melakukan absen pulang dengan lokasi GPS dan verifikasi wajah. Mendukung client-side validation untuk GPS dan face: kirim gps_verified=true + office_location_id untuk GPS client-side, dan face_verified=true untuk face client-side.',
        tags: ['Attendance'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['latitude', 'longitude'],
                    properties: [
                        new OA\Property(property: 'latitude', type: 'number', format: 'double', example: -6.200000),
                        new OA\Property(property: 'longitude', type: 'number', format: 'double', example: 106.816666),
                        new OA\Property(property: 'photo', type: 'string', format: 'binary', description: 'Foto selfie'),
                        new OA\Property(property: 'notes', type: 'string'),
                        new OA\Property(
                            property: 'face_verified',
                            type: 'boolean',
                            example: true,
                            description: 'Flag verifikasi wajah dari client (true jika sudah match di device lokal).'
                        ),
                        new OA\Property(
                            property: 'face_confidence',
                            type: 'number',
                            format: 'float',
                            example: 0.85,
                            description: 'Confidence score dari verifikasi wajah di client (0-1). Opsional, untuk audit log.'
                        ),
                        new OA\Property(
                            property: 'face_descriptors',
                            type: 'array',
                            items: new OA\Items(type: 'number', format: 'float'),
                            minItems: 128,
                            maxItems: 128,
                            description: '[LEGACY] Array 128 nilai face descriptor untuk server-side matching.'
                        ),
                        new OA\Property(
                            property: 'gps_verified',
                            type: 'boolean',
                            example: true,
                            description: 'Flag validasi GPS dari client (true jika sudah divalidasi di device lokal menggunakan assigned_offices dari login/profile).'
                        ),
                        new OA\Property(
                            property: 'office_location_id',
                            type: 'integer',
                            example: 1,
                            description: 'ID lokasi kantor yang tervalidasi di client. Wajib jika gps_verified=true.'
                        ),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Clock out berhasil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Clock out berhasil.'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'date', type: 'string', format: 'date'),
                                new OA\Property(property: 'clock_in', type: 'string'),
                                new OA\Property(property: 'clock_out', type: 'string'),
                                new OA\Property(property: 'working_minutes', type: 'integer'),
                                new OA\Property(property: 'overtime_minutes', type: 'integer'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Belum clock in atau sudah clock out'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function clockOut(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $employee = $user->employee;
            $company = $user->company;

            if (! $company) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data perusahaan tidak ditemukan atau akun perusahaan tidak aktif.',
                ], 422);
            }

            if (! $employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data karyawan tidak ditemukan. Akun Anda belum ditautkan dengan data pegawai aktif.',
                ], 422);
            }

        // Decode face_descriptors if sent as JSON string (e.g. from multipart form-data)
        if ($request->has('face_descriptors') && is_string($request->input('face_descriptors'))) {
            $decoded = json_decode($request->input('face_descriptors'), true);
            if (is_array($decoded)) {
                $request->merge(['face_descriptors' => $decoded]);
            }
        }

        // Build validation rules
        $rules = [
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'notes' => 'nullable|string|max:500',
            'face_verified' => 'nullable|boolean',
            'face_confidence' => 'nullable|numeric|min:0|max:1',
            'face_descriptors' => ['nullable', 'array', 'min:128', 'max:192'],
            'face_descriptors.*' => ['nullable', 'numeric'],
            'gps_verified' => 'nullable|boolean',
            'office_location_id' => 'nullable|integer|exists:office_locations,id',
        ];

        $request->validate($rules);

        // Check if clocked in - check today first, then yesterday for overnight shifts
        $today = $company->today();
        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->first();

        if (! $attendance) {
            // Check yesterday for overnight shift
            $yesterday = $today->copy()->subDay();
            $attendance = Attendance::where('employee_id', $employee->id)
                ->whereDate('date', $yesterday)
                ->whereNotNull('clock_in')
                ->whereNull('clock_out')
                ->first();

            // Only use yesterday's attendance if the schedule is overnight
            if ($attendance && (! $attendance->workSchedule || ! $attendance->workSchedule->is_overnight)) {
                $attendance = null;
            }
        }

        if (! $attendance) {
            return response()->json([
                'success' => false,
                'message' => 'Anda belum melakukan clock in.',
            ], 422);
        }

        if ($attendance->clock_out) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah melakukan clock out hari ini.',
            ], 422);
        }

        // Use schedule from the attendance record (important for overnight shifts)
        $schedule = $attendance->workSchedule;

        // Check GPS if enabled
        // Always capture office_location_id if provided, even when GPS validation is disabled
        $clockOutOfficeLocationId = $request->office_location_id;
        if ($company->enable_gps_validation) {
            // Determine validation mode: client-side (gps_verified) or server-side
            $hasClientGpsVerification = $request->has('gps_verified');

            if ($hasClientGpsVerification) {
                // Client-side GPS validation flow - trust the client's verification result
                if (! $request->boolean('gps_verified')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validasi GPS gagal.',
                    ], 422);
                }

                // Validate office_location_id is provided and belongs to employee's assigned offices
                if (! $request->office_location_id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Office location ID diperlukan untuk validasi GPS client-side.',
                    ], 422);
                }

                // Verify the office is assigned to employee
                $isAssigned = $employee->officeLocations()
                    ->where('office_location_id', $request->office_location_id)
                    ->exists();

                if (! $isAssigned) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Office location tidak di-assign ke karyawan ini.',
                    ], 422);
                }

                $clockOutOfficeLocationId = $request->office_location_id;
            } else {
                // Server-side GPS validation (legacy flow)
                $gpsResult = $this->gpsValidationService->validateEmployeeLocation(
                    $employee,
                    $request->latitude,
                    $request->longitude
                );

                if (! $gpsResult['valid']) {
                    $message = match ($gpsResult['reason']) {
                        'no_assigned_offices' => 'Tidak ada lokasi kantor yang ditugaskan.',
                        'no_active_offices' => 'Tidak ada lokasi kantor aktif yang ditugaskan.',
                        'outside_radius' => 'Lokasi Anda terlalu jauh dari kantor.',
                        default => 'Validasi lokasi gagal.',
                    };

                    return response()->json([
                        'success' => false,
                        'message' => $message,
                    ], 422);
                }

                $clockOutOfficeLocationId = $gpsResult['office_location_id'];
            }
        }

        // Face recognition verification if enabled
        $faceVerified = false;
        $faceConfidence = null;
        if ($company->enable_face_recognition) {
            // Check if employee has face enrolled
            if (! $employee->hasFaceEnrolled()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Wajah belum terdaftar. Silakan daftarkan wajah Anda terlebih dahulu.',
                ], 422);
            }

            // Determine verification mode: client-side (face_verified) or server-side (face_descriptors)
            $hasClientVerification = $request->has('face_verified');
            $hasServerDescriptors = $request->has('face_descriptors')
                && is_array($request->face_descriptors)
                && count($request->face_descriptors) >= 128
                && count($request->face_descriptors) <= 192;

            if ($hasClientVerification) {
                // Client-side verification flow - trust the client's verification result
                if (! $request->boolean('face_verified')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Verifikasi wajah gagal.',
                    ], 422);
                }

                $faceVerified = true;
                $faceConfidence = $request->face_confidence;
            } elseif ($hasServerDescriptors) {
                // Legacy server-side verification flow
                $faceResult = $this->faceRecognitionService->verifyFace(
                    $employee,
                    $request->face_descriptors,
                    $company->face_match_threshold ?? 0.6,
                    'clock_out'
                );

                if (! $faceResult['matched']) {
                    $message = match ($faceResult['error'] ?? null) {
                        'no_face_enrolled' => 'Wajah belum terdaftar. Silakan daftarkan wajah Anda terlebih dahulu.',
                        default => 'Verifikasi wajah gagal. Wajah tidak cocok.',
                    };

                    return response()->json([
                        'success' => false,
                        'message' => $message,
                    ], 422);
                }

                $faceVerified = true;
                $faceConfidence = $faceResult['confidence'];
            } else {
                // No face verification provided when required
                return response()->json([
                    'success' => false,
                    'message' => 'Verifikasi wajah diperlukan untuk absensi.',
                ], 422);
            }
        }

        // Calculate working time using company timezone
        $now = $company->now();
        $workingMinutes = $attendance->clock_in->diffInMinutes($now);

        // Subtract break duration if applicable
        if ($schedule && $schedule->break_duration) {
            $workingMinutes -= $schedule->break_duration;
            $workingMinutes = max(0, $workingMinutes);
        }

        // Calculate early leave or overtime using scheduled end (accounts for overnight)
        $overtimeMinutes = 0;
        $earlyLeaveMinutes = 0;
        $clockOutStatus = 'on_time';
        $scheduledEnd = $attendance->getScheduledEndDatetime();

        if ($scheduledEnd) {
            $tolerance = $schedule?->early_leave_tolerance ?? 15;

            if ($now->lt($scheduledEnd->copy()->subMinutes($tolerance))) {
                $earlyLeaveMinutes = $now->diffInMinutes($scheduledEnd);
                $clockOutStatus = 'early';
            } elseif ($now->gt($scheduledEnd)) {
                $overtimeMinutes = $scheduledEnd->diffInMinutes($now);
                $clockOutStatus = 'overtime';
            }
        }

        // Upload photo if provided
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')
                ->store('attendance-photos/'.$company->id, 'public');
        }

        // Update attendance record
        // Store clock_out in UTC for multi-timezone support
        $attendance->update([
            'clock_out' => $company->toUtc($now),
            'clock_out_latitude' => $request->latitude,
            'clock_out_longitude' => $request->longitude,
            'clock_out_office_location_id' => $clockOutOfficeLocationId,
            'clock_out_photo' => $photoPath,
            'clock_out_notes' => $request->notes,
            'working_minutes' => $workingMinutes,
            'early_leave_minutes' => $earlyLeaveMinutes,
            'overtime_minutes' => $overtimeMinutes,
            'clock_out_status' => $clockOutStatus,
        ]);

        // Log face verification for audit purposes
        if ($company->enable_face_recognition && $faceVerified) {
            FaceVerificationLog::create([
                'employee_id' => $employee->id,
                'attendance_id' => $attendance->id,
                'verification_type' => 'clock_out',
                'is_successful' => true,
                'confidence_score' => $faceConfidence,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);
        }

        // Dispatch event for push notification
        AttendanceClockOut::dispatch($attendance);

        return response()->json([
            'success' => true,
            'message' => 'Clock out berhasil.',
            'data' => [
                'id' => $attendance->id,
                'date' => $attendance->date->toDateString(),
                'clock_in' => $attendance->clock_in->setTimezone($company->timezone)->format('H:i'),
                'clock_out' => $attendance->clock_out->setTimezone($company->timezone)->format('H:i'),
                'working_minutes' => $attendance->working_minutes,
                'overtime_minutes' => $attendance->overtime_minutes,
            ],
        ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            \Log::error('Error during clock-out: '.$e->getMessage(), [
                'user_id' => $request->user()?->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memproses absen keluar: '.$e->getMessage(),
            ], 500);
        }
    }

    #[OA\Get(
        path: '/attendance/history',
        summary: 'Riwayat kehadiran',
        description: 'Mendapatkan riwayat kehadiran karyawan dengan filter tanggal',
        tags: ['Attendance'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'start_date', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'end_date', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'month', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 12)),
            new OA\Parameter(name: 'year', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar riwayat kehadiran',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer'),
                                    new OA\Property(property: 'date', type: 'string', format: 'date'),
                                    new OA\Property(property: 'clock_in', type: 'string'),
                                    new OA\Property(property: 'clock_out', type: 'string', nullable: true),
                                    new OA\Property(property: 'status', type: 'string'),
                                    new OA\Property(property: 'status_label', type: 'string'),
                                    new OA\Property(property: 'working_hours', type: 'string'),
                                ]
                            )
                        ),
                        new OA\Property(
                            property: 'meta',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'current_page', type: 'integer'),
                                new OA\Property(property: 'last_page', type: 'integer'),
                                new OA\Property(property: 'per_page', type: 'integer'),
                                new OA\Property(property: 'total', type: 'integer'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function history(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'month' => 'nullable|integer|between:1,12',
            'year' => 'nullable|integer',
        ]);

        $user = $request->user();
        $employee = $user->employee;

        if (! $employee) {
            return response()->json([
                'success' => false,
                'message' => 'Data karyawan tidak ditemukan. Akun Anda belum ditautkan dengan data pegawai aktif.',
            ], 422);
        }

        $query = Attendance::with(['officeLocation', 'clockOutOfficeLocation'])
            ->where('employee_id', $employee->id)
            ->orderByDesc('date');

        if ($request->start_date && $request->end_date) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        } elseif ($request->month && $request->year) {
            $query->whereYear('date', $request->year)
                ->whereMonth('date', $request->month);
        }

        $attendances = $query->paginate(15);

        $company = $user->company;
        $timezone = $company->timezone ?? 'Asia/Jakarta';

        return response()->json([
            'success' => true,
            'data' => $attendances->map(function ($attendance) use ($timezone) {
                return [
                    'id' => $attendance->id,
                    'date' => $attendance->date->toDateString(),
                    'clock_in' => $attendance->clock_in?->setTimezone($timezone)->format('H:i'),
                    'clock_out' => $attendance->clock_out?->setTimezone($timezone)->format('H:i'),
                    'status' => $attendance->status,
                    'status_label' => $attendance->status_label,
                    'office_location_name' => $attendance->officeLocation?->name,
                    'working_formatted' => $attendance->working_minutes
                        ? sprintf('%d jam %d menit', floor($attendance->working_minutes / 60), $attendance->working_minutes % 60)
                        : null,
                    // Location data for detail screen
                    'clock_in_location' => $attendance->clock_in_latitude ? [
                        'latitude' => (float) $attendance->clock_in_latitude,
                        'longitude' => (float) $attendance->clock_in_longitude,
                        'office' => $attendance->officeLocation ? [
                            'id' => $attendance->officeLocation->id,
                            'name' => $attendance->officeLocation->name,
                            'address' => $attendance->officeLocation->address,
                            'latitude' => (float) $attendance->officeLocation->latitude,
                            'longitude' => (float) $attendance->officeLocation->longitude,
                            'radius' => $attendance->officeLocation->radius,
                        ] : null,
                    ] : null,
                    'clock_out_location' => $attendance->clock_out_latitude ? [
                        'latitude' => (float) $attendance->clock_out_latitude,
                        'longitude' => (float) $attendance->clock_out_longitude,
                        'office' => $attendance->clockOutOfficeLocation ? [
                            'id' => $attendance->clockOutOfficeLocation->id,
                            'name' => $attendance->clockOutOfficeLocation->name,
                            'address' => $attendance->clockOutOfficeLocation->address,
                            'latitude' => (float) $attendance->clockOutOfficeLocation->latitude,
                            'longitude' => (float) $attendance->clockOutOfficeLocation->longitude,
                            'radius' => $attendance->clockOutOfficeLocation->radius,
                        ] : null,
                    ] : null,
                ];
            }),
            'meta' => [
                'current_page' => $attendances->currentPage(),
                'last_page' => $attendances->lastPage(),
                'per_page' => $attendances->perPage(),
                'total' => $attendances->total(),
            ],
        ]);
    }

    #[OA\Get(
        path: '/attendance/summary',
        summary: 'Ringkasan kehadiran bulanan',
        description: 'Mendapatkan ringkasan statistik kehadiran karyawan untuk bulan tertentu',
        tags: ['Attendance'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'month', in: 'query', required: true, schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 12)),
            new OA\Parameter(name: 'year', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Ringkasan kehadiran',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'total_working_days', type: 'integer'),
                                new OA\Property(property: 'total_present', type: 'integer'),
                                new OA\Property(property: 'total_absent', type: 'integer'),
                                new OA\Property(property: 'total_late', type: 'integer'),
                                new OA\Property(property: 'total_leave', type: 'integer'),
                                new OA\Property(property: 'total_working_hours', type: 'number'),
                                new OA\Property(property: 'total_overtime_hours', type: 'number'),
                                new OA\Property(property: 'total_late_minutes', type: 'integer'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function summary(Request $request): JsonResponse
    {
        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer',
        ]);

        $user = $request->user();
        $employee = $user->employee;

        if (! $employee) {
            return response()->json([
                'success' => false,
                'message' => 'Data karyawan tidak ditemukan. Akun Anda belum ditautkan dengan data pegawai aktif.',
            ], 422);
        }

        // Optimized: Use database aggregate instead of loading all records
        $summary = Attendance::where('employee_id', $employee->id)
            ->whereYear('date', $request->year)
            ->whereMonth('date', $request->month)
            ->selectRaw("
                SUM(CASE WHEN status IN ('present', 'late') THEN 1 ELSE 0 END) as total_present,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as total_absent,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as total_late,
                SUM(CASE WHEN status = 'leave' THEN 1 ELSE 0 END) as total_leave,
                COALESCE(SUM(working_minutes), 0) as total_working_minutes,
                COALESCE(SUM(overtime_minutes), 0) as total_overtime_minutes,
                COALESCE(SUM(late_minutes), 0) as total_late_minutes
            ")
            ->first();

        // Calculate working days in the month (excluding weekends)
        $startOfMonth = Carbon::create($request->year, $request->month, 1);
        $endOfMonth = $startOfMonth->copy()->endOfMonth();
        $workingDays = 0;
        for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
            if (! $date->isWeekend()) {
                $workingDays++;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'total_working_days' => $workingDays,
                'total_present' => (int) ($summary->total_present ?? 0),
                'total_absent' => (int) ($summary->total_absent ?? 0),
                'total_late' => (int) ($summary->total_late ?? 0),
                'total_leave' => (int) ($summary->total_leave ?? 0),
                'total_working_hours' => round(($summary->total_working_minutes ?? 0) / 60, 2),
                'total_overtime_hours' => round(($summary->total_overtime_minutes ?? 0) / 60, 2),
                'total_late_minutes' => (int) ($summary->total_late_minutes ?? 0),
            ],
        ]);
    }

    /**
     * Calculate distance between two GPS coordinates using Haversine formula
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // meters

        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLon = deg2rad($lon2 - $lon1);

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
            cos($lat1Rad) * cos($lat2Rad) *
            sin($deltaLon / 2) * sin($deltaLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c; // Distance in meters
    }
}
