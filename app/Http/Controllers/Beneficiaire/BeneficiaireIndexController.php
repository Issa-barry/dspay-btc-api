<?php

namespace App\Http\Controllers\Beneficiaire;

use App\Http\Controllers\Controller;
use App\Models\Beneficiaire;
use App\Traits\JsonResponseTrait;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BeneficiaireIndexController extends Controller
{
    use JsonResponseTrait;

    public function index(Request $r)
    {
        try {
            $r->validate([
                'search'   => 'nullable|string|max:100',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $q = Beneficiaire::query()
                ->where('user_id', $r->user()->id);

            if ($search = $r->string('search')->toString()) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('nom', 'like', "%{$search}%")
                       ->orWhere('prenom', 'like', "%{$search}%")
                       ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            $perPage  = (int)($r->per_page ?? 10);
            $page     = $q->orderByDesc('id')->paginate($perPage);

            return $this->responseJson(true, 'Liste des bénéficiaires.', [
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
        } catch (\Throwable $e) {
            return $this->responseJson(false, 'Erreur lors de la récupération des bénéficiaires.', $e->getMessage(), 500);
        }
    }

    public function getById(Request $r, $id)
    {
        try {
            $benef = Beneficiaire::where('user_id', $r->user()->id)->findOrFail($id);

            return $this->responseJson(true, 'Détails du bénéficiaire.', $benef);
        } catch (ModelNotFoundException $e) {
            return $this->responseJson(false, 'Bénéficiaire introuvable.', null, 404);
        } catch (\Throwable $e) {
            return $this->responseJson(false, 'Erreur lors de la récupération du bénéficiaire.', $e->getMessage(), 500);
        }
    }
}
