<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\User;

use Illuminate\Auth\Events\Verified;
 
class UpdateUserStatusOnEmailVerified
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \Illuminate\Auth\Events\Verified  $event
     * @return void
     */
    public function handle(Verified $event)
    {
        $user = $event->user;

        if ($user instanceof User) {
            // $user->statut = 'active'; 
            // $user->save(); // Sauvegarder dans la base de données
            if ($user->statut === 'attente') {
                $user->statut = 'active';
                $user->save();
            }
        }
    }
}
