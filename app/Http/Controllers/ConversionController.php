<?php
namespace App\Http\Controllers;

use App\Models\Conversion;
use App\Models\Devise;
use App\Models\TauxEchange;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class ConversionController extends Controller
{
    /**
     * Fonction pour centraliser les réponses JSON
     */
    protected function responseJson($success, $message, $data = null, $statusCode = 200)
    {
        return response()->json([
            'success' => $success,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    /** 
     * Créer une conversion de devise.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validation des données d'entrée
        $validated = Validator::make($request->all(), [
            'devise_source_id' => 'required|exists:devises,id',
            'devise_cible_id' => 'required|exists:devises,id',
            'montant' => 'required|numeric|min:0',
        ]);

        if ($validated->fails()) {
            return $this->responseJson(false, 'Validation échouée.', $validated->errors(), 422);
        }

        try {
            // Vérifier l'existence du taux de change pour les devises source et cible
            $tauxEchange = TauxEchange::where('devise_source_id', $request->devise_source_id)
                ->where('devise_cible_id', $request->devise_cible_id)
                ->first();

            if (!$tauxEchange) {
                return $this->responseJson(false, 'Taux d\'échange introuvable pour les devises spécifiées.', null, 404);
            }

            // Calcul du montant converti
            $montantConverti = $request->montant * $tauxEchange->taux;

            // Créer la conversion en base de données
            $conversion = Conversion::create([
                'devise_source_id' => $request->devise_source_id, 
                'devise_cible_id' => $request->devise_cible_id,
                'montant_source' => $request->montant,
                'montant_converti' => $montantConverti,
                'taux' => $tauxEchange->taux,
            ]);

            return $this->responseJson(true, 'Conversion effectuée avec succès.', $conversion, 201);
        } catch (Exception $e) {
            // Gestion des erreurs lors de la création de la conversion
            return $this->responseJson(false, 'Une erreur est survenue lors de la création de la conversion.', $e->getMessage(), 500);
        }
    }

    /**
     * Afficher toutes les conversions.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $conversions = Conversion::with(['deviseSource', 'deviseCible'])->get();
            return $this->responseJson(true, 'Liste des conversions récupérée avec succès.', $conversions);
        } catch (Exception $e) {
            return $this->responseJson(false, 'Erreur lors de la récupération des conversions.', $e->getMessage(), 500);
        }
    }

    /**
     * Afficher une conversion spécifique.
     *
     * @param  \App\Models\Conversion  $conversion
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $conversion = Conversion::with(['deviseSource', 'deviseCible'])->find($id);

            if (!$conversion) {
                return $this->responseJson(false, 'Conversion non trouvée.', null, 404);
            }

            return $this->responseJson(true, 'Conversion récupérée avec succès.', $conversion);
        } catch (Exception $e) {
            return $this->responseJson(false, 'Erreur lors de la récupération de la conversion.', $e->getMessage(), 500);
        }
    }

    /**
     * Mettre à jour une conversion existante.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Conversion  $conversion
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $conversion = Conversion::find($id);

            if (!$conversion) {
                return $this->responseJson(false, 'Conversion non trouvée.', null, 404);
            }

            // Validation des données d'entrée
            $validated = Validator::make($request->all(), [
                'devise_source_id' => 'required|exists:devises,id',
                'devise_cible_id' => 'required|exists:devises,id',
                'montant' => 'required|numeric|min:0',
            ]);

            if ($validated->fails()) {
                return $this->responseJson(false, 'Validation échouée.', $validated->errors(), 422);
            }

            // Récupérer le taux de change
            $tauxEchange = TauxEchange::where('devise_source_id', $request->devise_source_id)
                ->where('devise_cible_id', $request->devise_cible_id)
                ->first();

            if (!$tauxEchange) {
                return $this->responseJson(false, 'Taux d\'échange introuvable pour ces devises.', null, 404);
            }

            // Calcul du montant converti
            $montantConverti = $request->montant * $tauxEchange->taux;

            // Mise à jour de la conversion
            $conversion->update([
                'devise_source_id' => $request->devise_source_id,
                'devise_cible_id' => $request->devise_cible_id,
                'montant_source' => $request->montant,
                'montant_converti' => $montantConverti,
                'taux' => $tauxEchange->taux,
            ]);

            return $this->responseJson(true, 'Conversion mise à jour avec succès.', $conversion);
        } catch (Exception $e) {
            return $this->responseJson(false, 'Erreur lors de la mise à jour de la conversion.', $e->getMessage(), 500);
        }
    }

    /**
     * Supprimer une conversion existante.
     *
     * @param  \App\Models\Conversion  $conversion
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $conversion = Conversion::find($id);

            if (!$conversion) {
                return $this->responseJson(false, 'Conversion non trouvée.', null, 404);
            }

            $conversion->delete();

            return $this->responseJson(true, 'Conversion supprimée avec succès.');
        } catch (Exception $e) {
            return $this->responseJson(false, 'Erreur lors de la suppression de la conversion.', $e->getMessage(), 500);
        }
    }
}
