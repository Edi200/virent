<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VehicleAvailabilityChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public int $vehicleId) {}

    /**
     * @return list<Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('vehicle.'.$this->vehicleId.'.availability'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'VehicleAvailabilityChanged';
    }

    /**
     * @return array{vehicle_id: int}
     */
    public function broadcastWith(): array
    {
        return [
            'vehicle_id' => $this->vehicleId,
        ];
    }
}
