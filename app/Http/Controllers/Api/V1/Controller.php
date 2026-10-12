<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller as BaseController;
use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'GajiPro HRIS/Payroll API',
    description: 'REST API untuk aplikasi GajiPro HRIS/Payroll SaaS. API ini digunakan oleh aplikasi mobile Flutter untuk mengelola absensi, cuti, lembur, slip gaji, pinjaman, dan reimbursement karyawan.',
    contact: new OA\Contact(
        name: 'GajiPro Support',
        email: 'support@gajipro.com'
    )
)]
#[OA\Server(
    url: '/api/v1',
    description: 'GajiPro API v1'
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'Masukkan token Sanctum Anda (tanpa prefix Bearer)'
)]
#[OA\Tag(name: 'Authentication', description: 'Login, logout, dan manajemen profil')]
#[OA\Tag(name: 'Attendance', description: 'Clock in/out dan riwayat kehadiran')]
#[OA\Tag(name: 'Leave', description: 'Pengajuan dan manajemen cuti')]
#[OA\Tag(name: 'Overtime', description: 'Pengajuan dan manajemen lembur')]
#[OA\Tag(name: 'Payslip', description: 'Slip gaji dan ringkasan penghasilan')]
#[OA\Tag(name: 'Reimbursement', description: 'Pengajuan reimbursement')]
#[OA\Tag(name: 'Loan', description: 'Pengajuan pinjaman karyawan')]
#[OA\Tag(name: 'Announcement', description: 'Pengumuman perusahaan')]
#[OA\Tag(name: 'Dashboard', description: 'Dashboard dan statistik untuk mobile app')]
#[OA\Tag(name: 'Approvals', description: 'Approval cuti, lembur, dan reimbursement untuk manager')]
#[OA\Tag(name: 'DeviceToken', description: 'Manajemen device token untuk push notification')]
#[OA\Tag(name: 'FaceRecognition', description: 'Face enrollment dan verifikasi untuk absensi')]
#[OA\Tag(name: 'OfficeLocation', description: 'Lokasi kantor dan validasi GPS')]
abstract class Controller extends BaseController {}
