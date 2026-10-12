@extends('layouts.guest')

@section('title', 'Syarat dan Ketentuan - GajiPro')
@section('description', 'Syarat dan Ketentuan penggunaan layanan GajiPro.')

@section('content')
    @include('components.navbar')

    <div class="pt-24 pb-16">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            {{-- Header --}}
            <div class="text-center mb-12">
                <h1 class="text-3xl md:text-4xl font-bold text-secondary-900 mb-4">Syarat dan Ketentuan</h1>
                <p class="text-secondary-500">Terakhir diperbarui: {{ now()->format('d F Y') }}</p>
            </div>

            {{-- Content --}}
            <div class="prose prose-lg max-w-none">
                <div class="bg-white rounded-2xl p-8 md:p-12 shadow-sm border border-secondary-100">

                    <section class="mb-8">
                        <h2 class="text-xl font-bold text-secondary-900 mb-4">1. Penerimaan Ketentuan</h2>
                        <p class="text-secondary-600 mb-4">
                            Dengan mengakses atau menggunakan layanan GajiPro ("Layanan"), Anda menyetujui untuk terikat oleh Syarat dan Ketentuan ini. Jika Anda tidak menyetujui syarat-syarat ini, Anda tidak diperkenankan menggunakan Layanan kami.
                        </p>
                        <p class="text-secondary-600">
                            GajiPro berhak untuk mengubah Syarat dan Ketentuan ini kapan saja. Perubahan akan berlaku efektif setelah dipublikasikan di situs web kami. Penggunaan Layanan secara berkelanjutan setelah perubahan tersebut merupakan penerimaan Anda terhadap syarat yang diperbarui.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-bold text-secondary-900 mb-4">2. Deskripsi Layanan</h2>
                        <p class="text-secondary-600 mb-4">
                            GajiPro adalah platform Software as a Service (SaaS) yang menyediakan solusi manajemen Human Resource (HR) dan Payroll untuk bisnis di Indonesia. Layanan kami meliputi:
                        </p>
                        <ul class="list-disc list-inside text-secondary-600 space-y-2 ml-4">
                            <li>Manajemen data karyawan</li>
                            <li>Sistem kehadiran dengan GPS dan selfie</li>
                            <li>Pengelolaan cuti dan izin</li>
                            <li>Penggajian otomatis dengan perhitungan PPh 21 dan BPJS</li>
                            <li>Laporan HR dan keuangan</li>
                            <li>Aplikasi mobile untuk karyawan (Employee Self Service)</li>
                        </ul>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-bold text-secondary-900 mb-4">3. Pendaftaran Akun</h2>
                        <p class="text-secondary-600 mb-4">
                            Untuk menggunakan Layanan, Anda harus mendaftar dan membuat akun. Anda bertanggung jawab untuk:
                        </p>
                        <ul class="list-disc list-inside text-secondary-600 space-y-2 ml-4">
                            <li>Memberikan informasi yang akurat dan lengkap saat pendaftaran</li>
                            <li>Menjaga kerahasiaan kata sandi akun Anda</li>
                            <li>Memberitahu kami segera jika ada penggunaan tidak sah atas akun Anda</li>
                            <li>Semua aktivitas yang terjadi di bawah akun Anda</li>
                        </ul>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-bold text-secondary-900 mb-4">4. Paket Layanan dan Pembayaran</h2>
                        <p class="text-secondary-600 mb-4">
                            GajiPro menawarkan beberapa paket layanan dengan fitur dan harga yang berbeda:
                        </p>
                        <ul class="list-disc list-inside text-secondary-600 space-y-2 ml-4">
                            <li><strong>Paket Starter:</strong> Gratis dengan fitur terbatas untuk maksimal 5 karyawan</li>
                            <li><strong>Paket Professional:</strong> Berbayar per pengguna per bulan dengan fitur lengkap</li>
                            <li><strong>Paket Enterprise:</strong> Harga khusus dengan fitur kustom dan dukungan prioritas</li>
                        </ul>
                        <p class="text-secondary-600 mt-4">
                            Pembayaran dilakukan di muka setiap bulan atau tahun. Pengembalian dana hanya berlaku dalam 14 hari pertama untuk pelanggan baru, dengan syarat dan ketentuan yang berlaku.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-bold text-secondary-900 mb-4">5. Penggunaan yang Dilarang</h2>
                        <p class="text-secondary-600 mb-4">
                            Anda tidak diperkenankan menggunakan Layanan untuk:
                        </p>
                        <ul class="list-disc list-inside text-secondary-600 space-y-2 ml-4">
                            <li>Melanggar hukum atau peraturan yang berlaku</li>
                            <li>Mengunggah konten ilegal, berbahaya, atau melanggar hak pihak lain</li>
                            <li>Mencoba mengakses sistem atau data tanpa izin</li>
                            <li>Menyebarkan virus, malware, atau kode berbahaya lainnya</li>
                            <li>Mengganggu atau merusak infrastruktur Layanan</li>
                            <li>Menjual kembali atau mendistribusikan ulang Layanan tanpa izin</li>
                        </ul>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-bold text-secondary-900 mb-4">6. Hak Kekayaan Intelektual</h2>
                        <p class="text-secondary-600 mb-4">
                            Semua hak kekayaan intelektual dalam Layanan, termasuk namun tidak terbatas pada perangkat lunak, desain, logo, dan konten, adalah milik GajiPro atau pemberi lisensinya. Anda diberikan lisensi terbatas, non-eksklusif, dan tidak dapat dialihkan untuk menggunakan Layanan sesuai dengan Syarat dan Ketentuan ini.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-bold text-secondary-900 mb-4">7. Data dan Privasi</h2>
                        <p class="text-secondary-600 mb-4">
                            Penggunaan data pribadi Anda diatur dalam Kebijakan Privasi kami. Dengan menggunakan Layanan, Anda menyetujui pengumpulan dan penggunaan informasi sesuai dengan Kebijakan Privasi tersebut.
                        </p>
                        <p class="text-secondary-600">
                            Anda bertanggung jawab untuk memastikan bahwa Anda memiliki hak dan persetujuan yang diperlukan untuk memasukkan data karyawan ke dalam sistem kami.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-bold text-secondary-900 mb-4">8. Batasan Tanggung Jawab</h2>
                        <p class="text-secondary-600 mb-4">
                            Sejauh diizinkan oleh hukum yang berlaku:
                        </p>
                        <ul class="list-disc list-inside text-secondary-600 space-y-2 ml-4">
                            <li>Layanan disediakan "sebagaimana adanya" tanpa jaminan apapun</li>
                            <li>GajiPro tidak bertanggung jawab atas kerugian tidak langsung, insidental, atau konsekuensial</li>
                            <li>Tanggung jawab maksimum kami terbatas pada jumlah yang Anda bayarkan dalam 12 bulan terakhir</li>
                        </ul>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-bold text-secondary-900 mb-4">9. Pengakhiran</h2>
                        <p class="text-secondary-600 mb-4">
                            Anda dapat menghentikan penggunaan Layanan kapan saja dengan menonaktifkan akun Anda. GajiPro dapat menangguhkan atau mengakhiri akses Anda ke Layanan jika Anda melanggar Syarat dan Ketentuan ini.
                        </p>
                        <p class="text-secondary-600">
                            Setelah pengakhiran, Anda akan memiliki waktu 30 hari untuk mengunduh data Anda. Setelah periode tersebut, kami berhak menghapus semua data Anda dari sistem kami.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-bold text-secondary-900 mb-4">10. Hukum yang Berlaku</h2>
                        <p class="text-secondary-600">
                            Syarat dan Ketentuan ini diatur oleh dan ditafsirkan sesuai dengan hukum Republik Indonesia. Setiap perselisihan yang timbul dari atau sehubungan dengan Syarat dan Ketentuan ini akan diselesaikan melalui pengadilan yang berwenang di Jakarta, Indonesia.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-xl font-bold text-secondary-900 mb-4">11. Hubungi Kami</h2>
                        <p class="text-secondary-600 mb-4">
                            Jika Anda memiliki pertanyaan tentang Syarat dan Ketentuan ini, silakan hubungi kami:
                        </p>
                        <div class="bg-secondary-50 rounded-xl p-6">
                            <p class="text-secondary-600"><strong>Email:</strong> legal@gajipro.com</p>
                            <p class="text-secondary-600"><strong>Telepon:</strong> +62 21 1234 5678</p>
                            <p class="text-secondary-600"><strong>Alamat:</strong> Jakarta, Indonesia</p>
                        </div>
                    </section>

                </div>
            </div>

            {{-- Back Link --}}
            <div class="mt-8 text-center">
                <a href="{{ url('/') }}" class="text-primary-600 hover:text-primary-700 font-medium inline-flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>

    @include('components.footer')
@endsection
