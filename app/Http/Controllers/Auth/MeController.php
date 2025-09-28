<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\Request;

class MeController extends Controller
{
    use JsonResponseTrait;

    public function __invoke(Request $request)
    {
        $user = $request->user()->makeHidden(['password','remember_token']);
        return $this->responseJson(true, 'Profil récupéré.', $user);
    }
}
