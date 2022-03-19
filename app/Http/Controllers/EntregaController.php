<?php

namespace App\Http\Controllers;
use Automattic\WooCommerce\Client;
use Illuminate\Http\Request;
use Response;
class EntregaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $woocommerce = new Client(env('API_WOOCOMMERCE_URL'), env('API_WOOCOMMERCE_CLIENT'), env('API_WOOCOMMERCE_PASSWORD'),
            [
                'wp_api' => true,
                'version' => 'wc/v3',
            ]
        );

        $orders1 = $woocommerce->get('orders', $parameters= ['status' => 'processing,completed']);

        $data = array_map(function ($length) {
            $place = ['id' => $length->id,
                      'estado' => $length->status,
                      'envia' => [
                         'primer_nombre' =>  $length->billing->first_name,
                         'segundo_nombre' => $length->billing->last_name,
                         'direccion' => $length->billing->address_1.", ".$length->billing->city." (". $length->billing->address_2 .")",
                         'correo' => $length->billing->email,
                         'telefono' => $length->billing->phone,
                      ]];
            return $place;
        }, $orders1);


        
                  
        return Response::json(
            array('success' => true,
                  'data' => $data),200
        );

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
