<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use App\Models\Transfert;

class TransfertAnnulationNotification extends Notification
{
    use Queueable;

    protected $transfert;

    /**
     * Créer une nouvelle instance de notification.
     */
    public function __construct(Transfert $transfert)
    {
        $this->transfert = $transfert;
    }

    /**
     * Définir les canaux de notification (mail, base de données, etc.).
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Définir l'e-mail en utilisant une vue Blade.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Votre transfert a été annulé')
            ->view('mail.transfertAnnulationNotification', ['transfert' => $this->transfert]); // ✅ Utilisation de la vue Blade
    }

    /**
     * Retourner des données pour la notification en base de données (optionnel).
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'Votre transfert avec le code ' . $this->transfert->code . ' a été annulé.',
            'transfert_id' => $this->transfert->id
        ];
    }
}
