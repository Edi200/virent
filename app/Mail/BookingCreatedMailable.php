<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingCreatedMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking) {}

    public function envelope(): Envelope
    {
        $reference = 'VR-'.str_pad((string) $this->booking->id, 6, '0', STR_PAD_LEFT);

        return new Envelope(
            subject: __('Booking request received — :reference', ['reference' => $reference]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.booking-created',
        );
    }
}
