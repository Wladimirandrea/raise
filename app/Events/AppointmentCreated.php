<?php

namespace App\Events;

use App\Models\Appointment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AppointmentCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Appointment $appointment)
    {
        $this->appointment->load(['caseManager:id,name,avatar', 'client:id,name,avatar']);
    }

    // Canal privado del case manager
    public function broadcastOn(): Channel
    {
        return new PrivateChannel('case-manager.' . $this->appointment->case_manager_id);
    }

    public function broadcastAs(): string
    {
        return 'appointment.created';
    }

    public function broadcastWith(): array
    {
        return [
            'id'               => $this->appointment->id,
            'title'            => $this->appointment->title,
            'appointment_date' => $this->appointment->appointment_date,
            'start_time'       => $this->appointment->start_time,
            'end_time'         => $this->appointment->end_time,
            'client'           => [
                'id'     => $this->appointment->client->id,
                'name'   => $this->appointment->client->name,
                'avatar' => $this->appointment->client->avatar,
            ],
        ]   ;
    }
}