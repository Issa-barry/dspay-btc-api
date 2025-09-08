<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

use App\Models\Transfert;
use Illuminate\Notifications\Messages\MailMessage;

class TransfertNotification extends Mailable
{
    use Queueable, SerializesModels;
    public $transfert;

   
     /**
     * Créer une nouvelle instance de TransfertNotification.
     *
     * @param  \App\Models\Transfert  $transfert
     * @return void
     */
    public function __construct(Transfert $transfert)
    {
        $this->transfert = $transfert;
    }

    /**
     * Construire le message de l'email.
     *
     * @return \Illuminate\Mail\Mailable
     */
    public function build()
    {
        return $this->subject('Détails de votre transfert')
                    ->view('emails.transfertNotification')
                    ->with([
                        'transfert' => $this->transfert,
                        // 'codeDechiffre' => $codeDechiffre,
                    ]);
    }

}
