<?php

namespace App\Http\Controllers\Agence;

use App\Http\Controllers\Controller;
use App\Models\Agence;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class AgenceIndexController extends Controller
{
    use JsonResponseTrait;

    public function index(Request $r)
    {
        try {
            // ✅ Validation des paramètres d’entrée
            $r->validate([
                'search'   => 'nullable|string|max:100',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            // ✅ Alias rétro-compatibilité: accepter aussi ?q=
            $search = $r->filled('search') ? $r->string('search')->toString()
                                           : $r->string('q')->toString();

            // Base de la requête
            $q = Agence::query();

            // 👉 si multi-tenant, décommente:
            // $q->where('user_id', $r->user()->id);

            // 🔎 Filtre de recherche
            if (!empty($search)) {
                $q->where(function ($qq) use ($search) {
                    $like = "%{$search}%";
                    $qq->where('nom', 'like', $like)
                       ->orWhere('phone', 'like', $like)
                       ->orWhere('email', 'like', $like)
                       ->orWhere('ville', 'like', $like)
                       ->orWhere('quartier', 'like', $like)
                       ->orWhere('reference', 'like', $like);
                });
            }

            $perPage = (int) ($r->per_page ?? 10);
            $page    = $q->orderByDesc('id')->paginate($perPage);

            // ✅ Réponse uniformisée { items, meta }
            return $this->responseJson(true, 'Liste des agences.', [
                'items' => $page->items(),
                'meta'  => [
                    'total'        => $page->total(),
                    'per_page'     => $page->perPage(),
                    'current_page' => $page->currentPage(),
                    'last_page'    => $page->lastPage(),
                ],
            ]);

        } catch (ValidationException $e) {
            return $this->responseJson(false, 'Échec de la validation des paramètres.', $e->errors(), 422);
        } catch (QueryException $e) {
            return $this->responseJson(false, 'Erreur de base de données.', $e->getMessage(), 500);
        } catch (\Throwable $e) {
            return $this->responseJson(false, 'Erreur lors de la récupération des agences.', $e->getMessage(), 500);
        }
    }
}
