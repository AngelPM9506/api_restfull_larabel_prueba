<?php

namespace App\Helpers;

use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\facades\DB;

use function Psy\debug;

class JwtAuth{
    private $key;
    public function __construct(){
        $this->key = 'Seiyu_25_30_95_62_**_##';
    }
    public function signup($email, $password, $getToken = null){
        /**Buscar si existe el usuario con su credencial */
        /*$user = User::where([
            'email'     =>    $email,
            'password'  =>    $password
        ])->first();*/
        /**Buscar al usuario */
        $user = User::where('email', $email)->first();
        /**Verificar que el usuario exista y que la contraseña sea correcata */
        if (is_object($user) && password_verify($password, $user->password)) {
            /**Si el usuario se identifica correctamente se genera el token */
            $token = [
                'sub'       => $user->id,
                'email'     => $user->email,
                'name'      => $user->name,
                'surname'   => $user->surname,
                'iat'       => time(),
                'exp'       => time() + (30*24*60*60),
            ];
            $jwt = JWT::encode($token, $this->key, 'HS256');
            $decode = JWT::decode($jwt, new Key($this->key, 'HS256'));
            /**regresar los datos decodificados o el token en función de un parametro */
            if (is_null($getToken)) {
                $data = $jwt;
            }else{
                $data = (array) $decode;
            }
        }else{
            /**si la identificacion no es correcta se regresa un error**/
            $data = [
                'status'  => 'error',
                'message' => 'Datos incorrectos, intenta de nuevo',
                'code'    => 404
            ];
        }
        /**retorno del token obtenido */
        return $data;
    }
    public function newToken($jwt, $email)
    {
        if (self::checkToken($jwt)) {
            $user = User::where('email', $email)->first();
            $token = [
                'sub'       => $user->id,
                'email'     => $user->email,
                'name'      => $user->name,
                'surname'   => $user->surname,
                'iat'       => time(),
                'exp'       => time() + (30*24*60*60),
            ];
            $newJwt = JWT::encode($token, $this->key, 'HS256');
            $data = $newJwt;
        }else{
            $data = [
                'status'  => 'error',
                'message' => 'Datos incorrectos, intenta de nuevo',
                'code'    => 404
            ];
        }
        return $data;
    }
    public function checkToken($jwt, $getIdentity = false){
        $auth = false;
        //$data = [];
        try {
            $jwt = str_replace('"', '', $jwt);
            $decode = JWT::decode($jwt, new Key($this->key, 'HS256'));
        } catch (\Throwable $e ) {
            /*$data = [
                'status'  => 'error',
                'code'    => 404,
                'message' => 'Error de autenticacion',
                'error'   => $e->getMessage()
            ];
            echo json_encode($data);*/
            $auth = false;
        }

        if (!empty($decode) && is_object($decode) && isset($decode->sub)) {
            $auth = true;
            if ($getIdentity) {
                return $decode;
            }
        }else{
            $auth = false;
        }
        return $auth;
    }
}

