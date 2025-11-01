<?php

namespace App\Http\Controllers\Depot;

use App\Http\Controllers\Controller;
use App\Models\Depot;
use Illuminate\Http\Request;
use App\Mail\DepotNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Traits\JsonResponseTrait;
use Exception;

class DepotController extends Controller
{
    use JsonResponseTrait;

    public function store(Request $request)
    {
        try {
            // ✅ Validation des entrées
            $validated = $request->validate([
                'serviceId' => 'required|string',
                'amount' => 'required|numeric|min:0.01',
                'recipientTel' => 'nullable|string',
                'accountId' => 'nullable|string',
                'customerPhoneNumber' => 'nullable|string',
            ]);

            // ✅ Création du dépôt
            $depot = Depot::create([
                'serviceId' => $validated['serviceId'],
                'amount' => $validated['amount'],
                'recipientTel' => $validated['recipientTel'] ?? null,
                'accountId' => $validated['accountId'] ?? null,
                'customerPhoneNumber' => $validated['customerPhoneNumber'] ?? null,
                'transaction_ref' => Str::uuid(),
            ]);

            // ✅ Simulation ou appel réel vers KS-PAY
            // Exemple d’appel (à adapter selon la vraie API)
            /*
            $response = Http::timeout(10)->post('https://api.ks-pay.com/depot', [
                'serviceId' => $depot->serviceId,
                'amount' => $depot->amount,
                'recipientTel' => $depot->recipientTel,
                'accountId' => $depot->accountId,
                'customerPhoneNumber' => $depot->customerPhoneNumber,
            ]);

            if ($response->failed()) {
                $depot->update(['status' => 'failed']);
                return $this->responseJson(false, 'Échec lors de la communication avec KS-PAY', [
                    'error' => $response->json()
                ], 502);
            }
            */

            // ✅ Pour l’instant on simule une réussite
            $depot->update(['status' => 'success']);

            // ✅ Envoi de mail de notification (protégé contre les erreurs)
            try {
                Mail::to('client@example.com')->send(new DepotNotification($depot));
            } catch (Exception $e) {
                // On log mais on n’interrompt pas le processus
                \Log::error('Erreur lors de l’envoi du mail de dépôt : ' . $e->getMessage());
            }

            // ✅ Réponse JSON standardisée
            return $this->responseJson(true, 'Dépôt enregistré avec succès.', $depot, 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->responseJson(false, 'Erreur de validation', $e->errors(), 422);

        } catch (Exception $e) {
            \Log::error('Erreur dans DepotController@store : ' . $e->getMessage());
            return $this->responseJson(false, 'Une erreur interne est survenue.', null, 500);
        }
    }
}
