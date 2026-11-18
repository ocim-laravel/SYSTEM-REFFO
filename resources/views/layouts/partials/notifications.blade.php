<details class="notifications" style="position:relative">
    <summary class="btn secondary" style="cursor:pointer;list-style:none;display:inline-block">
        Notifikasi
        @if ($unreadCount > 0)
            <span class="notification-badge"
                  style="display:inline-block;background:#b91c1c;color:#fff;border-radius:999px;padding:0 6px;font-size:11px;margin-left:4px">{{ $unreadCount }}</span>
        @endif
    </summary>
    <div class="notification-panel"
         style="position:absolute;right:0;top:calc(100% + 6px);width:300px;max-height:360px;overflow:auto;background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,.12);padding:10px;z-index:20">
        @forelse ($notificationItems as $item)
            <div class="notification-item" style="padding:8px 4px;border-bottom:1px solid #f3f4f6">
                <strong style="font-size:14px">{{ $item->title }}</strong>
                @if ($item->body)
                    <div class="muted" style="margin-top:2px">{{ $item->body }}</div>
                @endif
                <div style="margin-top:6px">
                    @if ($item->read_at === null)
                        <form method="POST" action="{{ route('notifications.update', $item) }}" class="inline-form">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn small secondary">Tandai dibaca</button>
                        </form>
                    @else
                        <span class="muted">Sudah dibaca</span>
                    @endif
                </div>
            </div>
        @empty
            <div class="muted" style="padding:8px 4px">Belum ada notifikasi.</div>
        @endforelse

        <div style="margin-top:8px">
            <a href="{{ route('notifications.index') }}" style="font-size:13px">Lihat semua notifikasi</a>
        </div>
    </div>
</details>
