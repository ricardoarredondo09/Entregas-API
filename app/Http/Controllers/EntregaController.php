<?php

namespace App\Http\Controllers;
use Automattic\WooCommerce\Client;
use Illuminate\Http\Request;
use Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\DB;
use App\Mail\NotificarEntrega;
use App\Models\HistorialEntrega;
use Mail;
class EntregaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //Obtener Usuario Autentificado
        $user = JWTAuth::user();

        //Realizar conexion con Wocommerce
        $woocommerce = new Client(env('API_WOOCOMMERCE_URL'), env('API_WOOCOMMERCE_CLIENT'), env('API_WOOCOMMERCE_PASSWORD'),
            [
                'wp_api' => true,
                'version' => 'wc/v3',
            ]
        );

        //Validar Privilegios de usuarios
        if($user->privilegio_id == 1){
            $getOrdenesDeCompra = $woocommerce->get('orders', $parameters= ['status' => 'processing,completed']);
        }else{
            $entregasAsigandas = DB::table('entregas')->where('user_id', $user->id)->pluck('id_pedido')->toArray();

            //Verificar si El usuario tiene pedidos asignados
            if(count($entregasAsigandas) != 0)
            {
                $getOrdenesDeCompra = $woocommerce->get('orders', $parameters= ['status' => 'processing,completed', 'include' => $entregasAsigandas ]); 
            }else{
                //Retornar Mensaje sin Pedidos
                return Response::json(
                    array('success' => false,
                        'data' => [],
                        'mensaje' => 'Sin Pedidos Asignados'),200
                );
            }    
        }

        //Filtar y ordenar datos necesarios
        $data = array_map(function ($length) {
            $place = ['id' => $length->id,
                    'estado' => $length->status,
                    'fecha_entrega' => $length->meta_data[array_search("fecha-de-entrega", array_column($length->meta_data, 'key'), true)]->value,
                    'hora_entrega' => $length->meta_data[array_search("hora-de-entrega", array_column($length->meta_data, 'key'), true)]->value,
                    'envia' => [
                        'nombre' =>  $length->billing->first_name,
                        'apellido' => $length->billing->last_name,
                        'direccion' => $length->billing->address_1.", ".$length->billing->city." (". $length->billing->address_2 .")",
                        'correo' => $length->billing->email,
                        'telefono' => $length->billing->phone,
                    ],
                    'recibe' => [
                        'nombre' => $length->shipping->first_name,
                        'apellido' => $length->shipping->last_name,
                        'comuna' => $length->shipping->state,
                        'direccion' => $length->shipping->address_1.", ".$length->shipping->city." (". $length->shipping->address_2 .")",
                    ]];
            return $place;
        }, $getOrdenesDeCompra);

        //Retornar Pedidos
        return Response::json(
            array('success' => true,
                'data' => $data,
                'mensaje' => 'Pedidos Obtenidos con Exito'),200
        );
    }

 
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //Obtener Usuario Autentificado
        $user = JWTAuth::user();

        //Variables de respuestas
        $success = true;
        $data = [];
        $message = "";
        $codigoRespuesta= 200;

        //Completar Entrega
        $completarEntrega = false;

        //Realizar conexion con Wocommerce
        $woocommerce = new Client(env('API_WOOCOMMERCE_URL'), env('API_WOOCOMMERCE_CLIENT'), env('API_WOOCOMMERCE_PASSWORD'),
            [
                'wp_api' => true,
                'version' => 'wc/v3',
            ]
        );

        //Validar ID
        if($request->id != null){

            //Validar tipo de usuario
            if($user->privilegio_id == 2){
                //Buscar asignacíon de orden por usuario
                $orden = DB::table('entregas')->where('user_id', $user->id)->where('id_pedido', $request->id )->get();
                if (!count($orden)){
                    $success = false;
                    $data = [];
                    $message = "Pedido no se encuentra asignado al usuario";
                    $codigoRespuesta= 401;
                }else{
                    $completarEntrega = true;
                }
            }else if ($user->privilegio_id == 1){
                $completarEntrega = true;
            }
        }else{
            $success = false;
            $data = [];
            $message = "Debe Ingresar Id de la orden";
            $codigoRespuesta= 401;
        }

        //Validar que la orden no se entrego antes
        $getOrdenInHistorial = DB::table('historial_entregas')->where('id_pedido', $request->id)->exists();

        if($completarEntrega && !$getOrdenInHistorial){
            $success = true;
            $data = [];
            $message = "Entrega Completada";
            $codigoRespuesta= 200;

            //Data -> Status
            $data = [
                'status' => 'completed'
            ];

            //Modificar estado de orden
            $orden = $woocommerce->put('orders/'.$request->id, $data);

            $data = ['id' => $orden->id,
            'estado' => $orden->status,
            'fecha_entrega' => $orden->meta_data[array_search("fecha-de-entrega", array_column($orden->meta_data, 'key'), true)]->value,
            'hora_entrega' => $orden->meta_data[array_search("hora-de-entrega", array_column($orden->meta_data, 'key'), true)]->value,
            'envia' => [
                'nombre' =>  $orden->billing->first_name,
                'apellido' => $orden->billing->last_name,
                'direccion' => $orden->billing->address_1.", ".$orden->billing->city." (". $orden->billing->address_2 .")",
                'correo' => $orden->billing->email,
                'telefono' => $orden->billing->phone,
            ],
            'recibe' => [
                'nombre' => $orden->shipping->first_name,
                'apellido' => $orden->shipping->last_name,
                'comuna' => $orden->shipping->state,
                'direccion' => $orden->shipping->address_1.", ".$orden->shipping->city." (". $orden->shipping->address_2 .")",
            ]];

            if($data != []){
                //Guardar imagen
                if($request->image){
                    $request->request->add(['imagen' => $request->image->store('')]);
                }
                
                //Guardar historial de entrega
                $historia = new HistorialEntrega;
                $historia->id_pedido = $request->id;
                $historia->imagen = is_string($request->imagen) ? $request->imagen :'';
                $historia->ip =  $request->ip();
                $historia->user_id =  $user->id;
                $historia->save();


                //Datos Para correo
                $request->request->add(['nombreRemitente' => $data['envia']['nombre']]);
                $request->request->add(['numeroPedido' => $data['id']]);
                $request->request->add(['fecha' => date('d-m-Y')]);
                $success = true;
                $message = "Orden Entregada con Exito";

                //Enviar Correo
                Mail::to($data["envia"]["correo"])->send(new NotificarEntrega($request));
                Mail::to('ventas@desayunosyregalosvalparaiso.com')->send(new NotificarEntrega($request));
            }
            
        }else{
            $success = false;
            $data = [];
            $message = "Esta Orden ya fue entregada";
            $codigoRespuesta= 401;
        }


        return Response::json(
            array('success' => $success,
                  'data' => $data,
                  'message' => $message),$codigoRespuesta
        );
       

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
