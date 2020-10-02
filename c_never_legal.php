<?php
/*
Plugin Name: API Never Legal
Description: Custom code to use with the Datafeedr plugins. Don't delete me!
License: GPL v3

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

#    PAGINA QUE EXPLICA COMO HACER ORDENES
#    https://github.com/woocommerce/woocommerce/wiki/wc_get_orders-and-WC_Order_Query

#PAGINA PARA CONSULTAS DE ORDENES:
#    https://www.themelocation.com/how-to-show-all-orders-on-a-page-in-woocommerce/

#ATRIBUTOS DE UNA ORDEN
#    https://www.businessbloomer.com/woocommerce-easily-get-order-info-total-items-etc-from-order-object/

/** Add your custom code BELOW this line **/

#ESTILOS DEL COMPLEMENTO
//wp_enqueue_style('sw_estilos', plugins_url('/css/sw_estilos.css', __FILE__));
//wp_enqueue_style('sw_estilos');

#JS DEL COMPLEMENTO
//AQUI TENEMOS EL CODIGO QUE ME GUARDARA LA URL EN JS
/*
wp_enqueue_script('sw_script', plugins_url('/js/sw_script.js', __FILE__));
wp_enqueue_script('sw_script_2', plugins_url('/js/pop_up.js', __FILE__));
wp_localize_script('sw_script', 'sw_script', array(
'pluginsUrl' => plugins_url(),
));

wp_enqueue_script('sw_script', plugins_url('/js/sw_script.js', __FILE__), array('jquery'), '1.0', true);
wp_enqueue_script('sw_script_2', plugins_url('/js/pop_up.js', __FILE__), array('jquery'), '1.0', true);
 */

/** Step 2 (from text above). */
add_action('admin_menu', 'menu_never_legal');

/** Step 1. */
function menu_never_legal()
{
    add_menu_page('SW', 'Never Legal', 'manage_options', 'never_legal', 'never_legal_ajustes', 'dashicons-editor-paste-word', '35');
    //add_submenu_page( 'generar-liga', 'Categorias', 'Categorias', 'manage_options', 'mis-categorias', 'borrar_productos_web' );

    //SECCION DE CONFIGURACIONES
    add_submenu_page('never_legal', 'Ajustes', 'Ajustes', 'manage_options', 'mis-ajustes-never-legal', 'ajustes_nv_legal');
}

/** Step 3. */
function never_legal_ajustes()
{
    global $wpdb;

    echo "<h1>Somos los ajustes</h1>";

}

/*
 *
 *    ENVIO DE INFORMACION DE ORDEN A LA API DE SAKS
 *
 */
// define the woocommerce_thankyou callback
function send_api_saks_orders($order_get_id)
{
    // make action magic happen here...
    //echo "<h1>--------------> llegamos a la orden, el id es: {$order_get_id}</h1>";

    $data = armando_mi_variables($order_get_id);

    //echo json_encode($data);//MUESTRA ELL ARREGLO

    if ($data != "no") {
        //ENVIAMOS LA DATA
        $ch2 = curl_init("https://appdesarollo.clau.io/ecommerce_api/v1/enviar_compra");
        curl_setopt_array($ch2, array(
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => array(

                'Content-Type: application/json',
                'apikey: POS-NNKZecND4tThKCuUWG3FZ6yP7TTV6ZemV6eDjBXsbXGA',
            ),
            CURLOPT_POSTFIELDS     => json_encode($data),
        ));

        // Send the request
        $response2 = curl_exec($ch2);

        //echo "<h2>Pasamos!</h2>";
        // Check for errors
        if ($response2 === false) {
            die(curl_error($ch2));
        }

        //echo "<h1>----> LLEGA LA RESPUESTA!</h1>";

        //echo "<br><br><br>";

        //print_r($response2);

        $responseData2 = json_decode($response2, true);
        $respuestas    = json_encode($response2, true);

        echo "Respuesta API: " . $responseData2['msj'];
        //print_r($response2);
    }

}

// add the action
add_action('woocommerce_thankyou', 'send_api_saks_orders', 10, 1);

//send_api_saks_orders(46);

//add_action('wp_footer', 'send_api_saks_orders', 1);
/*
function my_init()
{
$order_id =  < myoderid >
$order    = new WC_Order($order_id);
}
 */

//woocommerce_payment_complete
//add_action('woocommerce_payment_complete', 'send_api_saks_orders', 10, 1);

