<?php

namespace App\Mail;

use App\Models\Transfert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TransfertRetireNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $transfert;

    /**
     * Créer une nouvelle instance du message.
     *
     * @param  \App\Models\Transfert  $transfert
     */
    public function __construct(Transfert $transfert)
    {
        $this->transfert = $transfert;
    }

    /**
     * Obtenez l'enveloppe du message.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Retrait du transfert effectué'
        );
    }

    /**
     * Contenu du message.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.transfertRetire',
        );
    }

    /**
     * Les pièces jointes pour le message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
