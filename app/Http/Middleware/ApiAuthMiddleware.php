<?php

namespace App\Http\Middleware;

use Closure;
use App\Helpers\JwtAuth;
use Illuminate\Http\Request;

class ApiAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        /**Comprobar que el usuario esta autenticado */
        $token = $request->header('Authorization');
        $jwtAuth = new JwtAuth();
        $checkToken = $jwtAuth->checkToken($token);
        if ($checkToken) {
            return $next($request);
        }else{
            /**mensaje de error */
            $data = [
                'code'    => 400,
                'status'  => 'error',
                'message' => 'Error de identificación',
            ];
            return response()->json($data, $data['code']);
        }
    }
}
