<?php

namespace App\Http\Controllers\Agence;

use App\Http\Controllers\Controller;
use App\Models\Agence;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Throwable;

class AgenceIndexController extends Controller
{
    use JsonResponseTrait;

    public function index(Request $request)
    {
        try {
            // Pagination
            $perPage = (int) $request->query('per_page', 15);
            if ($perPage <= 0) {
                $perPage = 15;
            }

            $query = Agence::query()->orderByDesc('id');

            // Recherche facultative
            if ($search = trim($request->query('q', ''))) {
                $query->where(function ($q) use ($search) {
                    $q->where('nom', 'like', "%$search%")
                        ->orWhere('phone', 'like', "%$search%")
                        ->orWhere('email', 'like', "%$search%")
                        ->orWhere('ville', 'like', "%$search%")
                        ->orWhere('quartier', 'like', "%$search%")
                        ->orWhere('reference', 'like', "%$search%");
                });
            }

            $agences = $query->paginate($perPage);

            return $this->responseJson(true, 'Liste des agences récupérée avec succès.', $agences);

        } catch (QueryException $e) {
            // Erreur SQL (ex : table inexistante, colonne inconnue)
            return $this->responseJson(false, 'Erreur de base de données.', $e->getMessage(), 500);
        } catch (Throwable $e) {
            // Autres erreurs (PHP, logique, etc.)
            return $this->responseJson(false, 'Une erreur interne est survenue lors de la récupération des agences.', $e->getMessage(), 500);
        }
    }
}
