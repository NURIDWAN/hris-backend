@extends('layouts.admin')

@section('title', 'Pendaftaran Wajah - ' . $employee->full_name)

@section('breadcrumb')
    <a href="{{ route('face-recognition.index') }}" class="text-primary-600 hover:text-primary-700">Pendaftaran Wajah</a>
    <span class="text-secondary-400 mx-2">/</span>
    <span class="text-slate-700 font-medium">{{ $employee->full_name }}</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Pendaftaran Wajah</h1>
            <p class="text-secondary-500 mt-1">{{ $employee->full_name }} - {{ $employee->employee_id }}</p>
        </div>
        <div>
            <a href="{{ route('face-recognition.index') }}" class="btn btn-ghost">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Employee Info --}}
        <div class="lg:col-span-1">
            <div class="card">
                <div class="card-body text-center">
                    <div class="w-24 h-24 bg-primary-100 rounded-full flex items-center justify-center text-primary-600 font-bold text-3xl mx-auto mb-4">
                        {{ strtoupper(substr($employee->first_name, 0, 1)) }}{{ strtoupper(substr($employee->last_name ?? '', 0, 1)) }}
                    </div>
                    <h3 class="font-semibold text-lg text-secondary-900">{{ $employee->full_name }}</h3>
                    <p class="text-secondary-500 text-sm">{{ $employee->employee_id }}</p>
                    <div class="mt-4 space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-secondary-500">Departemen:</span>
                            <span class="text-secondary-900">{{ $employee->department?->name ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-secondary-500">Jabatan:</span>
                            <span class="text-secondary-900">{{ $employee->position?->name ?? '-' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            @if($embedding)
                {{-- Current Enrollment --}}
                <div class="card mt-4">
                    <div class="card-header">
                        <h3 class="card-title">Status Pendaftaran</h3>
                    </div>
                    <div class="card-body">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 bg-success-100 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium text-success-600">Wajah Terdaftar</p>
                                <p class="text-xs text-secondary-500">{{ $embedding->enrolled_at->format('d M Y H:i') }}</p>
                            </div>
                        </div>

                        @if($embedding->enrollment_photo)
                            <div class="mb-4">
                                <p class="text-sm text-secondary-500 mb-2">Foto Terdaftar:</p>
                                <img src="{{ Storage::url($embedding->enrollment_photo) }}" alt="Foto Wajah" class="w-full rounded-lg border">
                            </div>
                        @endif

                        <button
                            type="button"
                            @click="$dispatch('confirm-dialog', {
                                title: 'Hapus Pendaftaran Wajah',
                                message: 'Apakah Anda yakin ingin menghapus pendaftaran wajah {{ $employee->full_name }}? Karyawan ini perlu didaftarkan ulang untuk dapat melakukan presensi dengan wajah.',
                                confirmText: 'Ya, Hapus',
                                type: 'danger',
                                formAction: '{{ route('face-recognition.destroy', $employee) }}'
                            })"
                            class="btn btn-danger w-full"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Hapus Pendaftaran
                        </button>
                    </div>
                </div>
            @endif
        </div>

        {{-- Enrollment Form --}}
        <div class="lg:col-span-2">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        @if($embedding)
                            Daftarkan Ulang Wajah
                        @else
                            Daftarkan Wajah Baru
                        @endif
                    </h3>
                </div>
                <div class="card-body">
                    @if($embedding)
                        <x-alert type="warning" class="mb-4">
                            Karyawan ini sudah memiliki pendaftaran wajah. Mengunggah foto baru akan menggantikan pendaftaran yang ada.
                        </x-alert>
                    @endif

                    <form action="{{ route('face-recognition.store', $employee) }}" method="POST" enctype="multipart/form-data" x-data="faceEnrollment()">
                        @csrf

                        {{-- Camera/Upload Option --}}
                        <div class="mb-6">
                            <div class="flex gap-4 mb-4">
                                <button type="button" @click="mode = 'upload'" :class="mode === 'upload' ? 'btn-primary' : 'btn-ghost'" class="btn flex-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    Upload Foto
                                </button>
                                <button type="button" @click="mode = 'camera'" :class="mode === 'camera' ? 'btn-primary' : 'btn-ghost'" class="btn flex-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    Gunakan Kamera
                                </button>
                            </div>
                        </div>

                        {{-- Upload Mode --}}
                        <div x-show="mode === 'upload'" class="mb-6">
                            <label class="block text-sm font-medium text-secondary-700 mb-2">
                                Upload Foto Wajah <span class="text-danger-500">*</span>
                            </label>
                            <div class="border-2 border-dashed border-secondary-300 rounded-lg p-8 text-center hover:border-primary-400 transition-colors"
                                 x-on:dragover.prevent="isDragging = true"
                                 x-on:dragleave.prevent="isDragging = false"
                                 x-on:drop.prevent="handleDrop($event)"
                                 :class="isDragging ? 'border-primary-500 bg-primary-50' : ''">
                                <template x-if="!previewUrl">
                                    <div>
                                        <svg class="w-12 h-12 text-secondary-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                        </svg>
                                        <p class="text-secondary-600 mb-2">Drag & drop foto di sini atau</p>
                                        <label class="btn btn-primary cursor-pointer">
                                            <input type="file" name="photo" accept="image/*" class="hidden" @change="handleFileSelect($event)">
                                            Pilih File
                                        </label>
                                        <p class="text-xs text-secondary-400 mt-2">Format: JPG, PNG. Maks: 5MB</p>
                                    </div>
                                </template>
                                <template x-if="previewUrl">
                                    <div>
                                        <img :src="previewUrl" class="max-h-64 mx-auto rounded-lg mb-4">
                                        <button type="button" @click="clearPreview()" class="btn btn-ghost btn-sm">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                            Hapus
                                        </button>
                                    </div>
                                </template>
                            </div>
                            @error('photo')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Camera Mode --}}
                        <div x-show="mode === 'camera'" class="mb-6">
                            <div class="bg-secondary-900 rounded-lg overflow-hidden relative aspect-video">
                                <video x-ref="video" autoplay playsinline class="w-full h-full object-cover" x-show="!capturedPhoto"></video>
                                <img x-show="capturedPhoto" :src="capturedPhoto" class="w-full h-full object-cover">

                                <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2">
                                    <template x-if="!capturedPhoto">
                                        <button type="button" @click="capturePhoto()" class="btn btn-primary">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                            Ambil Foto
                                        </button>
                                    </template>
                                    <template x-if="capturedPhoto">
                                        <button type="button" @click="retakePhoto()" class="btn btn-ghost">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                            </svg>
                                            Ambil Ulang
                                        </button>
                                    </template>
                                </div>
                            </div>
                            <canvas x-ref="canvas" class="hidden"></canvas>
                        </div>

                        {{-- Guidelines --}}
                        <div class="bg-info-50 border border-info-200 rounded-lg p-4 mb-6">
                            <h4 class="font-medium text-info-800 mb-2">Panduan Foto Wajah:</h4>
                            <ul class="text-sm text-info-700 space-y-1">
                                <li>
                                    <svg class="w-4 h-4 inline text-info-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Pastikan wajah terlihat jelas dan tidak tertutup
                                </li>
                                <li>
                                    <svg class="w-4 h-4 inline text-info-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Pencahayaan yang baik (tidak terlalu gelap/terang)
                                </li>
                                <li>
                                    <svg class="w-4 h-4 inline text-info-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Wajah menghadap langsung ke kamera
                                </li>
                                <li>
                                    <svg class="w-4 h-4 inline text-info-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Lepaskan kacamata hitam atau penutup wajah
                                </li>
                            </ul>
                        </div>

                        {{-- Submit --}}
                        <div class="flex justify-end gap-3">
                            <a href="{{ route('face-recognition.index') }}" class="btn btn-ghost">Batal</a>
                            <button type="submit" class="btn btn-primary" :disabled="!canSubmit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Daftarkan Wajah
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function faceEnrollment() {
            return {
                mode: 'upload',
                isDragging: false,
                previewUrl: null,
                capturedPhoto: null,
                stream: null,

                get canSubmit() {
                    return this.previewUrl !== null || this.capturedPhoto !== null;
                },

                handleFileSelect(event) {
                    const file = event.target.files[0];
                    if (file) {
                        this.previewUrl = URL.createObjectURL(file);
                    }
                },

                handleDrop(event) {
                    this.isDragging = false;
                    const file = event.dataTransfer.files[0];
                    if (file && file.type.startsWith('image/')) {
                        this.previewUrl = URL.createObjectURL(file);
                        // Update the file input
                        const input = this.$el.querySelector('input[type="file"]');
                        const dataTransfer = new DataTransfer();
                        dataTransfer.items.add(file);
                        input.files = dataTransfer.files;
                    }
                },

                clearPreview() {
                    this.previewUrl = null;
                    const input = this.$el.querySelector('input[type="file"]');
                    input.value = '';
                },

                async startCamera() {
                    try {
                        this.stream = await navigator.mediaDevices.getUserMedia({
                            video: { facingMode: 'user', width: 640, height: 480 }
                        });
                        this.$refs.video.srcObject = this.stream;
                    } catch (err) {
                        console.error('Error accessing camera:', err);
                        alert('Tidak dapat mengakses kamera. Pastikan izin kamera diberikan.');
                    }
                },

                stopCamera() {
                    if (this.stream) {
                        this.stream.getTracks().forEach(track => track.stop());
                        this.stream = null;
                    }
                },

                capturePhoto() {
                    const video = this.$refs.video;
                    const canvas = this.$refs.canvas;
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    canvas.getContext('2d').drawImage(video, 0, 0);
                    this.capturedPhoto = canvas.toDataURL('image/jpeg', 0.8);

                    // Convert to file and set to input
                    canvas.toBlob((blob) => {
                        const file = new File([blob], 'captured-face.jpg', { type: 'image/jpeg' });
                        const input = this.$el.querySelector('input[type="file"]');
                        const dataTransfer = new DataTransfer();
                        dataTransfer.items.add(file);
                        input.files = dataTransfer.files;
                    }, 'image/jpeg', 0.8);

                    this.stopCamera();
                },

                retakePhoto() {
                    this.capturedPhoto = null;
                    this.startCamera();
                },

                init() {
                    this.$watch('mode', (value) => {
                        if (value === 'camera') {
                            this.startCamera();
                        } else {
                            this.stopCamera();
                            this.capturedPhoto = null;
                        }
                    });
                }
            };
        }
    </script>
    @endpush
@endsection
