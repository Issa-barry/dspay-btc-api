<?php

namespace App\Http\Controllers\Transfert;

use App\Http\Controllers\Controller;
use App\Models\Transfert;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Exception;

class TransfertShowController extends Controller
{
    use JsonResponseTrait;

    public function index(Request $request)
    {
        try {
            $data = $request->validate([
                'search'          => 'nullable|string|max:100',
                'statut'          => ['nullable'], // string|array
                'beneficiaire_id' => 'nullable|integer',
                'user_id'         => 'nullable|integer',
                'date_from'       => 'nullable|date_format:Y-m-d',
                'date_to'         => 'nullable|date_format:Y-m-d',
                'montant_min'     => 'nullable|numeric',
                'montant_max'     => 'nullable|numeric',
                'total_min'       => 'nullable|numeric',
                'total_max'       => 'nullable|numeric',
                'sort_by'         => ['nullable', Rule::in(['created_at','montant_envoie','total_ttc','code'])],
                'sort_dir'        => ['nullable', Rule::in(['asc','desc'])],
                'per_page'        => 'nullable|integer|min:1|max:200',
                'page'            => 'nullable|integer|min:1',
            ]);

            $query = Transfert::query()
                ->with(['beneficiaire','expediteur','deviseSource','deviseCible','tauxEchange']);

            // 🔎 Recherche globale
            $this->applySearch($query, $data['search'] ?? null);

            // Filtre statut (string ou array)
            if (!empty($data['statut'])) {
                $statuts = is_array($data['statut']) ? $data['statut'] : [$data['statut']];
                $query->whereIn('statut', $statuts);
            }

            if (!empty($data['beneficiaire_id'])) $query->where('beneficiaire_id', $data['beneficiaire_id']);
            if (!empty($data['user_id']))         $query->where('user_id', $data['user_id']);

            if (!empty($data['date_from'])) $query->whereDate('created_at', '>=', $data['date_from']);
            if (!empty($data['date_to']))   $query->whereDate('created_at', '<=', $data['date_to']);

            // Plage EUR envoyée (montant_envoie)
            if (!empty($data['montant_min'])) $query->where('montant_envoie', '>=', $data['montant_min']);
            if (!empty($data['montant_max'])) $query->where('montant_envoie', '<=', $data['montant_max']);

            // Plage total TTC (EUR)
            if (!empty($data['total_min'])) $query->where('total_ttc', '>=', $data['total_min']);
            if (!empty($data['total_max'])) $query->where('total_ttc', '<=', $data['total_max']);

            $sortBy  = $data['sort_by']  ?? 'created_at';
            $sortDir = $data['sort_dir'] ?? 'desc';
            $query->orderBy($sortBy, $sortDir);

            $perPage = $data['per_page'] ?? 15;
            $page    = $data['page'] ?? null;

            $paginator = $query->paginate($perPage, ['*'], 'page', $page);

            // Masquer le code si statut = en_cours
            $items = $paginator->getCollection()->map(function (Transfert $t) {
                if ($t->statut === 'en_cours') {
                    $t->makeHidden('code');
                }
                return $t;
            });

            return $this->responseJson(true, 'Liste des transferts récupérée avec succès.', [
                'items' => $items->values(),
                'meta'  => [
                    'current_page' => $paginator->currentPage(),
                    'per_page'     => $paginator->perPage(),
                    'total'        => $paginator->total(),
                    'last_page'    => $paginator->lastPage(),
                ],
            ]);
        } catch (Exception $e) {
            return $this->responseJson(false, 'Erreur lors de la récupération des transferts.', $e->getMessage(), 500);
        }
    }

    public function show(Request $request, int $id)
    {
        $transfert = Transfert::with([
            'beneficiaire','expediteur','deviseSource','deviseCible','tauxEchange',
        ])->find($id);

        if (!$transfert) {
            return $this->responseJson(false, 'Transfert introuvable.', null, 404);
        }

        $user = $request->user();
        $isOwner = $user && (int)$user->id === (int)$transfert->user_id;
        $isAdmin = $user && method_exists($user, 'hasRole') ? $user->hasRole('Admin') : false;

        if ($transfert->statut === 'en_cours' && !($isOwner || $isAdmin)) {
            $transfert->makeHidden('code');
        }

        return $this->responseJson(true, 'Transfert récupéré avec succès.', $transfert);
    }

    public function showByCode(Request $request, string $code)
    {
        $transfert = Transfert::with([
            'beneficiaire','expediteur','deviseSource','deviseCible','tauxEchange',
        ])->where('code', $code)->first();

        if (!$transfert) {
            return $this->responseJson(false, 'Transfert introuvable pour ce code.', null, 404);
        }

        return $this->responseJson(true, 'Transfert récupéré avec succès.', $transfert);
    }

    public function byUser(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) return $this->responseJson(false, 'Non authentifié.', null, 401);

