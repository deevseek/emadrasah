<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\GuardianRegistered;
use App\Mail\GuardianRegistrationMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendGuardianRegistrationEmail implements ShouldQueue
{
    public function handle(GuardianRegistered $event): void
    {
        Mail::to($event->guardian->user->email)
            ->send(new GuardianRegistrationMail($event->guardian, $event->student));
    }
}
