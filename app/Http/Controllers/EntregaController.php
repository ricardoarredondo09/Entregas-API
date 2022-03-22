<?php

namespace App\Http\Controllers;
use Automattic\WooCommerce\Client;
use Illuminate\Http\Request;
use Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\DB;
use App\Mail\NotificarEntrega;
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

        $request->request->add(['image' => $request->image->store('')]);
        Mail::to("ricard.arredondo@gmail.com")->send(new NotificarEntrega($request));

        return Response::json(
            array('success' => true,
                'data' =>"s"),200
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
