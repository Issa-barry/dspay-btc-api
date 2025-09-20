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

    /**
     * GET /api/transferts
     * Filtres disponibles (query string) :
     * - search              : string (code, nom/prénom bénéficiaire, téléphone)
     * - statut              : en_cours|retire|annule  (ou liste: statut[]=en_cours&statut[]=retire)
     * - beneficiaire_id     : int
     * - user_id             : int (expéditeur)
     * - date_from           : YYYY-MM-DD
     * - date_to             : YYYY-MM-DD
     * - montant_min         : number (en EUR)
     * - montant_max         : number (en EUR)
     * - total_min           : number (EUR)
     * - total_max           : number (EUR)
     * - sort_by             : created_at|montant_euro|total|code
     * - sort_dir            : asc|desc
     * - page, per_page      : pagination
     */
    public function index(Request $request)
    {
        try {
            // Validation légère des filtres
            $data = $request->validate([
                'search'          => 'nullable|string|max:100',
                'statut'          => ['nullable'], // string ou array
                'beneficiaire_id' => 'nullable|integer',
                'user_id'         => 'nullable|integer',
                'date_from'       => 'nullable|date_format:Y-m-d',
                'date_to'         => 'nullable|date_format:Y-m-d',
                'montant_min'     => 'nullable|numeric',
                'montant_max'     => 'nullable|numeric',
                'total_min'       => 'nullable|numeric',
                'total_max'       => 'nullable|numeric',
                'sort_by'         => ['nullable', Rule::in(['created_at','montant_euro','total','code'])],
                'sort_dir'        => ['nullable', Rule::in(['asc','desc'])],
                'per_page'        => 'nullable|integer|min:1|max:200',
                'page'            => 'nullable|integer|min:1',
            ]);

            $query = Transfert::query()
                ->with(['beneficiaire','expediteur','deviseSource','deviseCible','tauxEchange']);

            // Recherche plein-texte simple
            if (!empty($data['search'])) {
                $s = trim($data['search']);
                $query->where(function ($q) use ($s) {
                    $q->where('code', 'like', "%{$s}%")
                      ->orWhereHas('beneficiaire', function ($qb) use ($s) {
                          $qb->where('nom', 'like', "%{$s}%")
                             ->orWhere('prenom', 'like', "%{$s}%")
                             ->orWhere('nom_complet', 'like', "%{$s}%")
                             ->orWhere('phone', 'like', "%{$s}%");
                      });
                });
            }

            // Statuts (string ou array)
            if (!empty($data['statut'])) {
                $statuts = is_array($data['statut']) ? $data['statut'] : [$data['statut']];
                $query->whereIn('statut', $statuts);
            }

            // Filtres directs
            if (!empty($data['beneficiaire_id'])) {
                $query->where('beneficiaire_id', $data['beneficiaire_id']);
            }
            if (!empty($data['user_id'])) {
                $query->where('user_id', $data['user_id']);
            }

            // Fenêtre de dates (sur created_at)
            if (!empty($data['date_from'])) {
                $query->whereDate('created_at', '>=', $data['date_from']);
            }
            if (!empty($data['date_to'])) {
                $query->whereDate('created_at', '<=', $data['date_to']);
            }

            // Plages de montants (EUR)
            if (!empty($data['montant_min'])) {
                $query->where('montant_euro', '>=', $data['montant_min']);
            }
            if (!empty($data['montant_max'])) {
                $query->where('montant_euro', '<=', $data['montant_max']);
            }

            // Plages de total débité (EUR)
            if (!empty($data['total_min'])) {
                $query->where('total', '>=', $data['total_min']);
            }
            if (!empty($data['total_max'])) {
                $query->where('total', '<=', $data['total_max']);
            }

            // Tri
            $sortBy  = $data['sort_by']  ?? 'created_at';
            $sortDir = $data['sort_dir'] ?? 'desc';
            $query->orderBy($sortBy, $sortDir);

            // Pagination
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

            $payload = [
                'items' => $items->values(),
                'meta'  => [
                    'current_page' => $paginator->currentPage(),
                    'per_page'     => $paginator->perPage(),
                    'total'        => $paginator->total(),
                    'last_page'    => $paginator->lastPage(),
                ],
            ];

            return $this->responseJson(true, 'Liste des transferts récupérée avec succès.', $payload);
        } catch (Exception $e) {
            return $this->responseJson(false, 'Erreur lors de la récupération des transferts.', $e->getMessage(), 500);
        }
    }

   /**
     * GET /api/transferts/{id}
     * Affiche un transfert par son ID.
     * - Masque le champ "code" si le transfert est "en_cours"
     *   et que l'utilisateur n'est ni l'expéditeur ni un Admin.
     */
    public function show(Request $request, int $id)
    {
        $transfert = Transfert::with([
            'beneficiaire',
            'expediteur',
            'deviseSource',
            'deviseCible',
            'tauxEchange',
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

    /**
     * GET /api/transferts/code/{code}
     * Affiche un transfert par son code.
     * - Ici, on considère que si l'appelant connaît le code, on ne le masque pas.
     */
    public function showByCode(Request $request, string $code)
    {
        $transfert = Transfert::with([
            'beneficiaire',
            'expediteur',
            'deviseSource',
            'deviseCible',
            'tauxEchange',
        ])->where('code', $code)->first();

        if (!$transfert) {
            return $this->responseJson(false, 'Transfert introuvable pour ce code.', null, 404);
        }

        return $this->responseJson(true, 'Transfert récupéré avec succès.', $transfert);
    }

    /**
     * GET /api/transferts/by-user
     * Liste paginée des transferts de l'utilisateur connecté (expéditeur).
     * Filtres disponibles (query string) :
     * - search          : string (code, nom/prénom bénéficiaire, téléphone)
     * - statut          : en_cours|retire|annule  (ou liste: statut[]=en_cours&statut[]=retire)
     * - beneficiaire_id : int
     * - date_from       : YYYY-MM-DD
     * - date_to         : YYYY-MM-DD
     * - montant_min     : number (EUR)
     * - montant_max     : number (EUR)
     * - total_min       : number (EUR)
     * - total_max       : number (EUR)
     * - sort_by         : created_at|montant_euro|total|code
     * - sort_dir        : asc|desc
     * - page, per_page  : pagination
     */
    public function byUser(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return $this->responseJson(false, 'Non authentifié.', null, 401);
            }

            // Validation légère des filtres (NOTE: pas de user_id ici)
            $data = $request->validate([
                'search'          => 'nullable|string|max:100',
                'statut'          => ['nullable'], // string ou array
                'beneficiaire_id' => 'nullable|integer',
                'date_from'       => 'nullable|date_format:Y-m-d',
                'date_to'         => 'nullable|date_format:Y-m-d',
                'montant_min'     => 'nullable|numeric',
                'montant_max'     => 'nullable|numeric',
                'total_min'       => 'nullable|numeric',
                'total_max'       => 'nullable|numeric',
                'sort_by'         => ['nullable', Rule::in(['created_at','montant_euro','total','code'])],
                'sort_dir'        => ['nullable', Rule::in(['asc','desc'])],
                'per_page'        => 'nullable|integer|min:1|max:200',
                'page'            => 'nullable|integer|min:1',
            ]);

            $query = Transfert::query()
                ->with(['beneficiaire','expediteur','deviseSource','deviseCible','tauxEchange'])
                // Force l’isolation par propriétaire
                ->where('user_id', $user->id);

            // Recherche plein-texte simple
            if (!empty($data['search'])) {
                $s = trim($data['search']);
                $query->where(function ($q) use ($s) {
                    $q->where('code', 'like', "%{$s}%")
                      ->orWhereHas('beneficiaire', function ($qb) use ($s) {
                          $qb->where('nom', 'like', "%{$s}%")
                             ->orWhere('prenom', 'like', "%{$s}%")
                             ->orWhere('nom_complet', 'like', "%{$s}%")
                             ->orWhere('phone', 'like', "%{$s}%");
                      });
                });
            }

            // Statuts (string ou array)
            if (!empty($data['statut'])) {
                $statuts = is_array($data['statut']) ? $data['statut'] : [$data['statut']];
                $query->whereIn('statut', $statuts);
            }

            // Filtres directs
            if (!empty($data['beneficiaire_id'])) {
                $query->where('beneficiaire_id', $data['beneficiaire_id']);
            }

            // Fenêtre de dates (sur created_at)
            if (!empty($data['date_from'])) {
                $query->whereDate('created_at', '>=', $data['date_from']);
            }
            if (!empty($data['date_to'])) {
                $query->whereDate('created_at', '<=', $data['date_to']);
            }

            // Plages de montants (EUR)
            if (!empty($data['montant_min'])) {
                $query->where('montant_euro', '>=', $data['montant_min']);
            }
            if (!empty($data['montant_max'])) {
                $query->where('montant_euro', '<=', $data['montant_max']);
            }

            // Plages de total débité (EUR)
            if (!empty($data['total_min'])) {
                $query->where('total', '>=', $data['total_min']);
            }
            if (!empty($data['total_max'])) {
                $query->where('total', '<=', $data['total_max']);
            }

            // Tri
            $sortBy  = $data['sort_by']  ?? 'created_at';
            $sortDir = $data['sort_dir'] ?? 'desc';
            $query->orderBy($sortBy, $sortDir);

            // Pagination
            $perPage   = $data['per_page'] ?? 15;
            $page      = $data['page'] ?? null;
            $paginator = $query->paginate($perPage, ['*'], 'page', $page);

            // ⚠️ Ici on NE masque PAS le code : l’utilisateur est le propriétaire
            $payload = [
                'items' => $paginator->getCollection()->values(),
                'meta'  => [
                    'current_page' => $paginator->currentPage(),
                    'per_page'     => $paginator->perPage(),
                    'total'        => $paginator->total(),
                    'last_page'    => $paginator->lastPage(),
                ],
            ];

            return $this->responseJson(true, 'Liste des transferts (moi) récupérée avec succès.', $payload);
        } catch (Exception $e) {
            return $this->responseJson(false, 'Erreur lors de la récupération des transferts (moi).', $e->getMessage(), 500);
        }
    }
}
