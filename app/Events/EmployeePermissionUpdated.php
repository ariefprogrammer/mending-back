<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;


class EmployeePermissionUpdated implements ShouldBroadcastNow
{
    public function __construct(
        public string $employeeId,
        public array $permissions,
    ) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('employee.' . $this->employeeId);
    }

    public function broadcastAs(): string
    {
        return 'permission.updated';
    }
}
