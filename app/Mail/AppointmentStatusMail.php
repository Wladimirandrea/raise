<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppointmentStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $lang;

    public function __construct(
        public Appointment $appointment,
        string $lang = 'es',
        public string $recipientType = 'client'
    ) {
        $this->lang = $lang;
        $this->appointment->loadMissing(['caseManager', 'client']);
    }

    public function envelope(): Envelope
    {
        $subject = match($this->appointment->status) {
            'confirmed' => $this->lang === 'en' ? 'Your appointment has been confirmed' : 'Tu cita ha sido confirmada',
            'completed' => $this->lang === 'en' ? 'Your appointment has been completed' : 'Tu cita ha sido completada',
            'cancelled' => $this->lang === 'en' ? 'Your appointment has been cancelled' : 'Tu cita ha sido cancelada',
            'no_show'   => $this->lang === 'en' ? 'Missed appointment'                  : 'Cita no atendida',
            default     => $this->lang === 'en' ? 'Appointment status updated'           : 'Estado de cita actualizado',
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.appointments.status',
            with: [
                'appointment'   => $this->appointment,
                'recipientType' => $this->recipientType,
                'recipient'     => $this->recipientType === 'client'
                    ? $this->appointment->client
                    : $this->appointment->caseManager,
                'locale'        => $this->lang,
            ]
        );
    }
}