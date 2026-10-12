@extends('layouts.guest')

@section('title', 'Privacy Policy - GajiPro')
@section('description', 'Privacy Policy for GajiPro HR & Payroll Management application regarding collection and use of data.')

@section('content')
    @include('components.navbar')

    <div class="pt-24 pb-16">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            {{-- Header --}}
            <div class="text-center mb-12">
                <h1 class="text-3xl md:text-4xl font-bold text-secondary-900 mb-4">Privacy Policy</h1>
                <p class="text-secondary-500">Last updated: February 23, 2026</p>
            </div>

            {{-- Content --}}
            <div class="prose prose-lg max-w-none">
                <div class="bg-white rounded-2xl p-8 md:p-12 shadow-sm border border-secondary-100">

                    <section class="mb-8">
                        <h2 class="text-xl font-bold text-secondary-900 mb-4">1. Introduction</h2>
                        <p class="text-secondary-600 mb-4">
                            JagoFlutter ("we", "us", or "our") operates the GajiPro application, available as a web platform and mobile application (collectively, the "Service"). This Privacy Policy informs you of our policies regarding the collection, use, and disclosure of personal data when you use our Service and the choices you have associated with that data.
                        </p>
                        <p class="text-secondary-600">
                            By using GajiPro, you agree to the collection and use of information in accordance with this policy.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-bold text-secondary-900 mb-4">2. Information We Collect</h2>

                        <h3 class="text-lg font-semibold text-secondary-800 mb-3">2.1 Personal Information</h3>
                        <ul class="list-disc list-inside text-secondary-600 space-y-2 ml-4 mb-4">
                            <li><strong>Account Information:</strong> Name, email address, phone number, and password for authentication purposes.</li>
                            <li><strong>Company Information:</strong> Company name, address, tax ID (NPWP), and industry type.</li>
                            <li><strong>Employee Data:</strong> Name, date of birth, address, identity number, employment information, and salary data.</li>
                            <li><strong>Transaction Data:</strong> Payment and billing information.</li>
                        </ul>

                        <h3 class="text-lg font-semibold text-secondary-800 mb-3">2.2 Biometric Data (Mobile App)</h3>
                        <ul class="list-disc list-inside text-secondary-600 space-y-2 ml-4 mb-4">
                            <li><strong>Facial Recognition Data:</strong> We collect facial biometric data (face embeddings) for the purpose of verifying employee identity during attendance check-in and check-out. Facial data is processed on-device using machine learning models (Google ML Kit and TFLite). The resulting face embeddings are stored securely on our servers. Raw facial images are not permanently stored.</li>
                        </ul>

                        <h3 class="text-lg font-semibold text-secondary-800 mb-3">2.3 Location Data (Mobile App)</h3>
                        <ul class="list-disc list-inside text-secondary-600 space-y-2 ml-4 mb-4">
                            <li><strong>GPS Location:</strong> We collect precise location data (latitude and longitude) only during attendance check-in and check-out to validate that employees are within designated office areas. Location data is not collected in the background or outside of attendance activities.</li>
                        </ul>

                        <h3 class="text-lg font-semibold text-secondary-800 mb-3">2.4 Automatically Collected Information</h3>
                        <ul class="list-disc list-inside text-secondary-600 space-y-2 ml-4">
                            <li><strong>Usage Data:</strong> Activity logs, features used, and access times.</li>
                            <li><strong>Device Information:</strong> Device manufacturer, model, operating system version, and browser type.</li>
                            <li><strong>Push Notification Tokens:</strong> We use Firebase Cloud Messaging (FCM) to deliver work-related notifications. A device token is collected for this purpose.</li>
                            <li><strong>Cookies:</strong> Used on the web platform to enhance user experience.</li>
                        </ul>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-bold text-secondary-900 mb-4">3. How We Use Your Information</h2>
                        <p class="text-secondary-600 mb-4">
                            We use the information collected for the following purposes:
                        </p>
                        <ul class="list-disc list-inside text-secondary-600 space-y-2 ml-4">
                            <li>Providing and maintaining our Service</li>
                            <li>Processing transactions and sending related notifications</li>
                            <li>Calculating employee salaries, taxes, and benefits</li>
                            <li>Verifying employee attendance through face recognition and GPS location</li>
                            <li>Sending push notifications for work-related updates and announcements</li>
                            <li>Providing customer support</li>
                            <li>Detecting and preventing fraud</li>
                            <li>Complying with legal obligations</li>
                            <li>Analyzing and improving our Service</li>
                        </ul>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-bold text-secondary-900 mb-4">4. Information Sharing</h2>
                        <p class="text-secondary-600 mb-4">
                            We do not sell your personal data. We only share information in the following circumstances:
                        </p>
                        <ul class="list-disc list-inside text-secondary-600 space-y-2 ml-4">
                            <li><strong>With Your Employer:</strong> Employee attendance records, profile data, and payroll information are shared with your employer's HR/management team as part of the HR management system.</li>
                            <li><strong>With Consent:</strong> When you provide explicit permission.</li>
                            <li><strong>Service Providers:</strong> Third parties that help us operate our Service (hosting, payment processing, Firebase by Google for push notifications).</li>
                            <li><strong>Legal Compliance:</strong> If required by law or legal process.</li>
                            <li><strong>Rights Protection:</strong> To protect the rights, property, or safety of JagoFlutter and its users.</li>
                            <li><strong>Business Transactions:</strong> In the event of a merger, acquisition, or asset sale.</li>
                        </ul>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-bold text-secondary-900 mb-4">5. Data Security</h2>
                        <p class="text-secondary-600 mb-4">
                            We implement reasonable security measures to protect your data:
                        </p>
                        <ul class="list-disc list-inside text-secondary-600 space-y-2 ml-4">
                            <li>Encryption of data in transit (HTTPS/TLS) and at rest (AES-256)</li>
                            <li>Facial biometric embeddings are encrypted and stored securely</li>
                            <li>Authentication tokens are stored using encrypted local storage on mobile devices</li>
                            <li>Role-based access control</li>
                            <li>Regular data backups</li>
                            <li>Regular security testing and monitoring</li>
                        </ul>
                        <p class="text-secondary-600 mt-4">
                            While we strive to protect your data, no method of electronic transmission or storage is 100% secure. We cannot guarantee absolute security.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-bold text-secondary-900 mb-4">6. Data Retention</h2>
                        <p class="text-secondary-600 mb-4">
                            Your data is retained as long as your account is active or as needed to provide the Service. Specific retention periods:
                        </p>
                        <ul class="list-disc list-inside text-secondary-600 space-y-2 ml-4">
                            <li>Employee data: Duration of active employment + 5 years after termination</li>
                            <li>Payroll data: In accordance with Indonesian tax requirements (minimum 10 years)</li>
                            <li>Attendance data: 3 years</li>
                            <li>Activity logs: 1 year</li>
                        </ul>
                        <p class="text-secondary-600 mt-4">
                            After the retention period ends, data will be securely deleted or anonymized.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-bold text-secondary-900 mb-4">7. Account Deletion</h2>
                        <p class="text-secondary-600 mb-4">
                            You have the right to delete your account at any time:
                        </p>
                        <ul class="list-disc list-inside text-secondary-600 space-y-2 ml-4">
                            <li><strong>Mobile App:</strong> Go to Profile > Delete Account (Hapus Akun).</li>
                            <li><strong>Web Platform:</strong> Go to Settings > Delete Account.</li>
                        </ul>
                        <p class="text-secondary-600 mt-4">
                            Upon requesting account deletion, your account will be deactivated immediately. Your data will be retained for a <strong>30-day recovery period</strong>, during which you may contact us to restore your account. After 30 days, all associated data will be permanently and irreversibly deleted from our servers.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-bold text-secondary-900 mb-4">8. Your Rights</h2>
                        <p class="text-secondary-600 mb-4">
                            In accordance with applicable data protection regulations, you have the right to:
                        </p>
                        <ul class="list-disc list-inside text-secondary-600 space-y-2 ml-4">
                            <li><strong>Access:</strong> Request a copy of your personal data.</li>
                            <li><strong>Correction:</strong> Update inaccurate or incomplete data.</li>
                            <li><strong>Deletion:</strong> Request deletion of your data (subject to certain limitations).</li>
                            <li><strong>Restriction:</strong> Restrict the processing of your data.</li>
                            <li><strong>Portability:</strong> Receive your data in a machine-readable format.</li>
                            <li><strong>Object:</strong> Object to processing for marketing purposes.</li>
                            <li><strong>Withdraw Consent:</strong> Revoke device permissions (camera, location) through your device settings at any time. Note that revoking certain permissions may limit app functionality.</li>
                        </ul>
                        <p class="text-secondary-600 mt-4">
                            To exercise these rights, please contact us at hey@jagoflutter.com.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-bold text-secondary-900 mb-4">9. Mobile App Permissions</h2>
                        <p class="text-secondary-600 mb-4">
                            The GajiPro mobile application requires the following device permissions:
                        </p>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-secondary-600 text-sm">
                                <thead>
                                    <tr class="border-b border-secondary-200">
                                        <th class="text-left py-3 pr-4 font-semibold text-secondary-800">Permission</th>
                                        <th class="text-left py-3 font-semibold text-secondary-800">Purpose</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="border-b border-secondary-100">
                                        <td class="py-3 pr-4 font-medium">Camera</td>
                                        <td class="py-3">Face recognition for attendance verification and face enrollment</td>
                                    </tr>
                                    <tr class="border-b border-secondary-100">
                                        <td class="py-3 pr-4 font-medium">Location (GPS)</td>
                                        <td class="py-3">Validate employee presence at office location during attendance check-in/out</td>
                                    </tr>
                                    <tr class="border-b border-secondary-100">
                                        <td class="py-3 pr-4 font-medium">Internet</td>
                                        <td class="py-3">Communicate with backend servers for data synchronization</td>
                                    </tr>
                                    <tr class="border-b border-secondary-100">
                                        <td class="py-3 pr-4 font-medium">Media / Gallery</td>
                                        <td class="py-3">Profile image selection</td>
                                    </tr>
                                    <tr>
                                        <td class="py-3 pr-4 font-medium">Push Notifications</td>
                                        <td class="py-3">Receive work-related notifications and announcements via Firebase Cloud Messaging</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-bold text-secondary-900 mb-4">10. Cookies and Tracking Technologies</h2>
                        <p class="text-secondary-600 mb-4">
                            On our web platform, we use cookies and similar technologies for:
                        </p>
                        <ul class="list-disc list-inside text-secondary-600 space-y-2 ml-4">
                            <li><strong>Essential Cookies:</strong> Required for basic site functionality.</li>
                            <li><strong>Preference Cookies:</strong> Remember your settings.</li>
                            <li><strong>Analytics Cookies:</strong> Understand how the Service is used.</li>
                        </ul>
                        <p class="text-secondary-600 mt-4">
                            You can control cookies through your browser settings. Disabling certain cookies may affect the functionality of the Service.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-bold text-secondary-900 mb-4">11. Third-Party Services</h2>
                        <p class="text-secondary-600 mb-4">
                            Our Service may contain links to third-party sites or use the following third-party services:
                        </p>
                        <ul class="list-disc list-inside text-secondary-600 space-y-2 ml-4">
                            <li><strong>Firebase (Google):</strong> Push notification delivery via Firebase Cloud Messaging. See <a href="https://policies.google.com/privacy" class="text-primary-600 hover:text-primary-700 underline" target="_blank" rel="noopener">Google's Privacy Policy</a>.</li>
                            <li><strong>Google ML Kit:</strong> On-device face detection processing. Data is processed locally on the device.</li>
                            <li><strong>Payment Gateway:</strong> For payment processing.</li>
                            <li><strong>Cloud Storage Provider:</strong> For secure data storage.</li>
                        </ul>
                        <p class="text-secondary-600 mt-4">
                            We are not responsible for the privacy practices of third parties. We encourage you to read their respective privacy policies.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-bold text-secondary-900 mb-4">12. Children's Privacy</h2>
                        <p class="text-secondary-600">
                            GajiPro is a workplace application and is not intended for use by anyone under the age of 17. We do not knowingly collect personal information from children. If you become aware that a child has provided personal data to us, please contact us.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-bold text-secondary-900 mb-4">13. Changes to This Policy</h2>
                        <p class="text-secondary-600">
                            We may update this Privacy Policy from time to time. Changes will be published on this page with an updated "Last updated" date. We will notify you via email or in-app notification for significant changes.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-xl font-bold text-secondary-900 mb-4">14. Contact Us</h2>
                        <p class="text-secondary-600 mb-4">
                            If you have any questions about this Privacy Policy or wish to exercise your rights, please contact:
                        </p>
                        <div class="bg-secondary-50 rounded-xl p-6">
                            <p class="text-secondary-600 mb-2"><strong>JagoFlutter</strong></p>
                            <p class="text-secondary-600"><strong>Email:</strong> hey@jagoflutter.com</p>
                            <p class="text-secondary-600"><strong>Website:</strong> <a href="https://jagoflutter.com" class="text-primary-600 hover:text-primary-700 underline" target="_blank" rel="noopener">https://jagoflutter.com</a></p>
                        </div>
                    </section>

                </div>
            </div>

            {{-- Back Link --}}
            <div class="mt-8 text-center">
                <a href="{{ url('/') }}" class="text-primary-600 hover:text-primary-700 font-medium inline-flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Back to Home
                </a>
            </div>
        </div>
    </div>

    @include('components.footer')
@endsection
