<x-guest-layout>
    <div class="min-h-screen flex items-center justify-center bg-slate-100 px-4 py-10">
        <div class="w-full max-w-md overflow-hidden rounded-3xl bg-white shadow-xl ring-1 ring-slate-200">

            <div class="bg-slate-950 px-8 py-7 text-white">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-600 shadow-lg shadow-blue-950/40">
                    <i data-lucide="key-round" class="h-7 w-7"></i>
                </div>

                <h1 class="mt-5 text-center text-2xl font-extrabold tracking-tight">
                    Lupa Password?
                </h1>

                <p class="mt-2 text-center text-sm leading-relaxed text-slate-300">
                    Masukkan email akun kamu. Sistem akan mengirim link untuk membuat password baru.
                </p>
            </div>

            <div class="px-8 py-7">
                <div class="mb-5 rounded-2xl border border-blue-100 bg-blue-50 p-4 text-sm leading-relaxed text-blue-800">
                    <div class="flex gap-3">
                        <div class="mt-0.5 shrink-0">
                            <i data-lucide="info" class="h-5 w-5"></i>
                        </div>

                        <div>
                            Link reset hanya berlaku sementara. Setelah berhasil reset, gunakan password baru untuk login.
                        </div>
                    </div>
                </div>

                <x-auth-session-status class="mb-4" :status="session('status')" />

                @if(session('success'))
                    <div class="mb-4 rounded-2xl border border-emerald-100 bg-emerald-50 p-4 text-sm font-semibold text-emerald-700">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-4 rounded-2xl border border-red-100 bg-red-50 p-4 text-sm font-semibold text-red-700">
                        {{ session('error') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="email" class="mb-2 block text-sm font-bold text-slate-700">
                            Email
                        </label>

                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            autocomplete="email"
                            placeholder="nama@email.com"
                            class="block w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-500 focus:ring-blue-500">

                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <button
                        type="submit"
                        class="flex w-full items-center justify-center gap-2 rounded-2xl bg-blue-600 px-4 py-3 text-sm font-extrabold text-white transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        <i data-lucide="send" class="h-4 w-4"></i>
                        Kirim Link Reset Password
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