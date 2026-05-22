<?php

namespace App\Mail;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventAttendeeAnnouncement extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Event $event,
        public string $subjectLine,
        public string $messageBody,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.event-attendee-announcement');
    }
}
