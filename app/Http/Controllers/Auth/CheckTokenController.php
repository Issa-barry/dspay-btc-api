<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class CheckTokenController extends Controller
{
    use JsonResponseTrait;

    public function __invoke(Request $request)
    {
        $header = $request->header('Authorization');
        if (!$header) {
            return $this->responseJson(false, "Token manquant dans l'en-tête.", null, 422);
        }

        $token = str_starts_with($header, 'Bearer ') ? substr($header, 7) : $header;
        $exists = PersonalAccessToken::where('token', hash('sha256', $token))->exists();

        return $exists
            ? $this->responseJson(true, 'Token valide.')
            : $this->responseJson(false, 'Token invalide ou inexistant.', null, 404);
    }
}
