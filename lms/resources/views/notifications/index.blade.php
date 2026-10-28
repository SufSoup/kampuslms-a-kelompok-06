<x-layout title="Notifikasi — KampusLMS">
    <div style="max-width: 900px; margin: 0 auto; padding: 24px 16px;">
        <div style="margin-bottom: 24px;">
            <h1 style="font-size: 32px; margin: 0 0 6px;">Notifikasi</h1>
            <p style="color: #6E6E73; margin: 0;">Informasi terbaru untuk akun Anda.</p>
        </div>

        @if ($notifications->isEmpty())
            <div style="padding: 32px; background: #F5F5F7; border: 1px dashed #D1D1D6; border-radius: 12px; text-align: center; color: #6E6E73;">
                Belum ada notifikasi.
            </div>
        @else
            <div style="display: flex; flex-direction: column; gap: 14px;">
                @foreach ($notifications as $notification)
                    @php
                        $payload = is_array($notification->data) ? $notification->data : [];
                        $title = $payload['title'] ?? $payload['assignment_title'] ?? 'Notifikasi baru';
                        $message = $payload['message'] ?? null;
                        $courseName = $payload['course_name'] ?? null;
                        $dueAt = $payload['due_at'] ?? null;
                    @endphp

                    <article style="background: #FFFFFF; border: 1px solid #E5E5EA; border-radius: 12px; padding: 20px; display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap;">
                        <div style="min-width: 0; flex: 1;">
                            <div style="margin-bottom: 8px;">
                                @if ($notification->read_at)
                                    <span style="display: inline-block; padding: 4px 9px; border-radius: 999px; background: #E5E5EA; color: #424245; font-size: 12px; font-weight: 600;">
                                        Sudah Dibaca
                                    </span>
                                @else
                                    <span style="display: inline-block; padding: 4px 9px; border-radius: 999px; background: #DBEAFE; color: #1D4ED8; font-size: 12px; font-weight: 600;">
                                        Belum Dibaca
                                    </span>
                                @endif
                            </div>

                            <h2 style="font-size: 18px; margin: 0 0 6px;">
                                {{ is_string($title) && trim($title) !== '' ? $title : 'Notifikasi baru' }}
                            </h2>

                            @if (is_string($message) && trim($message) !== '')
                                <p style="margin: 0 0 6px; color: #424245;">{{ $message }}</p>
                            @endif

                            @if (is_string($courseName) && trim($courseName) !== '')
                                <p style="margin: 0 0 6px; color: #6E6E73;">Mata kuliah: {{ $courseName }}</p>
                            @endif

                            @if (is_string($dueAt) && trim($dueAt) !== '')
                                <p style="margin: 0 0 6px; color: #6E6E73;">Batas waktu: {{ $dueAt }}</p>
                            @endif

                            <time datetime="{{ $notification->created_at?->toIso8601String() }}" style="display: block; margin-top: 10px; color: #8E8E93; font-size: 13px;">
                                {{ $notification->created_at?->format('d M Y H:i') ?? 'Waktu tidak tersedia' }}
                            </time>
                        </div>

                        @unless ($notification->read_at)
                            <form method="POST" action="{{ route('notifications.read', ['id' => $notification->id]) }}" style="margin: 0;">
                                @csrf
                                <button type="submit" style="background: transparent; color: #0071E3; border: 1px solid #0071E3; padding: 8px 14px; border-radius: 999px; cursor: pointer; white-space: nowrap;">
                                    Tandai Sudah Dibaca
                                </button>
                            </form>
                        @endunless
                    </article>
                @endforeach
            </div>

            <div style="margin-top: 24px;">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</x-layout>
