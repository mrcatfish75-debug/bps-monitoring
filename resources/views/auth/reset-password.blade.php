<x-guest-layout>
    <div class="min-h-screen flex items-center justify-center bg-slate-100 px-4 py-10">
        <div class="w-full max-w-md overflow-hidden rounded-3xl bg-white shadow-xl ring-1 ring-slate-200">

            <div class="bg-slate-950 px-8 py-7 text-white">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-600 shadow-lg shadow-emerald-950/30">
                    <i data-lucide="lock-keyhole" class="h-7 w-7"></i>
                </div>

                <h1 class="mt-5 text-center text-2xl font-extrabold tracking-tight">
                    Buat Password Baru
                </h1>

                <p class="mt-2 text-center text-sm leading-relaxed text-slate-300">
                    Masukkan password baru untuk akun kamu. Setelah ini, login menggunakan password baru tersebut.
                </p>
            </div>

            <div class="px-8 py-7">
                @if(session('error'))
                    <div class="mb-4 rounded-2xl border border-red-100 bg-red-50 p-4 text-sm font-semibold text-red-700">
                        {{ session('error') }}
                    </div>
                @endif

                @if(session('status'))
                    <div class="mb-4 rounded-2xl border border-emerald-100 bg-emerald-50 p-4 text-sm font-semibold text-emerald-700">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
                    @csrf

                    <input type="hidden" name="token" value="{{ $request->route('token') }}">

                    <div>
                        <label for="email" class="mb-2 block text-sm font-bold text-slate-700">
                            Email
                        </label>

                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email', $request->email) }}"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="nama@email.com"
                            class="block w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500">

                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <label for="password" class="mb-2 block text-sm font-bold text-slate-700">
                            Password Baru
                        </label>

                        <input
                            id="password"
                            name="password"
                            type="password"
                            required
                            autocomplete="new-password"
                            placeholder="Masukkan password baru"
                            class="block w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500">

                        <x-input-error :messages="$errors->get('password')" class="mt-2" />

                        <p class="mt-2 text-xs leading-relaxed text-slate-500">
                            Gunakan password yang mudah kamu ingat, tetapi tidak mudah ditebak.
                        </p>
                    </div>

                    <div>
                        <label for="password_confirmation" class="mb-2 block text-sm font-bold text-slate-700">
                            Konfirmasi Password Baru
                        </label>

                        <input
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            required
                            autocomplete="new-password"
                            placeholder="Ulangi password baru"
                            class="block w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500">

                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                    </div>

                    <button
                        type="submit"
                        class="flex w-full items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-extrabold text-white transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                        <i data-lucide="save" class="h-4 w-4"></i>
                        Simpan Password Baru
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <a href="{{ route('login') }}"
                       class="inline-flex items-center gap-2 text-sm font-bold text-blue-600 transition hover:text-blue-700">
                        <i data-lucide="arrow-left" class="h-4 w-4"></i>
                        Kembali ke Login
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) {
                window.lucide.createIcons();
            }
        });
    </script>
</x-guest-layout>