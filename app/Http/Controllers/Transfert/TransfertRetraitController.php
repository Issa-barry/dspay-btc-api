<?php

namespace App\Http\Controllers\Transfert;

use App\Http\Controllers\Controller;
use App\Mail\TransfertRetireNotification;
use App\Models\Transfert;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\Request;
use Mail;
use Validator;

class TransfertRetraitController extends Controller
{
     use JsonResponseTrait;

    /**
     * Valider un retrait de transfert.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function validerRetrait(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'code' => 'required|string|exists:transferts,code',
        ]);

        if ($validated->fails()) {
            return $this->responseJson(false, 'Validation échouée.', $validated->errors(), 422);
        }

        // Recherche du transfert avec le code
        $transfert = Transfert::where('code', $request->code)->first();

        if (!$transfert) {
            return $this->responseJson(false, 'Transfert non trouvé.', null, 404);
        }

        // Utilisation de switch pour gérer les messages selon le statut du transfert
        switch ($transfert->statut) {
            case 'retiré':
                return $this->responseJson(false, 'Ce transfert a déjà été retiré.', null);

            case 'annulé':
                return $this->responseJson(false, 'Ce transfert a été annulé et ne peut pas être retiré.', null);

            default:
                // Si le statut est valide pour le retrait
                // Mettre à jour le statut du transfert
                $transfert->statut = 'retiré';
                $transfert->save();

                 Mail::to($transfert->expediteur_email)->send(new TransfertRetireNotification($transfert));

                return $this->responseJson(true, 'Retrait effectué avec succès.', $transfert);
        }
    }

    
}
