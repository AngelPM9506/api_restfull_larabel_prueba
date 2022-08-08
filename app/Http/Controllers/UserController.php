<?php

namespace App\Http\Controllers;

use App\Models\User;
use Nette\Utils\Json;
use App\Helpers\JwtAuth;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function pruebas(Request $request){
        return "Acción de pruebas de User Controller";
    }
    public function register(Request $request){
        /**Obtener lo datos del usuario por post */
        $json = $request->input('json') ?? null;
        /**decodificacion de datos */
        $params = json_decode($json);
        /**objeto */
        $params_array = json_decode($json, true);
        /**array */
        if (!empty($params_array) && !empty($json)) {
            /**Limpiar de espacios adelante y atras el array con trim*/
            $params_array = array_map('trim', $params_array);
            /**Validar datos */
            $validate = Validator::make($params_array, [
                'name'      => 'required',
                'surname'   => 'required',
                'email'     => 'required|email|unique:users',
                'password'  => 'required',
            ]);
            /**La condicional unique:users, cuenta como validacion de que no existe el usuario en la base de datos */
            if ($validate->fails()) {
                /**La validación a fallado */
                $data = [
                    'status' => 'error',
                    'code'   =>  400,
                    'message' => 'El usuario no se ha creado correctamente',
                    'errors' => $validate->errors()
                ];
            } else {
                /**Validación pasada correctamente*/
                /**cifrar la contraseña */
                $pwd = password_hash($params->password, PASSWORD_BCRYPT, ['cost' => 5]);
                /**Crear el usuario */
                unset($params_array['id']);
                $user = new User($params_array);
                $user->role = 'ROLE_USER';
                $user->password = $pwd;
                /**Guardar el usuario */
                $user->save();
                /**Regresar mensaje de resultado */
                $data = [
                    'status' => 'success',
                    'code'   =>  202,
                    'message' => 'El usuario se ha creado correctamente',
                    'user'   => $user
                ];
            }
        } else {

            $data = [
                'status' => 'error',
                'code'   =>  400,
                'message' => 'Los datos enviados no son correctos'
            ];
        }
        return response()->json($data, $data['code']);
    }
    public function login(Request $request){
        $jwtAuth = new JwtAuth();
        /**Recibir datos por post */
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);
        /**Validar datos */
        $validate = Validator::make($params_array, [
            'email'     => 'required|email',
            'password'  => 'required',
        ]);
        /**La condicional unique:users, cuenta como validacion de que no existe el usuario en la base de datos */
        if ($validate->fails()) {
            /**La validación a fallado */
            $signup = [
                'token' => [
                    'status' => 'error',
                    'message' => 'Datos incorrectos de usuario',
                    'errors' => $validate->errors()
                ],
                'code'   =>  404
            ];
        } else {
            /**cifrar contraseña */
            //$pwd = password_hash($params->password, PASSWORD_BCRYPT);
            /**devolver oten o datos */
            $token = $jwtAuth->signup($params->email, $params->password);
            $signup = [
                'token' => $token,
                'code'  => $token['code'] ?? 202
            ];
            if (!empty($params->gettoken)) {
                $token = $jwtAuth->signup($params->email, $params->password, true);
                $signup = [
                    'token' => $token, 
                    'code'  => $token['code'] ?? 202
                ];
            }
        }
        return response()->json($signup['token'], $signup['code']);
        //var_dump($email);
        //$datos = $request->input('datos');
        //return "Acción de login de User Controller Datos: ";
    }
    public function update(Request $request){
        /**Comprobar que el usuario esta autenticado */
        $token = $request->header('Authorization');
        $jwtAuth = new JwtAuth();
        $checkToken = $jwtAuth->checkToken($token);
        /**tomar los datos por post */
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);
        if ($checkToken && !empty($params_array)) {
            /**btener usuario identificado */
            $user = $jwtAuth->checkToken($token, true);
            /**validar los datos */
            $validate = Validator::make($params_array, [
                'name'      => 'required',
                'surname'   => 'required',
                'email'     => [
                    'required',
                    Rule::unique('users')->ignore($user->sub)
                    ]
            ]);
            /**quitar los campos que no quiera actualiza */
            unset($params_array['id']);
            unset($params_array['role']);
            unset($params_array['password']);
            unset($params_array['created_at']);
            unset($params_array['remember_token']);
            if ($validate->fails()) {
                $data = [
                    'code'    => 400,
                    'status'  => 'error',
                    'message' => 'Datos incorrectos, intenta de nuevo.',
                ];
            } else {
                /**actualizar los datos en ls BD */
                $user_updated = User::find($user->sub);
                $resultado = $user_updated->update($params_array);
                $tokenNuevo = $jwtAuth->newToken($token, $user_updated->email);
                /**regresar un resultado */
                $data = [
                    'code'    => 202,
                    'status'  => 'success',
                    'message' => 'Usuario actualizado correctamente.',
                    'token'   => $tokenNuevo,
                    'user'    => $jwtAuth->checkToken($tokenNuevo,true),
                    'changes' => $params_array
                ];
            }            
        } else {
            /**mensaje de error */
            $data = [
                'code'    => 400,
                'status'  => 'error',
                'message' => 'usuario no identificado.',
            ];
        }
        return response()->json($data, $data['code']);
    }

    public function uploadImage(Request $request){
        $jwtAuth = new JwtAuth();
        $token_verified = $jwtAuth->checkToken($request->header('Authorization'), true);
        $user = User::find($token_verified->sub, '*');
        /**obtener imagen anterior */
        $last_image = $user->image;
        /**obtener los datos de la peticion */
        $image = $request->file('file0');
        /**Validacion de imagen */
        $validate = Validator::make($request->all(), [
            'file0' => 'required|image|mimes:jpg,jpeg,png,gif,svg,webp'
        ]);
        $extencion = explode('.', $image->getClientOriginalName());
        /**subir imagen */
        if (!$image || $validate->fails()) {
            $data = [
                'code'    => 505,
                'status'  => 'error',
                'message' => 'Imagen dañada o archivo no valido',
            ];
        } else {
            $image_name = md5(uniqid(rand(), true)) . '.' . $extencion[array_key_last($extencion)];
            if (Storage::disk('image_users')->put($image_name, File::get($image))) {
                /**eliminar imagen anterior */
                Storage::disk('image_users')->delete($last_image);
                /**guardar nueva imagen */
                $user->image = $image_name;
                $user->update();
            }
            $data = [
                'code'   => 200,
                'status' => 'success',
                'iamge'  => $image_name
            ];
        }
        return response()->json($data, $data['code']);
    }
    public function getImage($fileName){
        if (Storage::disk('image_users')->exists($fileName)) {
            $file = Storage::disk('image_users')->get($fileName);
            return new Response($file, 200);
        }else{
            $data = [
                'code'     => 404,
                'status'   => 'error',
                'message'  => 'Imagen no encontrada'
            ];
            return response()->json($data, $data['code']);
        }
    }
    public function profile($id){
        $user = User::find($id);
        if (!is_null($user)) {
            $data = [
                'code'   => 200,
                'status' => 'success',
                'user'   => $user
            ];
        }else{
            $data = [
                'code'      => 404,
                'status'    => 'error',
                'Mmesage'   => 'Ususrio no encontrado'
            ];
        }
        return response()->json($data, $data['code']);
    }
}
