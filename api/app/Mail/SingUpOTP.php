<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SingUpOTP extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected $otp;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($otp)
    {
        $this->otp = $otp;

        // $this->connection = 'database';
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('One Time Password for '.config('app.name').' Registration')->markdown('emails.auth.signup_otp', [
            'otp' => $this->otp,
        ]);
    }
}
