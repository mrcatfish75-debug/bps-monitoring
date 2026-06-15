@php
    $currentUser = auth()->user();

    $notifications = collect();
    $unreadCount = 0;

    if ($currentUser) {
        $notifications = \App\Models\Notification::where('user_id', $currentUser->id)
            ->latest()
            ->limit(8)
            ->get();

        $unreadCount = \App\Models\Notification::where('user_id', $currentUser->id)
            ->where('is_read', false)
            ->count();
    }
@endphp

<div class="relative" data-notification-dropdown>
    <button
        type="button"
        data-notification-toggle
        class="relative w-12 h-12 rounded-2xl border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 hover:text-slate-950 transition flex items-center justify-center"
        title="Notifikasi"
        aria-label="Buka notifikasi">

        <i data-lucide="bell" class="w-5 h-5"></i>

        @if($unreadCount > 0)
            <span class="absolute -top-1 -right-1 min-w-5 h-5 rounded-full bg-red-500 px-1 text-[10px] font-bold text-white flex items-center justify-center ring-2 ring-white">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
    </button>

    <div
        data-notification-panel
        class="hidden absolute right-0 mt-3 w-[360px] max-w-[calc(100vw-2rem)] overflow-hidden rounded-3xl bg-white shadow-2xl ring-1 ring-slate-200 z-[9999]">

        <div class="border-b border-slate-200 px-5 py-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-base font-extrabold text-slate-950">
                        Notifikasi
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        Pembaruan penting untuk akun kamu.
                    </p>
                </div>

                @if($unreadCount > 0)
                    <span class="shrink-0 rounded-full bg-red-50 px-3 py-1 text-xs font-bold text-red-600">
                        {{ $unreadCount }} baru
                    </span>
                @else
                    <span class="shrink-0 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-600">
                        Aman
                    </span>
                @endif
            </div>
        </div>

        <div class="max-h-[420px] overflow-y-auto divide-y divide-slate-100">
            @forelse($notifications as $notification)
                <form method="POST" action="{{ route('notification.read', $notification->id) }}">
                    @csrf

                    <button
                        type="submit"
                        class="w-full text-left px-5 py-4 transition hover:bg-slate-50 {{ !$notification->is_read ? 'bg-blue-50/50' : 'bg-white' }}">

                        <div class="flex gap-3">
                            <div class="relative shrink-0">
                                <div class="flex h-11 w-11 items-center justify-center rounded-2xl border {{ $notification->color_class ?? 'bg-slate-50 text-slate-700 border-slate-100' }}">
                                    <i data-lucide="{{ $notification->icon ?? 'bell' }}" class="h-5 w-5"></i>
                                </div>

                                @if(!$notification->is_read)
                                    <span class="absolute -right-0.5 -top-0.5 h-3 w-3 rounded-full bg-red-500 ring-2 ring-white"></span>
                                @endif
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <p class="truncate text-sm font-extrabold text-slate-950">
                                                {{ $notification->title }}
                                            </p>

                                            @if(!$notification->is_read)
                                                <span class="shrink-0 rounded-full bg-red-500 px-2 py-0.5 text-[10px] font-bold text-white">
                                                    Baru
                                                </span>
                                            @endif
                                        </div>

                                        <p class="mt-1 line-clamp-2 text-xs leading-relaxed text-slate-600">
                                            {{ $notification->message }}
                                        </p>
                                    </div>
                                </div>

                                <div class="mt-2 flex flex-wrap items-center gap-2 text-[11px] text-slate-400">
                                    <span class="inline-flex items-center gap-1">
                                        <i data-lucide="clock-3" class="h-3 w-3"></i>
                                        {{ $notification->time_label ?? $notification->created_at?->diffForHumans() }}
                                    </span>

                                    @if($notification->url)
                                        <span class="inline-flex items-center gap-1 text-blue-600 font-semibold">
                                            <i data-lucide="mouse-pointer-click" class="h-3 w-3"></i>
                                            Buka
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </button>
                </form>
            @empty
                <div class="px-5 py-10 text-center">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-3xl bg-slate-100 text-slate-400">
                        <i data-lucide="bell-off" class="h-6 w-6"></i>
                    </div>

                    <h4 class="mt-4 text-sm font-extrabold text-slate-900">
                        Belum ada notifikasi
                    </h4>

                    <p class="mt-1 text-xs leading-relaxed text-slate-500">
                        Info seperti IKI masuk, IKI disetujui, atau IKI perlu revisi akan tampil di sini.
                    </p>
                </div>
            @endforelse
        </div>

        <div class="border-t border-slate-200 bg-slate-50 px-5 py-3">
            <div class="flex items-center justify-between gap-3">
                @if($unreadCount > 0)
                    <form method="POST" action="{{ route('notification.readAll') }}">
                        @csrf

                        <button
                            type="submit"
                            class="text-xs font-bold text-emerald-600 hover:text-emerald-700">
                            Tandai semua dibaca
                        </button>
                    </form>
                @else
                    <span class="text-xs text-slate-400">
                        Semua sudah dibaca
                    </span>
                @endif

                <a href="{{ route('notification.index') }}"
                    class="text-xs font-bold text-blue-600 hover:text-blue-700">
                    Lihat semua
                </a>
            </div>
        </div>
    </div>
</div>

@once
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-notification-dropdown]').forEach((dropdown) => {
                const toggle = dropdown.querySelector('[data-notification-toggle]');
                const panel = dropdown.querySelector('[data-notification-panel]');

                if (!toggle || !panel) {
                    return;
                }

                toggle.addEventListener('click', (event) => {
                    event.stopPropagation();

                    document.querySelectorAll('[data-notification-panel]').forEach((otherPanel) => {
                        if (otherPanel !== panel) {
                            otherPanel.classList.add('hidden');
                        }
                    });

                    panel.classList.toggle('hidden');

                    if (window.lucide) {
                        window.lucide.createIcons();
                    }
                });

                panel.addEventListener('click', (event) => {
                    event.stopPropagation();
                });
            });

            document.addEventListener('click', () => {
                document.querySelectorAll('[data-notification-panel]').forEach((panel) => {
                    panel.classList.add('hidden');
                });
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    document.querySelectorAll('[data-notification-panel]').forEach((panel) => {
                        panel.classList.add('hidden');
                    });
                }
            });

            if (window.lucide) {
                window.lucide.createIcons();
            }
        });
    </script>
@endonce