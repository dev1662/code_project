<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class JoiningInvitation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected $centralUser;
    protected $organization;
    protected $url;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($centralUser, $organization, $url)
    {
        $this->centralUser = $centralUser;
        $this->organization = $organization;
        $this->url = $url;

        // $this->connection = 'database';
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Joining Invitation - '.config('app.name'))->markdown('emails.auth.joining_invitation', [
            'centralUser' => $this->centralUser,
            'organization' => $this->organization,
            'url' => $this->url,
        ]);
    }
}
