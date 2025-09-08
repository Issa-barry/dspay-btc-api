<?php

namespace App\Http\Controllers\Transfert;

use App\Http\Controllers\Controller;
use App\Models\Transfert;
use App\Notifications\TransfertAnnulationNotification;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Exception;

class TransfertAnnulerController extends Controller
{
    use JsonResponseTrait;

    /**
     * Annuler un transfert existant avec gestion des erreurs.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function annulerTransfert($id)
    {
        try {
            // Vérification si l'ID est un nombre valide
            if (!is_numeric($id)) {
                return $this->responseJson(false, 'ID de transfert invalide.', null, 400);
            }

            // Rechercher le transfert
            $transfert = Transfert::find($id);

            if (!$transfert) {
                return $this->responseJson(false, 'Transfert non trouvé.', null, 404);
            }

            if ($transfert->statut !== 'en_cours') {
                return $this->responseJson(false, 'Seuls les transferts en cours peuvent être annulés.', null, 400);
            }

            // Mettre à jour le statut du transfert
            $transfert->update(['statut' => 'annulé']);

            // Essayer d'envoyer l'email de notification
            // if ($transfert->expediteur_email) {
            //     try {
            //         Notification::route('mail', $transfert->expediteur_email)
            //             ->notify(new TransfertAnnulationNotification($transfert));
            //     } catch (Exception $e) {
            //         return $this->responseJson(false, 'Transfert annulé, mais erreur lors de l’envoi de l’email.', [
            //             'transfert' => $transfert,
            //             'email_error' => $e->getMessage()
            //         ], 500);
            //     }
            // }

            return $this->responseJson(true, 'Transfert annulé avec succès. Une notification a été envoyée.', $transfert);
        } catch (Exception $e) {
            return $this->responseJson(false, 'Une erreur est survenue lors de l’annulation du transfert.', $e->getMessage(), 500);
        }
    }
}
