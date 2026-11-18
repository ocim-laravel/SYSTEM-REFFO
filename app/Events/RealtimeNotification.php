<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event uji realtime MOD-10 (AC-10-6).
 *
 * Hanya memakai PrivateChannel dan baru dikirim setelah transaksi database
 * berhasil di-commit (04-architecture §4.2).
 */
class RealtimeNotification implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  list<string>  $channels  nama kanal tanpa awalan "private-"
     */
    public function __construct(
        public string $message = 'Notifikasi realtime berhasil dikirim.',
        public ?string $sentAt = null,
        public array $channels = [],
    ) {
        $this->sentAt ??= now()->toDateTimeString();
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return array_map(
            fn (string $channel): PrivateChannel => new PrivateChannel($channel),
            $this->channels,
        );
    }

    public function broadcastAs(): string
    {
        return 'RealtimeNotification';
    }

    /**
     * Hanya sinyal kecil: tidak ada email, harga, atau data sensitif lain.
     *
     * @return array<string, string>
     */
    public function broadcastWith(): array
    {
        return [
            'message' => $this->message,
            'sent_at' => $this->sentAt ?? '',
        ];
    }
}
