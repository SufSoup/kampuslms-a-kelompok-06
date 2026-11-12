<x-layout title="Notifikasi — KampusLMS">
    @php
        $unreadCount = $notifications->getCollection()->whereNull('read_at')->count();
    @endphp

    <div class="container-medium" style="margin: 0 auto;">
        <div class="page-header">
            <h1 class="page-title">Notifikasi</h1>
            <p class="page-desc">
                Informasi terbaru untuk akun Anda
                @if ($unreadCount > 0)
                    &mdash; {{ $unreadCount }} belum dibaca
                @endif
            </p>
        </div>

        @if ($notifications->isEmpty())
            <div class="empty-state">
                <x-icon name="bell" class="icon" />
                <span>Belum ada notifikasi.</span>
            </div>
        @else
            <div class="item-list">
                @foreach ($notifications as $notification)
                    @php
                        $payload = is_array($notification->data) ? $notification->data : [];
                        $title = $payload['title'] ?? $payload['assignment_title'] ?? 'Notifikasi baru';
                        $message = $payload['message'] ?? null;
                        $courseName = $payload['course_name'] ?? null;
                        $dueAt = $payload['due_at'] ?? null;
                    @endphp

                    <article class="item-card {{ $notification->read_at ? '' : 'is-unread' }}">
                        <div class="item-main">
                            <div class="item-eyebrow">
                                @if ($notification->read_at)
                                    <span class="badge badge-neutral">Sudah Dibaca</span>
                                @else
                                    <span class="badge badge-primary">Belum Dibaca</span>
                                @endif
                            </div>

                            <h2 class="item-title">
                                {{ is_string($title) && trim($title) !== '' ? $title : 'Notifikasi baru' }}
                            </h2>

                            @if (is_string($message) && trim($message) !== '')
                                <p class="item-desc">{{ $message }}</p>
                            @endif

                            <div class="item-meta">
                                @if (is_string($courseName) && trim($courseName) !== '')
                                    <span><x-icon name="book" class="icon-sm" /> {{ $courseName }}</span>
                                @endif
                                @if (is_string($dueAt) && trim($dueAt) !== '')
                                    <span><x-icon name="clock" class="icon-sm" /> Batas waktu: {{ $dueAt }}</span>
                                @endif
                            </div>

                            <time datetime="{{ $notification->created_at?->toIso8601String() }}" class="text-muted" style="display: block; margin-top: 8px; font-size: 13px;">
                                {{ $notification->created_at?->format('d M Y H:i') ?? 'Waktu tidak tersedia' }}
                            </time>
                        </div>

                        @unless ($notification->read_at)
                            <div class="item-actions">
                                <form method="POST" action="{{ route('notifications.read', ['id' => $notification->id]) }}" style="margin: 0;">
                                    @csrf
                                    <button type="submit" class="btn btn-secondary btn-sm">
                                        <x-icon name="check" class="icon-sm" /> Tandai Sudah Dibaca
                                    </button>
                                </form>
                            </div>
                        @endunless
                    </article>
                @endforeach
            </div>

            <div class="pagination-wrapper">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</x-layout>
