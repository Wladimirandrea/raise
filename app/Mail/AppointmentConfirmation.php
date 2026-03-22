<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppointmentConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public string $lang;

    public function __construct(
        public Appointment $appointment,
        public string $recipientType,
        string $lang = 'es'
    ) {
        $this->lang = $lang;
        $this->appointment->loadMissing(['caseManager', 'client']);
    }

    public function envelope(): Envelope
    {
        if ($this->lang === 'en') {
            $subject = $this->recipientType === 'client'
                ? 'Your appointment has been confirmed'
                : 'New appointment scheduled for you';
        } else {
            $subject = $this->recipientType === 'client'
                ? 'Tu cita ha sido agendada'
                : 'Nueva cita agendada para ti';
        }

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.appointment-confirmation',
            with: [
                'appointment'   => $this->appointment,
                'recipientType' => $this->recipientType,
                'locale'        => $this->lang,
                'recipient'     => $this->recipientType === 'client'
                    ? $this->appointment->client
                    : $this->appointment->caseManager,
            ]
        );
    }
}