<x-app-layout>

@php
    $role = auth()->user()->role;

    $notificationUrl = match ($role) {
        'admin' => url('/notification'),
        'ketua' => url('/notification'),
        'anggota' => url('/notification'),
        'kepala' => url('/notification'),
        default => url('/notification'),
    };
@endphp

<div class="space-y-6">

    <!-- HEADER -->
    <section class="overflow-hidden rounded-3xl bg-slate-950 text-white shadow-sm">
        <div class="relative px-6 py-7">
            <div class="absolute inset-y-0 right-0 hidden w-1/2 bg-gradient-to-l from-blue-600/20 to-transparent lg:block"></div>

            <div class="relative flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full bg-blue-500/15 px-3 py-1 text-xs font-bold text-blue-200 ring-1 ring-blue-400/20">
                        <span class="h-2 w-2 rounded-full {{ $unreadCount > 0 ? 'bg-red-400' : 'bg-emerald-400' }}"></span>
                        Pusat Informasi
                    </div>

                    <h1 class="mt-3 text-2xl font-extrabold tracking-tight sm:text-3xl">
                        Notifikasi
                    </h1>

                    <p class="mt-2 max-w-3xl text-sm leading-relaxed text-slate-300">
                        Lihat pembaruan penting seperti IKI yang menunggu review, IKI disetujui, IKI perlu revisi, atau informasi project.
                    </p>
                </div>

                <div class="flex flex-col gap-2 sm:flex-row">
                    @if($unreadCount > 0)
                        <form method="POST" action="{{ route('notification.readAll') }}">
                            @csrf

                            <button
                                type="submit"
                                class="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-bold text-white transition hover:bg-emerald-500">
                                <i data-lucide="check-check" class="h-4 w-4"></i>
                                Tandai Semua Dibaca
                            </button>
                        </form>
                    @endif

                    <a href="{{ $notificationUrl }}"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-white/10 px-4 py-3 text-sm font-bold text-white ring-1 ring-white/10 transition hover:bg-white/15">
                        <i data-lucide="refresh-cw" class="h-4 w-4"></i>
                        Refresh
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- SUMMARY -->
    <section class="grid gap-4 md:grid-cols-3">

        <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-semibold text-slate-500">
                        Total Notifikasi
                    </div>

                    <div class="mt-3 text-3xl font-extrabold text-slate-950">
                        {{ $totalCount }}
                    </div>
                </div>

                <div class="rounded-2xl bg-blue-50 p-3 text-blue-600">
                    <i data-lucide="bell" class="h-6 w-6"></i>
                </div>
            </div>
        </div>

        <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-semibold text-slate-500">
                        Belum Dibaca
                    </div>

                    <div class="mt-3 text-3xl font-extrabold {{ $unreadCount > 0 ? 'text-red-600' : 'text-slate-950' }}">
                        {{ $unreadCount }}
                    </div>
                </div>

                <div class="rounded-2xl bg-red-50 p-3 text-red-600">
                    <i data-lucide="bell-ring" class="h-6 w-6"></i>
                </div>
            </div>
        </div>

        <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-semibold text-slate-500">
                        Sudah Dibaca
                    </div>

                    <div class="mt-3 text-3xl font-extrabold text-emerald-600">
                        {{ max($totalCount - $unreadCount, 0) }}
                    </div>
                </div>

                <div class="rounded-2xl bg-emerald-50 p-3 text-emerald-600">
                    <i data-lucide="check-circle-2" class="h-6 w-6"></i>
                </div>
            </div>
        </div>

    </section>

    <!-- FILTER -->
    <section class="rounded-3xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
        <div class="flex flex-wrap gap-2">

            <a href="{{ route('notification.index', ['filter' => 'all']) }}"
                class="rounded-2xl px-4 py-2 text-sm font-bold transition
                    {{ $filter === 'all' ? 'bg-slate-950 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                Semua
            </a>

            <a href="{{ route('notification.index', ['filter' => 'unread']) }}"
                class="rounded-2xl px-4 py-2 text-sm font-bold transition
                    {{ $filter === 'unread' ? 'bg-red-600 text-white' : 'bg-red-50 text-red-700 hover:bg-red-100' }}">
                Belum Dibaca
                @if($unreadCount > 0)
                    <span class="ml-1 rounded-full bg-white/20 px-2 py-0.5 text-xs">
                        {{ $unreadCount }}
                    </span>
                @endif
            </a>

            <a href="{{ route('notification.index', ['filter' => 'read']) }}"
                class="rounded-2xl px-4 py-2 text-sm font-bold transition
                    {{ $filter === 'read' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}">
                Sudah Dibaca
            </a>

        </div>
    </section>

    <!-- NOTIFICATION LIST -->
    <section class="rounded-3xl bg-white shadow-sm ring-1 ring-slate-200 overflow-hidden">

        <div class="border-b border-slate-200 px-6 py-4">
            <h2 class="text-base font-extrabold text-slate-950">
                Daftar Notifikasi
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Klik notifikasi untuk menandai sebagai dibaca dan membuka halaman terkait.
            </p>
        </div>

        <div class="divide-y divide-slate-100">
            @forelse($notifications as $notification)

                <div class="relative {{ !$notification->is_read ? 'bg-blue-50/40' : 'bg-white' }}">
                    <form method="POST" action="{{ route('notification.read', $notification->id) }}">
                        @csrf

                        <button type="submit"
                            class="w-full text-left px-6 py-5 transition hover:bg-slate-50">
                            <div class="flex gap-4">

                                <div class="relative shrink-0">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl border {{ $notification->color_class }}">
                                        <i data-lucide="{{ $notification->icon }}" class="h-5 w-5"></i>
                                    </div>

                                    @if(!$notification->is_read)
                                        <span class="absolute -right-1 -top-1 h-3 w-3 rounded-full bg-red-500 ring-2 ring-white"></span>
                                    @endif
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <h3 class="font-extrabold text-slate-950">
                                                    {{ $notification->title }}
                                                </h3>

                                                <span class="rounded-full border px-2.5 py-1 text-xs font-bold {{ $notification->color_class }}">
                                                    {{ $notification->type_label }}
                                                </span>

                                                @if(!$notification->is_read)
                                                    <span class="rounded-full bg-red-500 px-2.5 py-1 text-xs font-bold text-white">
                                                        Baru
                                                    </span>
                                                @endif
                                            </div>

                                            <p class="mt-2 text-sm leading-relaxed text-slate-600">
                                                {{ $notification->message }}
                                            </p>
                                        </div>

                                        <div class="shrink-0 text-xs font-semibold text-slate-400">
                                            {{ $notification->time_label }}
                                        </div>
                                    </div>

                                    <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-slate-400">
                                        @if($notification->is_read)
                                            <span class="inline-flex items-center gap-1">
                                                <i data-lucide="check-circle-2" class="h-3.5 w-3.5"></i>
                                                Dibaca
                                                @if($notification->read_at)
                                                    {{ $notification->read_at->diffForHumans() }}
                                                @endif
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 font-bold text-blue-600">
                                                <i data-lucide="mouse-pointer-click" class="h-3.5 w-3.5"></i>
                                                Klik untuk membuka
                                            </span>
                                        @endif

                                        @if($notification->url)
                                            <span class="inline-flex items-center gap-1">
                                                <i data-lucide="link" class="h-3.5 w-3.5"></i>
                                                Ada halaman terkait
                                            </span>
                                        @endif
                                    </div>
                                </div>

                            </div>
                        </button>
                    </form>
                </div>

            @empty
                <div class="px-6 py-14 text-center">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-slate-100 text-slate-400">
                        <i data-lucide="bell-off" class="h-7 w-7"></i>
                    </div>

                    <h3 class="mt-4 text-lg font-extrabold text-slate-900">
                        Belum ada notifikasi
                    </h3>

                    <p class="mt-2 text-sm text-slate-500">
                        Pembaruan penting seperti review IKI atau status approval akan muncul di sini.
                    </p>
                </div>
            @endforelse
        </div>

        @if($notifications->hasPages())
            <div class="border-t border-slate-200 px-6 py-4">
                {{ $notifications->links() }}
            </div>
        @endif

    </section>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) {
        window.lucide.createIcons();
    }
});
</script>

</x-app-layout>