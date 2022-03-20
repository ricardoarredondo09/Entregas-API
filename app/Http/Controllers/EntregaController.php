<?php

namespace App\Http\Controllers;
use Automattic\WooCommerce\Client;
use Illuminate\Http\Request;
use Response;
use Tymon\JWTAuth\Facades\JWTAuth;
class EntregaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = JWTAuth::user();
        $woocommerce = new Client(env('API_WOOCOMMERCE_URL'), env('API_WOOCOMMERCE_CLIENT'), env('API_WOOCOMMERCE_PASSWORD'),
            [
                'wp_api' => true,
                'version' => 'wc/v3',
            ]
        );
        if($user->privilegio_id == 1){
            $getOrdenesDeCompra = $woocommerce->get('orders', $parameters= ['status' => 'processing,completed']);
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

            return Response::json(
                array('success' => true,
                    'data' => $data),200
            );
        }else{
            return Response::json(
                array('success' => false,
                      'data' => "Usuario Sin Privilegios"),400
            );
        }

        

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
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
