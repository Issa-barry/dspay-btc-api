<?php

namespace App\Http\Controllers\Depot;

use App\Http\Controllers\Controller;
use App\Models\Depot;
use Illuminate\Http\Request;
use App\Mail\DepotNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Traits\JsonResponseTrait;
use Exception;

class DepotController extends Controller
{
    use JsonResponseTrait;

    public function store(Request $request)
    {
        try {
            // ✅ Validation des entrées — tous obligatoires
            $validated = $request->validate([
                'serviceId'            => 'required|string',
                'amount'               => 'required|numeric|min:0.01',
                'recipientTel'         => 'required|string',
                'accountId'            => 'required|string',
                'customerPhoneNumber'  => 'required|string',
            ]);

            $user = Auth::user();
            if (!$user) {
                return $this->responseJson(false, 'Non authentifié.', null, 401);
            }

            // ✅ Transaction DB pour générer une référence unique/jour sans collision
            $depot = DB::transaction(function () use ($validated, $user) {
                $todayYmd = now()->format('Ymd');

                // Verrouillage pessimiste sur la sélection du dernier enregistrement du jour
                $lastToday = Depot::whereDate('created_at', now()->toDateString())
                    ->where('transaction_ref', 'like', "DSP-{$todayYmd}-%")
                    ->lockForUpdate()
                    ->orderByDesc('id')
                    ->first();

                $next = 1;
                if ($lastToday && preg_match('/DSP-\d{8}-(\d{4})$/', $lastToday->transaction_ref, $m)) {
                    $next = (int)$m[1] + 1;
                }
                $increment = str_pad($next, 4, '0', STR_PAD_LEFT);
                $transactionRef = "DSP-{$todayYmd}-{$increment}";

                // Création initiale en 'pending'
                return Depot::create([
                    'user_id'             => $user->id,
                    'serviceId'           => $validated['serviceId'],
                    'amount'              => $validated['amount'],
                    'recipientTel'        => $validated['recipientTel'],
                    'accountId'           => $validated['accountId'],
                    'customerPhoneNumber' => $validated['customerPhoneNumber'],
                    'status'              => 'pending',         // enum: pending|success|failed
                    'transaction_ref'     => $transactionRef,  // DSP-YYYYMMDD-000X
                ]);
            });

            // ✅ Simulation ou appel réel vers KS-PAY (à adapter)
            /*
            $response = Http::timeout(15)->post('https://api.ks-pay.com/depot', [
                'serviceId'           => $depot->serviceId,
                'amount'              => $depot->amount,
                'recipientTel'        => $depot->recipientTel,
                'accountId'           => $depot->accountId,
                'customerPhoneNumber' => $depot->customerPhoneNumber,
                'transaction_ref'     => $depot->transaction_ref,
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

            // ✅ Envoi de l’email de confirmation à l’utilisateur connecté (sans bloquer en cas d’erreur)
            try {
                Mail::to($user->email)->send(new DepotNotification($depot));
            } catch (Exception $e) {
                \Log::error('Erreur envoi email confirmation dépôt : ' . $e->getMessage());
            }

            // ✅ Réponse JSON standardisée
            return $this->responseJson(true, 'Dépôt enregistré avec succès.', $depot->fresh(), 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->responseJson(false, 'Erreur de validation', $e->errors(), 422);

        } catch (Exception $e) {
            \Log::error('Erreur dans DepotController@store : ' . $e->getMessage());
            return $this->responseJson(false, 'Une erreur interne est survenue.', null, 500);
        }
    }
}
