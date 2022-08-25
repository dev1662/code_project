<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ForgotOrganization extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected $centralUser;
    protected $tenants;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($centralUser, $tenants)
    {
        $this->centralUser = $centralUser;
        $this->tenants = $tenants;

        // $this->connection = 'database';
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Forgot Organization - '.config('app.name'))->markdown('emails.auth.forgot_organization', [
            'centralUser' => $this->centralUser,
            'tenants' => $this->tenants,
        ]);
    }
}
