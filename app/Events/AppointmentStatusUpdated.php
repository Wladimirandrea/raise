<?php

namespace App\Events;

use App\Models\Appointment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AppointmentStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Appointment $appointment)
    {
        $this->appointment->loadMissing(['caseManager', 'client']);
    }

    // Broadcastea al admin Y al case manager
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.notifications'),
            new PrivateChannel('case-manager.' . $this->appointment->case_manager_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'appointment.status.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'id'           => $this->appointment->id,
            'status'       => $this->appointment->status,
            'title'        => $this->appointment->title,
            'case_manager' => $this->appointment->caseManager?->name,
            'client'       => $this->appointment->client?->name,
        ];
    }
}