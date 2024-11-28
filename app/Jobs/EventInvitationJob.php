<?php

namespace App\Jobs;

use App\Mail\EventInvitationMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class EventInvitationJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    protected $invitee;

    public function __construct($invitee)
    {
        $this->invitee = $invitee;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $invitee = $this->invitee;

        Mail::to($invitee->email)->send(new EventInvitationMail($invitee));
    }
}
