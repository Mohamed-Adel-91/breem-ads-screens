<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountApprovedWithoutSystemsMail extends Mailable
{
    use Queueable, SerializesModels;

    public $email;

    public function __construct($email)
    {
        $this->email = $email;
    }

    public function envelope()
    {
        return new Envelope(
            subject: 'Congratulations! Your account has been approved successfully',
        );
    }

    public function build()
    {
        return $this->markdown('emails.account_approved_without_systems_email')
            ->with(['email' => $this->email]);
    }
}
