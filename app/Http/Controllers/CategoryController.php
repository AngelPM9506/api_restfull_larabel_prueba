<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Nette\Utils\Json;

class CategoryController extends Controller
{
    public function __construct()
    {
        /**solicitar la utenticacion simpre para los metos, exceptuendo losmetodos 
         * index y show
         */
        $this->middleware('api.auth', [
            'except' => [
                'index', 'show'
            ]
        ]);
    }
    public function pruebas(Request $request)
    {
        return "Acción de pruebas de Category Controller";
    }
    public function index()
    {
        $categories = Category::all();
        return response()->json([
            'code'       => 200,
            'status'     => 'success',
            'categories' => $categories,
        ], 200);
    }
    public function show($id)
    {
        $category = Category::find($id);
        if (!is_null($category)) {
            $data = [
                'code'       => 200,
                'status'     => 'success',
                'category'   => $category,
            ];
        } else {
            $data = [
                'code'       => 404,
                'status'     => 'error',
                'message'    => 'Categoria No encontrada o no valida',
            ];
        }
        return response()->json($data, $data['code']);
    }
    public function store(Request $request)
    {
        /**obtener los datos por post */
        $json = $request->input('json', null);
        $params_arry = json_decode($json, true);
        if (!empty($params_arry)) {
            /**validar los datos */
            $validate = Validator::make($params_arry, [
                'name' => 'required'
            ]);
            /**Guardar la categoria */
            if ($validate->fails()) {
                $data = [
                    'code'       => 400,
                    'status'     => 'error',
                    'message'    => 'La categoria no se guardo correctamente',
                    'error'      => $validate->errors()
                ];
            } else {
                $params_arry = array_map('trim', $params_arry);
                $category = new Category($params_arry);
                if ($category->save()) {
                    $data = [
                        'code'       => 200,
                        'status'     => 'success',
                        'category'   => $category
                    ];
                } else {
                    $data = [
                        'code'       => 404,
                        'status'     => 'error',
                        'message'   => 'Error al guardar la categoria'
                    ];
                }
            }
        } else {
            $data = [
                'code'       => 404,
                'status'     => 'error',
                'message'   => 'Llena correctamente el formulario'
            ];
        }
        /**regresar el resultado */
        return response()->json($data, $data['code']);
    }
    public function update($id, Request $request)
    {
        /**obtener los datos por post */
        $json = $request->input('json', null);
        $params_arry = json_decode($json, true);
        /**verificar que los parametros no esten vacios */
        if (!is_null($params_arry)) {
            /**Validar si es correcta la finformacion del formulario */
            $validate = Validator::make($params_arry, ['name' => 'required']);
            if (!$validate->fails()) {
                /**aliminar los datos que no se desean si llegaran a tenerse */
                unset($params_arry['id']);
                unset($params_arry['created_at']);
                /**corroborar que exista la categoria que se quiere actualizar */
                /**buscar la categoria */
                $category = Category::find($id);
                if (!is_null($category)) {
                    /**si no es nulo guardar la actualizacion */
                    $params_arry = array_map('trim', $params_arry);
                    if ($category->update($params_arry)) {
                        $data = [
                            'code'    => 200,
                            'status'  => 'success',
                            'category'  => $category
                        ];
                    } else {
                        $data = [
                            'code'    => 404,
                            'status'  => 'error',
                            'message'   => 'Categoria no actualizada intenta de nuevo'
                        ];
                    }
                } else {
                    $data = [
                        'code'      => 404,
                        'status'    => 'error',
                        'message'   => 'Categoria no encontrada'
                    ];
                }
            } else {
                $data = [
                    'code'      => 404,
                    'status'    => 'error',
                    'message'   => 'Llena correctamente el formulario'
                ];
            }
        } else {
            $data = [
                'code'      => 404,
                'status'    => 'error',
                'message'   => 'Llena correctamente el formulario'
            ];
        }
        /**Regresar la respuesta */
        return response()->json($data, $data['code']);
    }
    public function destroy($id, Request $request)
    {
        $category = Category::find($id);
        if (!is_null($category)) {
            $resultado = $category->delete();
            if ($resultado) {
                $data = [
                    'code'      => 200,
                    'status'    => 'success',
                    'message'   => 'Categoria eliminada correctamente',
                    'resultado' => $resultado
                ];
            }

        }else{
            $data = [
                'code'      => 404,
                'status'    => 'error',
                'message'   => 'Llena correctamente el formulario'
            ];
        }
        return response()->json($data, $data['code']);
    }
}
