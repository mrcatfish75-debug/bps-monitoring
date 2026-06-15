<x-guest-layout>
    <div class="min-h-screen flex items-center justify-center bg-slate-100 px-4 py-8">
        <div class="w-full max-w-md">

            <!-- HEADER -->
            <div class="mb-6 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-950 shadow-lg">
                    <span class="text-xl font-black text-white">
                        BPS
                    </span>
                </div>

                <h1 class="text-2xl font-black tracking-tight text-slate-950">
                    Ganti Password
                </h1>

                <p class="mt-2 text-sm leading-6 text-slate-500">
                    Kamu sedang menggunakan password sementara. Demi keamanan, buat password baru sebelum melanjutkan ke sistem.
                </p>
            </div>

            <!-- CARD -->
            <div class="rounded-3xl bg-white p-8 shadow-xl ring-1 ring-slate-200">

                @if(session('error'))
                    <div class="mb-5 rounded-2xl border border-red-100 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                        {{ session('error') }}
                    </div>
                @endif

                @if(session('success'))
                    <div class="mb-5 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                        {{ session('success') }}
                    </div>
                @endif

                <form method="POST"
                    action="{{ route('password.force-change.update') }}"
                    id="forcePasswordForm"
                    class="space-y-5">
                    @csrf
                    @method('PUT')

                    <!-- PASSWORD BARU -->
                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-bold text-slate-700">
                            Password Baru
                        </label>

                        <div class="relative">
                            <input id="password"
                                type="password"
                                name="password"
                                required
                                autocomplete="new-password"
                                placeholder="Minimal 10 karakter, huruf besar, angka, simbol"
                                class="block w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 pr-12 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-slate-950 focus:bg-white focus:ring-slate-950">

                            <button type="button"
                                onclick="togglePassword('password')"
                                class="absolute inset-y-0 right-0 flex items-center pr-4 text-xs font-bold text-slate-400 hover:text-slate-700">
                                Lihat
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

                    <!-- KONFIRMASI PASSWORD -->
                    <div>
                        <label for="password_confirmation" class="mb-1.5 block text-sm font-bold text-slate-700">
                            Konfirmasi Password Baru
                        </label>

                        <div class="relative">
                            <input id="password_confirmation"
                                type="password"
                                name="password_confirmation"
                                required
                                autocomplete="new-password"
                                placeholder="Ulangi password baru"
                                class="block w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 pr-12 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-slate-950 focus:bg-white focus:ring-slate-950">

                            <button type="button"
                                onclick="togglePassword('password_confirmation')"
                                class="absolute inset-y-0 right-0 flex items-center pr-4 text-xs font-bold text-slate-400 hover:text-slate-700">
                                Lihat
                            </button>
                        </div>
                    </div>

                    <!-- INFO -->
                    <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-700">
                        <div class="font-bold mb-1">
                            Aturan password:
                        </div>

                        <ul class="list-disc ml-5 space-y-1">
                            <li>Minimal 10 karakter.</li>
                            <li>Mengandung huruf besar dan huruf kecil.</li>
                            <li>Mengandung angka.</li>
                            <li>Mengandung simbol.</li>
                            <li>Tidak boleh sama dengan password sementara.</li>
                        </ul>
                    </div>

                    <!-- SUBMIT -->
                    <button type="submit"
                        id="forcePasswordButton"
                        class="inline-flex w-full items-center justify-center rounded-2xl bg-slate-950 px-5 py-3 text-sm font-black text-white shadow-lg shadow-slate-900/20 transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-70">
                        <span id="forcePasswordButtonText">
                            Simpan Password Baru
                        </span>

                        <span id="forcePasswordButtonLoading"
                            class="hidden items-center gap-2">
                            <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path>
                            </svg>
                            Menyimpan...
                        </span>
                    </button>
                </form>

                <!-- LOGOUT -->
                <form method="POST"
                    action="{{ route('logout') }}"
                    class="mt-4 text-center">
                    @csrf

                    <button type="submit"
                        class="text-sm font-semibold text-slate-500 hover:text-red-600">
                        Logout
                    </button>
                </form>
            </div>

            <p class="mt-6 text-center text-xs text-slate-400">
                © {{ date('Y') }} Sistem Monitoring Kinerja BPS
            </p>
        </div>
    </div>

    <script>
        function togglePassword(id) {
            const input = document.getElementById(id);

            if (!input) {
                return;
            }

            input.type = input.type === 'password' ? 'text' : 'password';
        }

        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('forcePasswordForm');
            const button = document.getElementById('forcePasswordButton');
            const buttonText = document.getElementById('forcePasswordButtonText');
            const buttonLoading = document.getElementById('forcePasswordButtonLoading');

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