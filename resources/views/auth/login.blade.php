<x-guest-layout>
    <div class="min-h-screen bg-slate-100 px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto flex min-h-[calc(100vh-3rem)] w-full max-w-6xl items-center justify-center">

            <div class="grid w-full overflow-hidden rounded-[2rem] bg-white shadow-2xl ring-1 ring-slate-200 lg:grid-cols-2">

                <!-- LEFT BRAND PANEL -->
                <div class="hidden bg-slate-950 p-10 text-white lg:flex lg:flex-col lg:justify-between">
                    <div>
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-600 shadow-lg shadow-blue-600/30">
                            <span class="text-xl font-black tracking-tight">
                                BPS
                            </span>
                        </div>

                        <div class="mt-10">
                            <p class="text-sm font-semibold uppercase tracking-[0.3em] text-blue-300">
                                Monitoring Kinerja
                            </p>

                            <h1 class="mt-4 max-w-md text-4xl font-black leading-tight tracking-tight">
                                Pantau IKU, RK, Project, dan progres kerja dalam satu sistem.
                            </h1>

                            <p class="mt-5 max-w-md text-sm leading-7 text-slate-300">
                                Sistem internal untuk membantu Admin, Kepala, Ketua Tim, dan Anggota memantau capaian kinerja secara lebih rapi dan terukur.
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <div class="font-bold text-white">IKU</div>
                            <div class="mt-1 text-xs text-slate-400">Pantau target utama.</div>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <div class="font-bold text-white">RK Anggota</div>
                            <div class="mt-1 text-xs text-slate-400">Review berbasis bukti.</div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT LOGIN PANEL -->
                <div class="flex items-center justify-center bg-white px-5 py-8 sm:px-8 lg:px-12">
                    <div class="w-full max-w-md">

                        <!-- MOBILE BRAND -->
                        <div class="mb-7 text-center lg:hidden">
                            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-950 shadow-lg">
                                <span class="text-xl font-black text-white">
                                    BPS
                                </span>
                            </div>

                            <h1 class="text-2xl font-black tracking-tight text-slate-950">
                                Sistem Monitoring Kinerja
                            </h1>

                            <p class="mt-2 text-sm leading-6 text-slate-500">
                                Masuk untuk memantau IKU, RK Ketua, Project, RK Anggota, dan Daily Task.
                            </p>
                        </div>

                        <!-- DESKTOP FORM HEADER -->
                        <div class="hidden lg:block">
                            <div class="mb-8">
                                <h2 class="text-3xl font-black tracking-tight text-slate-950">
                                    Masuk ke Akun
                                </h2>

                                <p class="mt-2 text-sm leading-6 text-slate-500">
                                    Gunakan email dan password yang diberikan oleh Admin.
                                </p>
                            </div>
                        </div>

                        <!-- SESSION STATUS -->
                        @if (session('status'))
                            <div class="mb-5 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                                {{ session('status') }}
                            </div>
                        @endif

                        @if (session('success'))
                            <div class="mb-5 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                                {{ session('success') }}
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="mb-5 rounded-2xl border border-red-100 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                                {{ session('error') }}
                            </div>
                        @endif

                        <form method="POST"
                            action="{{ route('login') }}"
                            id="loginForm"
                            class="space-y-5">
                            @csrf

                            <!-- EMAIL -->
                            <div>
                                <label for="email" class="mb-1.5 block text-sm font-bold text-slate-700">
                                    Email
                                </label>

                                <div class="relative">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M21.75 6.75v10.5A2.25 2.25 0 0 1 19.5 19.5h-15A2.25 2.25 0 0 1 2.25 17.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15A2.25 2.25 0 0 0 2.25 6.75m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0l-7.5-4.615A2.25 2.25 0 0 1 2.25 6.993V6.75" />
                                        </svg>
                                    </div>

                                    <input id="email"
                                        type="email"
                                        name="email"
                                        value="{{ old('email') }}"
                                        required
                                        autofocus
                                        autocomplete="username"
                                        placeholder="nama@bps.go.id"
                                        class="block w-full rounded-2xl border-slate-200 bg-slate-50 py-3 pl-10 pr-4 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-slate-950 focus:bg-white focus:ring-slate-950">
                                </div>

                                @if($errors->get('email'))
                                    <div class="mt-2 text-sm font-medium text-red-600">
                                        @foreach($errors->get('email') as $message)
                                            <p>{{ $message }}</p>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <!-- PASSWORD -->
                            <div>
                                <div class="mb-1.5 flex items-center justify-between">
                                    <label for="password" class="block text-sm font-bold text-slate-700">
                                        Password
                                    </label>

                                    @if (Route::has('password.request'))
                                        <a href="{{ route('password.request') }}"
                                            class="text-xs font-bold text-slate-500 hover:text-slate-950">
                                            Lupa password?
                                        </a>
                                    @endif
                                </div>

                                <div class="relative">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M16.5 10.5V6.75a4.5 4.5 0 0 0-9 0v3.75m-.75 11.25h10.5A2.25 2.25 0 0 0 19.5 19.5v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75A2.25 2.25 0 0 0 6.75 21.75Z" />
                                        </svg>
                                    </div>

                                    <input id="password"
                                        type="password"
                                        name="password"
                                        required
                                        autocomplete="current-password"
                                        placeholder="Masukkan password"
                                        class="block w-full rounded-2xl border-slate-200 bg-slate-50 py-3 pl-10 pr-12 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-slate-950 focus:bg-white focus:ring-slate-950">

                                    <button type="button"
                                        onclick="togglePasswordVisibility()"
                                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-700"
                                        aria-label="Tampilkan password">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                        </svg>
                                    </button>
                                </div>

                                @if($errors->get('password'))
                                    <div class="mt-2 text-sm font-medium text-red-600">
                                        @foreach($errors->get('password') as $message)
                                            <p>{{ $message }}</p>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <!-- REMEMBER -->
                            <div class="flex items-center justify-between">
                                <label for="remember_me" class="inline-flex items-center">
                                    <input id="remember_me"
                                        type="checkbox"
                                        name="remember"
                                        class="rounded border-slate-300 text-slate-950 shadow-sm focus:ring-slate-950">

                                    <span class="ml-2 text-sm text-slate-600">
                                        Ingat saya
                                    </span>
                                </label>

                                <span class="text-xs font-medium text-slate-400">
                                    Akses internal
                                </span>
                            </div>

                            <!-- CAPTCHA -->
                            <div>
                                <div class="flex justify-center sm:justify-start">
                                    <div class="g-recaptcha"
                                        data-sitekey="{{ config('services.recaptcha.site_key') }}">
                                    </div>
                                </div>

                                @if($errors->get('g-recaptcha-response'))
                                    <div class="mt-2 text-sm font-medium text-red-600">
                                        @foreach($errors->get('g-recaptcha-response') as $message)
                                            <p>{{ $message }}</p>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <!-- SUBMIT -->
                            <button type="submit"
                                id="loginButton"
                                class="inline-flex w-full items-center justify-center rounded-2xl bg-slate-950 px-5 py-3 text-sm font-black text-white shadow-lg shadow-slate-900/20 transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-950 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-70">
                                <span id="loginButtonText">
                                    Masuk
                                </span>

                                <span id="loginButtonLoading"
                                    class="hidden items-center gap-2">
                                    <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none"
                                        viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                            stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path>
                                    </svg>
                                    Memproses...
                                </span>
                            </button>
                        </form>

                        <div class="mt-7 text-center">
                            <p class="text-xs text-slate-500">
                                Tidak punya akun? Hubungi Admin untuk dibuatkan akun.
                            </p>

                            <p class="mt-2 text-xs text-slate-400">
                                © {{ date('Y') }} Sistem Monitoring Kinerja BPS
                            </p>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://www.google.com/recaptcha/api.js" async defer></script>

    <script>
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');

            if (!passwordInput) {
                return;
            }

            passwordInput.type = passwordInput.type === 'password' ? 'text' : 'password';
        }

        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('loginForm');
            const button = document.getElementById('loginButton');
            const buttonText = document.getElementById('loginButtonText');
            const buttonLoading = document.getElementById('loginButtonLoading');

            if (!form || !button || !buttonText || !buttonLoading) {
                return;
            }

            form.addEventListener('submit', function () {
                button.disabled = true;
                buttonText.classList.add('hidden');
                buttonLoading.classList.remove('hidden');
                buttonLoading.classList.add('inline-flex');
            });
        });
    </script>
</x-guest-layout>