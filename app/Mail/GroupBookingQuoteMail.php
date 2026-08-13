<?php

namespace App\Mail;

use App\Models\GroupBookingRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GroupBookingQuoteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public GroupBookingRequest $groupRequest,
        public array $quote,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Báo giá đặt đoàn/nhóm — Yêu cầu #' . $this->groupRequest->id,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.group-booking-quote',
        );
    }
}
