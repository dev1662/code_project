<?php

namespace App\Observers;

use App\Models\CentralOnboarding;
use App\Notifications\SignupEmailNotification;

class UserObserver
{
    /**
     * Handle the CentralOnboarding "created" event.
     *
     * @param  \App\Models\CentralOnboarding  $centralOnboarding
     * @return void
     */
    public function created(CentralOnboarding $centralOnboarding)
    {
        $centralOnboarding->notify(new SignupEmailNotification($centralOnboarding->otp));

    }

    /**
     * Handle the CentralOnboarding "updated" event.
     *
     * @param  \App\Models\CentralOnboarding  $centralOnboarding
     * @return void
     */
    public function updated(CentralOnboarding $centralOnboarding)
    {
        //
    }

    /**
     * Handle the CentralOnboarding "deleted" event.
     *
     * @param  \App\Models\CentralOnboarding  $centralOnboarding
     * @return void
     */
    public function deleted(CentralOnboarding $centralOnboarding)
    {
        //
    }

    /**
     * Handle the CentralOnboarding "restored" event.
     *
     * @param  \App\Models\CentralOnboarding  $centralOnboarding
     * @return void
     */
    public function restored(CentralOnboarding $centralOnboarding)
    {
        //
    }

    /**
     * Handle the CentralOnboarding "force deleted" event.
     *
     * @param  \App\Models\CentralOnboarding  $centralOnboarding
     * @return void
     */
    public function forceDeleted(CentralOnboarding $centralOnboarding)
    {
        //
    }
}
