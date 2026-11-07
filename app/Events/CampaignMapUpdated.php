<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CampaignMapUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $mapGuid,
        public string $action,        // 'entity.added' | 'entity.updated' | 'entity.deleted' | 'map.updated'
        public ?string $entityGuid,
        public int $byUserId,
        public string $updatedAt,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('campaign-map.' . $this->mapGuid)];
    }

    public function broadcastAs(): string
    {
        return 'map.updated';
    }
}
