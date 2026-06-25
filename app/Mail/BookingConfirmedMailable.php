<?php

namespace App\Mail;

use App\Models\Booking;
use App\Services\BookingContractService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingConfirmedMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Booking confirmed — :reference', ['reference' => $this->booking->reference()]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.booking-confirmed',
        );
    }

    /**
     * @return list<Attachment>
     */
    public function attachments(): array
    {
        $service = app(BookingContractService::class);

        return [
            Attachment::fromData(
                fn () => $service->generate($this->booking)->output(),
                $service->filename($this->booking),
            )->withMime('application/pdf'),
        ];
    }
}
