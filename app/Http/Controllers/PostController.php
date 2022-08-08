<?php

namespace App\Http\Controllers;

use Whoops\Run;
use App\Models\Post;
use App\Helpers\JwtAuth;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PostController extends Controller
{
    public function __construct()
    {
        $this->middleware('api.auth', [
            'except' => [
                'index', 'show', 'getImage', 'getPostsByCategory', 'getPostsByUser'
            ]
        ]);
    }
    public function index(Request $request)
    {
        $posts = Post::all()->load('category', 'user');
        $data = [
            'code' => 200,
            'status' => 'success',
            'posts'  => $posts
        ];
        return response()->json($data, $data['code']);
    }
    public function store(Request $request)
    {
        /**obtener datos por post */
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_arry = json_decode($json, true);
        if (!empty($params_arry)) {
            /**conseguir el usuario identificado */
            $jwtAuth = new JwtAuth();
            $token = $request->header('Authorization', null);
            $user = $jwtAuth->checkToken($token, true);
            /**validar los datos */
            $validate = Validator::make($params_arry, [
                'title' => 'required',
                'content' => 'required',
                'category_id' => 'required',
            ]);
            if (!$validate->fails()) {
                $params_arry = array_map('trim', $params_arry);
                $post = new Post($params_arry);
                $post->user_id = $user->sub;
                if ($post->save()) {
                    $data = [
                        'code' => 200,
                        'status' => 'succes',
                        'post' => $post
                    ];
                } else {
                    $data = [
                        'code' => 404,
                        'status' => 'error',
                        'message' => 'Post no guardado intenta de nuevo',
                    ];
                }
            } else {
                $data = [
                    'code' => 404,
                    'status' => 'error',
                    'message' => 'Datos invalidos intenta de nuevo',
                    'error'      => $validate->errors()
                ];
            }
        } else {
            $data = [
                'code' => 404,
                'status' => 'error',
                'message' => 'Datos invalidos intenta de nuevo',
            ];
        }
        /**regresar la respuesta */
        return response()->json($data, $data['code']);
    }
    public function show($id, Request $request)
    {
        $post = Post::find($id);
        if (!is_null($post)) {
            $post = $post->load('category', 'user');
            $data = [
                'code' => 200,
                'status' => 'success',
                'post' => $post
            ];
        } else {
            $data = [
                'code' => 404,
                'status' => 'error',
                'message' => 'Entrada no encontrada'
            ];
        }
        return response()->json($data, $data['code']);
    }
    public function update($id, Request $request)
    {
        /**obtener datos del post */
        $json = $request->input('json', null);
        $params = json_decode($json, true);
        if (!is_null($params)) {
            /**validar datos */
            $validate = Validator::make($params, [
                'title' => 'required',
                'content' => 'required',
                'category_id' => 'required',
            ]);
            if (!$validate->fails()) {
                /**buscar entra a actualizar */
                $post = Post::find($id);
                if (!is_null($post)) {
                    /**Eliminar datos que si estan no deberian ir */
                    unset($params['id']);
                    unset($params['user_id']);
                    unset($params['created_at']);
                    unset($params['user']);
                    $params = array_map('trim', $params);
                    /**Verificar que el usuario que creo la entrada sea el unico que la pueda modificar */
                    if (self::isAutor($post->user_id, $request)) {
                        /**guardar datos actualizados */
                        if ($post->update($params)) {
                            $data = [
                                'code'  => 200,
                                'status' => 'success',
                                'post' => $post
                            ];
                        } else {
                            $data = [
                                'code'  => 404,
                                'status' => 'error',
                                'message' => 'Entrada no se actualizo correctamente'
                            ];
                        }
                    } else {
                        $data = [
                            'code'  => 404,
                            'status' => 'error',
                            'message' => 'No se actualizo la entrada, ya que no eres el autor'
                        ];
                    }
                } else {
                    $data = [
                        'code'  => 404,
                        'status' => 'error',
                        'message' => 'Entrada no encontrada'
                    ];
                }
            } else {
                $data = [
                    'code'  => 404,
                    'status' => 'error',
                    'message' => 'Datos incompletos intenta de nuevo'
                ];
            }
        } else {
            $data = [
                'code'  => 404,
                'status' => 'error',
                'message' => 'Datos no validos intenta de nuevo'
            ];
        }
        /**regresar resultados */
        return response()->json($data, $data['code']);
    }
    public function destroy($id, Request $request)
    {
        /**existe la entrada */
        $post = Post::find($id);
        if (!is_null($post)) {
            /**confirmar que el usuario autenticado sea elmismo que creo la entrrada */
            if (self::isAutor($post->user_id, $request)) {
                /**bosrrar entrada */
                if ($post->delete()) {
                    $data = [
                        'code' => 200,
                        'satus' => 'success',
                        'message' => 'Entrada eliminada correctamente',
                        'post' => $post
                    ];
                } else {
                    $data = [
                        'code' => 404,
                        'satus' => 'error',
                        'message' => 'No se elimino la entrada correctamente intenta de nuevo'
                    ];
                }
            } else {
                $data = [
                    'code' => 404,
                    'satus' => 'error',
                    'message' => 'No se elimino la entrada, ya que no eres el autor'
                ];
            }
        } else {
            $data = [
                'code' => 404,
                'satus' => 'error',
                'message' => 'Entrada no encontrada'
            ];
        }
        /**Regresar respuesta */
        return response()->json($data, $data['code']);
    }
    public function uploadImage($id, Request $request)
    {
        /**verificar que sea el autor */
        $post = Post::find($id);
        $last_image = $post->image;
        if (self::isAutor($post->user_id, $request)) {
            /**obtner la imagen de la peticion */
            $image = $request->file('file0', null);
            /**validar imagne */
            $validate = Validator::make($request->all(), [
                'file0' => 'required|image|mimes:jpg,jpeg,png,gif,svg,webp'
            ]);
            /**verificar que no se tengan errores */
            if (is_null($image) || $validate->fails()) {
                $data=[
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'Es necesario una imagen valida',
                    'image' => $image,
                    'errores' => $validate->errors()
                ];
            } else {
                /**Generar un nuevo nombre */
                $extencion = '.'.$image->getClientOriginalExtension();
                $image_name = md5(uniqid(rand(),true)).$extencion;
                /**guardar imagen en el disco */
                if (Storage::disk('image_posts')->put($image_name, File::get($image))) {
                    /**eliminar imagen anterior */
                    Storage::disk('image_posts')->delete($last_image);
                    /**actualizar la imagen en la base de datos */
                    $post->image = $image_name;
                    $post->update();
                    $data=[
                        'code' => 200,
                        'status' => 'success',
                        'message' => 'si paso la validacion',
                        'post'  => $post
                    ];
                } else {
                    $data=[
                        'code' => 505,
                        'status' => 'error',
                        'message' => 'No se logro actualizar el post'
                    ];
                }                
            }
        }else{
            $data=[
                'code' => 400,
                'status' => 'error',
                'message' => 'no es el autor'
            ];
        }
        /**regresar elnombre de la imagne para gardarlo en la base de datos*/
        return response()->json($data, $data['code']);
    }
    public function getImage($id, Request $request)
    {
        $post = Post::find($id) ?? null;
        if (!is_null($post)) {
            if (Storage::disk('image_posts')->exists($post->image)) {
                $file = Storage::disk('image_posts')->get($post->image);
                return new Response($file, 200);
            }else{
                $data = [
                    'code' => 404,
                    'status' => 'error',
                    'message' => 'imagen no encontrada'
                ];
            }
        } else {
            $data = [
                'code' => 404,
                'status' => 'error',
                'message' => 'Entrada no encontrada, intenta de nuevo'
            ];
        }
        return response()->json($data, $data['code']);
    }
    public function getPostsByCategory($id)
    {
        $posts = Post::where('category_id', $id)->get() ?? null;
            $data = [
                'code' => 200,
                'status' => 'succes',
                'posts' => $posts
            ];
        return response()->json($data, $data['code']);
    }
    public function getPostsByUser($id)
    {
        $posts = Post::where('user_id', $id)->get() ?? null;
            $data = [
                'code' => 200,
                'status' => 'succes',
                'posts' => $posts
            ];
        return response()->json($data, $data['code']);
    }
    private function isAutor($postUserId, $request): bool
    {
        $jwtAuth = new JwtAuth();
        $token = $request->header('Authorization', null);
        $user = $jwtAuth->checkToken($token, true);
        if ($user->sub === $postUserId) {
            $resultado = true;
        } else {
            $resultado = false;
        }
        return $resultado;
    }
}
