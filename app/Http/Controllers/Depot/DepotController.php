<?php

namespace App\Http\Controllers\Depot;

use App\Http\Controllers\Controller;
use App\Models\Depot;
use Illuminate\Http\Request;
use App\Mail\DepotNotification;
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
            // ✅ Validation (sans accountId)
            $validated = $request->validate([
                'serviceId'           => 'required|string',
                'montant_envoye'      => 'required|numeric|min:0.01',
                'amount'              => 'required|integer|min:1',
                'recipientTel'        => 'required|string',
                'customerPhoneNumber' => 'required|string',
            ]);

            $user = $request->user();
            if (!$user) {
                return $this->responseJson(false, 'Non authentifié.', null, 401);
            }

            $depot = DB::transaction(function () use ($validated, $user) {
                $todayYmd = now()->format('Ymd');

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

                return Depot::create([
                    'user_id'             => $user->id,
                    'serviceId'           => $validated['serviceId'],
                    'montant_envoye'      => $validated['montant_envoye'],
                    'amount'              => $validated['amount'],
                    'recipientTel'        => $validated['recipientTel'],
                    'customerPhoneNumber' => $validated['customerPhoneNumber'],
                    'status'              => 'pending',
                    'transaction_ref'     => $transactionRef,
                ]);
            });

            // ✅ Simulation succès
            $depot->update(['status' => 'success']);

            // ✅ Envoi d’email
            try {
                Mail::to($user->email)->send(new DepotNotification($depot->fresh()));
            } catch (Exception $e) {
                \Log::error('Erreur envoi mail dépôt : ' . $e->getMessage());
            }

            return $this->responseJson(true, 'Dépôt enregistré avec succès.', $depot->fresh(), 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->responseJson(false, 'Erreur de validation', $e->errors(), 422);

        } catch (Exception $e) {
            \Log::error('Erreur dans DepotController@store : ' . $e->getMessage());
            return $this->responseJson(false, 'Une erreur interne est survenue.', null, 500);
        }
    }
}