//ARMANDO MI FORMATO JSON
// add_action("wp_footer", "armando_mi_variables");
function armando_mi_variables($order_get_id)
{
    /*
    id_Ecommerce
    customer_Name
    customer_Last_Name
    total
    total_Tax
    status
     */

    $order_id = $order_get_id;
    //$order_id = 46;

    //echo "<h1>ESTO ES PRIMERO {$order_id}</h1>";

    $order = new WC_Order($order_id);

    //$order->get_total();

    #CONSULTAMOS SI EXISTE ALGUN PRODUCTO PARA ANALIZAR
    $enviar_api = productos_buscados_nl($order);

    //AHORA ASIGNAMOS LOS PRODUCTOS

    if (count($enviar_api) > 0) {
        //echo "<h1>Tenemos " . count($enviar_api) . " Productos Legal!!</h1>";

        $array_productos = array();

        foreach ($order->get_items() as $item_id => $item) {

            //echo "<h1>-----------------------------------------</h1>";

            //print_r($item);

            //echo "<h1>-----------------------------------------</h1>";

            //VALIDAMOS QUE ESTE PRODUCTO SEA DE LOS QUE EXISTEN EN EL ARREGLO
            if (in_array($item->get_product_id(), $enviar_api)) {
                $product_id   = $item->get_product_id();
                $variation_id = $item->get_variation_id();
                $product      = $item->get_product();
                $name         = $item->get_name();
                $quantity     = $item->get_quantity();
                $subtotal     = $item->get_subtotal();
                $total        = $item->get_total();
                $tax          = $item->get_subtotal_tax();
                $taxclass     = $item->get_tax_class();
                $taxstat      = $item->get_tax_status();
                $allmeta      = $item->get_meta_data();
                $somemeta     = $item->get_meta('_whatever', true);
                $type         = $item->get_type();

                $product_t = wc_get_product($product_id);

                if ($variation_id != 0 && $variation_id != "") {
                    $product_t = wc_get_product($variation_id);
                }

                //PRIMERO QUE NADA VEMOS SI PERTENECEN A LA CATEGORIA BUSCADA
                /*
                $terms = get_the_terms($product_id, 'product_cat');
                foreach ($terms as $term) {
                $product_cat_id = $term->term_id;
                echo "<h1>-====================================> {$product_cat_id}</h1>";
                break;
                }
                 */

                $sku   = $product_t->get_sku();
                $price = $product_t->get_price();

                //
                $variable_productos = array(
                    'name'           => $name,
                    'product_id'     => $product_id,
                    'variation_id'   => $variation_id,
                    'quantity'       => $quantity,
                    'tax_class'      => $tax_class,
                    'subtotal'       => $subtotal,
                    'subtotal_tax'   => $subtotal_tax,
                    'total'          => $total,
                    'total_tax'      => $total_tax,
                    'sku'            => $sku,
                    'price'          => $price,
                    'metaDataOrders' => []
                );

                array_push($array_productos, $variable_productos);
            }

        }

        //ARMAMOS EL METODO DE PAGO
        $arreglo_metodo_de_pago = array();

        $variable_metodo_de_pago = array(
            'method_id'    => 1000,
            'method_uid'   => "1000",
            'method_title' => $order->get_payment_method_title(),
        );

        array_push($arreglo_metodo_de_pago, $variable_metodo_de_pago);

        //addressEntrega
        $arreglo_addressEntrega = array();

        $variable_addressEntregaarray = array(
            "nombre"   => $order->get_billing_first_name(),
            "latitud"  => 0,
            "address1" => $order->get_billing_address_1(),
            "address2" => $order->get_billing_address_2(),
            "apellido" => $order->get_billing_last_name(),
            "longitud" => 0,
            "telefono" => $order->get_billing_phone(),
        );

        array_push($arreglo_addressEntrega, $variable_addressEntregaarray);

        //metodoEnvio

        $arreglo_metodoEnvio  = array();
        $variable_metodoEnvio = array(

            "method_id"      => 0,
            "method_uid"     => 0,
            "method_cost"    => $order->get_shipping_total(),
            "method_title"   => $order->get_shipping_method(),
            "method_status"  => 1,
            "method_visible" => 1,

        );

        array_push($arreglo_metodoEnvio, $variable_metodoEnvio);

        $data = array(
            /*
            "id_Ecommerce"       => $order_id,
            "customer_Name"      => $order->get_billing_first_name(),
            'customer_Last_Name' => $order->get_billing_last_name(),
            'total'              => $order->get_total(),
            'total_Tax'          => $order->get_total_tax(),
            'status'             => $order->get_status(),
            'city'               => $order->get_billing_city(),
            'state'              => $order->get_billing_state(),
             */
            //DATOS DE NEVER LEGAL
            'cliente'        => $order->get_billing_email(),
            'metodoPago'     => $arreglo_metodo_de_pago, //CONTINUAR DESDE ESTE PUNTO
            'addressEntrega' => $arreglo_addressEntrega,
            'id'             => '1', //CONSULTAR QUE ES ESTE ID
            'carrito'        => $array_productos,
            'metodoEnvio'    => $arreglo_metodoEnvio,
            "subtotal"       => $order->get_subtotal(),
            "tax"            => $order->get_total_tax(),
            "descuento"      => $order->get_discount_total(),
            "total"          => $order->get_total(),
        );

        $json = json_encode($data);

        //echo "<h1>{$json}</h1>";

        return $data;
    } else {
        return "no";
    }

}

/*
 *
 *   FUNCION PARA SABER SI LA PRDEN CUENTA
 *   CON LOS PRODUCTOS DECEADOS
 *
 */
function productos_buscados_nl($order = 0)
{

    $arreglo = [];

    foreach ($order->get_items() as $item_id => $item) {

        $product_id = $item->get_product_id();

        array_push($arreglo, $product_id);
    }

    //$arreglo = array(37, 38, 39, 40);

    // Get external products limited to ones with specific IDs.
    $args = array(
        //'type' => 'external',
        //'include' => array( 134, 200, 210, 340 ),
        'include'  => $arreglo,
        'category' => array("never-legal"),
        'return'   => 'ids',
    );

    $products = wc_get_products($args);

    //print_r($products);
    return $products;

}

//add_action("wp_footer", "productos_buscados_nl");
