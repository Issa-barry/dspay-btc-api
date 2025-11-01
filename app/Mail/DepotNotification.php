<?php

namespace App\Mail;

use App\Models\Depot;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DepotNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $depot;

    public function __construct(Depot $depot)
    {
        $this->depot = $depot;
    }

    public function build()
    {
        return $this->subject('Confirmation de votre dépôt')
                    ->markdown('emails.depotNotification')
                    ->with([
                        'depot' => $this->depot,
                    ]);
    }
}