            $data = $request->validate([
                'search'          => 'nullable|string|max:100',
                'statut'          => ['nullable'], // string|array
                'beneficiaire_id' => 'nullable|integer',
                'date_from'       => 'nullable|date_format:Y-m-d',
                'date_to'         => 'nullable|date_format:Y-m-d',
                'montant_min'     => 'nullable|numeric',
                'montant_max'     => 'nullable|numeric',
                'total_min'       => 'nullable|numeric',
                'total_max'       => 'nullable|numeric',
                'sort_by'         => ['nullable', Rule::in(['created_at','montant_envoie','total_ttc','code'])],
                'sort_dir'        => ['nullable', Rule::in(['asc','desc'])],
                'per_page'        => 'nullable|integer|min:1|max:200',
                'page'            => 'nullable|integer|min:1',
            ]);

            $query = Transfert::query()
                ->with(['beneficiaire','expediteur','deviseSource','deviseCible','tauxEchange'])
                ->where('user_id', $user->id);

            // 🔎 Recherche globale
            $this->applySearch($query, $data['search'] ?? null);

            if (!empty($data['statut'])) {
                $statuts = is_array($data['statut']) ? $data['statut'] : [$data['statut']];
                $query->whereIn('statut', $statuts);
            }

            if (!empty($data['beneficiaire_id'])) $query->where('beneficiaire_id', $data['beneficiaire_id']);

            if (!empty($data['date_from'])) $query->whereDate('created_at', '>=', $data['date_from']);
            if (!empty($data['date_to']))   $query->whereDate('created_at', '<=', $data['date_to']);

            if (!empty($data['montant_min'])) $query->where('montant_envoie', '>=', $data['montant_min']);
            if (!empty($data['montant_max'])) $query->where('montant_envoie', '<=', $data['montant_max']);

            if (!empty($data['total_min'])) $query->where('total_ttc', '>=', $data['total_min']);
            if (!empty($data['total_max'])) $query->where('total_ttc', '<=', $data['total_max']);

            $sortBy  = $data['sort_by'] ?? 'created_at';
            $sortDir = $data['sort_dir'] ?? 'desc';
            $query->orderBy($sortBy, $sortDir);

            $perPage   = $data['per_page'] ?? 15;
            $page      = $data['page'] ?? null;
            $paginator = $query->paginate($perPage, ['*'], 'page', $page);

            return $this->responseJson(true, 'Liste des transferts (moi) récupérée avec succès.', [
                'items' => $paginator->getCollection()->values(),
                'meta'  => [
                    'current_page' => $paginator->currentPage(),
                    'per_page'     => $paginator->perPage(),
                    'total'        => $paginator->total(),
                    'last_page'    => $paginator->lastPage(),
                ],
            ]);
        } catch (Exception $e) {
            return $this->responseJson(false, 'Erreur lors de la récupération des transferts (moi).', $e->getMessage(), 500);
        }
    }

    /**
     * Recherche globale:
     * - texte: code, statut, serviceId
     * - num: total_ttc, total_gnf, amount, montant_envoie, frais
     * - tel: recipientTel, customerPhoneNumber
     * - bénéficiaire: nom, prenom, "prenom nom", phone
     */
    private function applySearch($query, ?string $search): void
    {
        if (!$search) return;

        $s = trim($search);
        if ($s === '') return;

        $isNumeric = preg_match('/^\d+([.,]\d+)?$/', $s) === 1;
        $num = $isNumeric ? (float) str_replace(',', '.', $s) : null;

        $query->where(function ($q) use ($s, $isNumeric, $num) {

            // 🔤 texte
            $q->where('code', 'like', "%{$s}%")
              ->orWhere('statut', 'like', "%{$s}%")
              ->orWhere('serviceId', 'like', "%{$s}%")
              ->orWhere('recipientTel', 'like', "%{$s}%")
              ->orWhere('customerPhoneNumber', 'like', "%{$s}%");

            // 👤 bénéficiaire
            $q->orWhereHas('beneficiaire', function ($qb) use ($s) {
                $qb->where('nom', 'like', "%{$s}%")
                   ->orWhere('prenom', 'like', "%{$s}%")
                   ->orWhereRaw("CONCAT(COALESCE(prenom,''),' ',COALESCE(nom,'')) LIKE ?", ["%{$s}%"])
                   ->orWhere('phone', 'like', "%{$s}%");
            });

            // 🔢 numérique (égalité)
            // 🔢 numérique (partiel) : 53 => match 52.5 / 535000 / 105.0
            if ($isNumeric) {
                // On recherche "53" dans la représentation texte du champ
                $like = "%{$s}%";

                $q->orWhereRaw("CAST(total_ttc AS CHAR) LIKE ?", [$like])
                ->orWhereRaw("CAST(total_gnf AS CHAR) LIKE ?", [$like])
                ->orWhereRaw("CAST(amount AS CHAR) LIKE ?", [$like])
                ->orWhereRaw("CAST(montant_envoie AS CHAR) LIKE ?", [$like])
                ->orWhereRaw("CAST(frais AS CHAR) LIKE ?", [$like]);

                // Bonus: si l'utilisateur tape "53€" ou "53 GNF" côté front, on garde juste les chiffres
                // (optionnel: à faire plutôt côté front)
            }

        });
    }
}
