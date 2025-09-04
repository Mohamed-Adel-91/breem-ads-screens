<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Setting;

class ThanksMail extends Mailable
{
    use Queueable, SerializesModels;

    public $messageBody;

    public function __construct(string $messageBody)
    {
        $this->messageBody = $messageBody;
    }

    public function envelope()
    {
        return new Envelope(
            subject: 'Thank you',
        );
    }

    public function build()
    {
        $setting = Setting::first();
        $from = $setting?->email;

        return $this->from($from)
            ->markdown('emails.thanks_email')
            ->with(['messageBody' => $this->messageBody]);
    }
}
