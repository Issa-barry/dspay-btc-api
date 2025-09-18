<?php 
// app/Http/Controllers/Beneficiaire/BeneficiaireController.php
namespace App\Http\Controllers\Beneficiaire;

use App\Http\Controllers\Controller;
use App\Models\Beneficiaire;
use App\Traits\JsonResponseTrait;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BeneficiaireDeleteController extends Controller
{
     use JsonResponseTrait;

    public function deleteById(Request $r, $id)
    {
        try {
            $benef = Beneficiaire::where('user_id', $r->user()->id)->findOrFail($id);
            $benef->delete();

            return $this->responseJson(true, 'Bénéficiaire supprimé.', null, 200);
        } catch (ModelNotFoundException $e) {
            return $this->responseJson(false, 'Bénéficiaire introuvable.', null, 404);
        } catch (\Exception $e) {
            return $this->responseJson(false, 'Une erreur est survenue lors de la suppression.', $e->getMessage(), 500);
        }
    }
}
