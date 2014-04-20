<?php
/*
Plugin Name: WooCommerce Ceca Gateway
Plugin URI: http://woothemes.com/woocommerce
Description: Extends WooCommerce with an Ceca gateway.
Version: 1.0
Author: juanmirod
Author URI: http://juanmirodriguez.es/
 
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
 
add_action('plugins_loaded', 'woocommerce_gateway_ceca_init', 0);
 
function woocommerce_gateway_ceca_init() {
 
    if ( !class_exists( 'WC_Payment_Gateway' ) ) return;
 
    /**
     * Localization - NOT AVAILABLE YET
     */
    // load_plugin_textdomain('wc-gateway-ceca', false, dirname( plugin_basename( __FILE__ ) ) . '/languages');
    
    /**
     * Gateway class
     */
    class WC_Gateway_Ceca extends WC_Payment_Gateway {
    
        public function __construct() {
            $this->id = 'ceca';
            $this->icon = '';
            $this->has_fields = false;
            $this->method_title = 'Pasarela CECABANK';
            $this->method_description = 'Pasarela para pago con tarjetas de crédito.';

            $this->init_form_fields();
            $this->init_settings();

            // Define user set variables            
            $this->title        = $this->get_option( 'title' );
            $this->description  = $this->get_option( 'description' );
            $this->merchand_id  = $this->get_option( 'merchand_id' );
            $this->acquirer_bin = $this->get_option( 'acquirer_bin' );
            $this->terminal_id  = $this->get_option( 'terminal_id' );
            $this->currency     = $this->get_option( 'currency' );
            $this->language     = $this->get_option( 'language' );
            $this->debug        = $this->get_option( 'debug' ) == 'yes';

            if($this->debug) {
                $this->ceca_url = 'http://tpv.ceca.es:8000/cgi-bin/tpv';
                $this->password     = $this->get_option( 'password_debug' );
            } else {
                $this->ceca_url = 'https://pgw.ceca.es/cgi-bin/tpv';
                $this->password     = $this->get_option( 'password' );
            }

            add_action( 'valid-ceca-standard-ipn-request', array( $this, 'successful_request' ) );
            add_action( 'woocommerce_receipt_ceca', array( $this, 'receipt_page' ) );
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

            // Payment listener/API hook
            add_action( 'woocommerce_api_wc_gateway_ceca', array( $this, 'check_cecabank_response' ) );

        }

        /**
         * Check for PayPal IPN Response
         *
         * @access public
         * @return void
         */
        function check_cecabank_response() {

            @ob_clean();

            $response = ! empty( $_POST ) ? $_POST : false;

            if ( $response ) {

                header( 'HTTP/1.1 200 OK' );

                do_action( "valid-ceca-standard-ipn-request", $response );

            } else {

                wp_die( "CECABANK Request Failure", "CECABANK", array( 'response' => 200 ) );

            }

        }

        function successful_request( $posted ) {

            $posted = stripslashes_deep( $posted );

            $order_id   = $posted['num_operacion'];

            if(empty($posted['importe']) 
               || empty( $posted['referencia'])
               || empty( $posted['num_operacion']) ) {
                wp_die( "ERROR", "CECABANK", array( 'response' => 200 ) );
            }

            // search for this order and store the $ref
            $order = new WC_Order($order_id);
            if ( $order ) {
                update_post_meta( $order->id, 'REF', wc_clean( $posted['referencia'] ) );
            }
            $order->payment_completed();

            wp_die( "$*$OKY$*$", "CECABANK", array( 'response' => 200 ) );

        }

        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __( 'Activar/Desactivar' ),
                    'type' => 'checkbox',
                    'label' => __('Permitir pasarela de pago CECABANK'),
                    'default' => 'no'
                ),
                'debug' => array(
                    'title' => __( 'Activar/Desactivar' ),
                    'type' => 'checkbox',
                    'label' => __('Modo de prueba'),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title' => __('Title:', 'mrova'),
                    'type'=> 'text',
                    'description' => __('This controls the title which the user sees during checkout.', 'mrova'),
                    'default' => __('Pago con tarjeta')
                ),
                'description' => array(
                    'title' => __('Description:', 'mrova'),
                    'type' => 'textarea',
                    'description' => __('This controls the description which the user sees during checkout.', 'mrova'),
                    'default' => __('Paga de forma segura con la pasarela de pago CECABANK.')
                ),
                'merchand_id' => array(
                    'title' => __('Merchant ID'),
                    'type' => 'text',
                    'description' => __('Identifica al comercio. Facilitado por la caja en el proceso de alta.')
                ),
                'acquirer_bin' => array(
                    'title' => __('acquirer BIN'),
                    'type' => 'text',
                    'description' => __('Identifica a la caja. Facilitado por la caja en el proceso de alta.')
                ),
                'terminal_id' => array(
                    'title' => __('Terminal ID'),
                    'type' => 'text',
                    'description' => __('Identifica al terminal. Facilitado por la caja en el proceso de alta')
                ),
                'password' => array(
                    'title' => __('Clave de encriptación REAL'),
                    'type' => 'text',
                    'description' => __('Facilitado por la caja, a diferencia de los demás paámetros la clave cambia del entorno de pruebas al entorno real.')
                ),
                'password_debug' => array(
                    'title' => __('Clave de encriptación PRUEBAS'),
                    'type' => 'text',
                    'description' => __('Facilitado por la caja, a diferencia de los demás paámetros la clave cambia del entorno de pruebas al entorno real.')
                ),
                'currency' => array(
                    'title' => __('Tipo Moneda'),
                    'type' => 'text',
                    'description' => __('Es el código ISO-4217 correspondiente a la moneda en la que se efectúa el pago, 978 para euros, para más info ver documentación CECABANK'),
                    'default' => '978'
                ),
                'language' => array(
                    'title' => __('Código de idioma'),
                    'type' => 'select',
                    'description' => __('Selecciona el idioma en el que se mostrará la pasarela de pago.'),
                    'default' => '1',
                    'options' => array(
                        '1' => __('Español'), 
                        '2' => __('Catalán'), 
                        '3' => __('Euskera'), 
                        '4' => __('Gallego'), 
                        '5' => __('Valenciano'),
                        '6' => __('Inglés'),
                        '7' => __('Francés'),
                        '8' => __('Alemán'),
                        '9' => __('Portugués'),
                        '10' => __('Italiano'),
                        '14' => __('Ruso'),
                        '15' => __('Noruego')
                    ) 
                )
            );
        }

        function receipt_page( $order ) {
            echo '<p>' . __( 'Gracias, tu orden está ahora pendiente de pago, deberías ser redirigido en unos segundos a la pasarela de pago con tarjeta de CECABANK.' ) . '</p>';

            echo $this->generate_ceca_form( $order );
        }

        function calculate_sign ( $order ) {

            // Clave_encriptacion+MerchantID+AcquirerBIN+TerminalID+Num_operacion+Importe+
            // TipoMoneda+Exponente+“SHA1”+URL_OK+URL_NOK
            $signature_str = $this->password
                .$this->merchand_id
                .$this->acquirer_bin
                .$this->terminal_id
                .$order->id
                .$order->get_total()*100
                .$this->currency
                .'2SHA1'
                .$this->get_return_url( $order )
                .$this->get_return_url( $order );

            return sha1($signature_str);
        }

        function get_ceca_args( $order ) {
            $result = array();

            $result['MerchantID']       = $this->merchand_id;
            $result['AcquirerBIN']      = $this->acquirer_bin;
            $result['TerminalID']       = $this->terminal_id;
            $result['URL_OK']           = $this->get_return_url( $order );
            $result['URL_NOK']          = $this->get_return_url( $order );
            $result['Firma']            = $this->calculate_sign( $order );
            $result['Cifrado']          = 'SHA1';
            $result['Num_operacion']    = $order->id;
            $result['Importe']          = $order->get_total()*100;
            $result['TipoMoneda']       = $this->currency;
            $result['Exponente']        = '2';
            $result['Pago_soportado']   = 'SSL';
            $result['idioma']           = $this->language;

            return $result;
        }
        
        /**
         * Generate the CECA button link
         *
         * @access public
         * @param mixed $order_id
         * @return string
         */
        function generate_ceca_form( $order_id ) {

            $order = new WC_Order( $order_id );

            $ceca_args = $this->get_ceca_args( $order );

            $ceca_args_array = array();


            foreach ( $ceca_args as $key => $value ) {
                $ceca_args_array[] = '<input type="hidden" name="'.esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" />';
            }

            wc_enqueue_js( '
                $.blockUI({
                        message: "Gracias por su pedido, en unos segundos se le redigirá a la pasarela de pago CECABANK.",
                        baseZ: 99999,
                        overlayCSS:
                        {
                            background: "#fff",
                            opacity: 0.6
                        },
                        css: {
                            padding:        "20px",
                            zindex:         "9999999",
                            textAlign:      "center",
                            color:          "#555",
                            border:         "3px solid #aaa",
                            backgroundColor:"#fff",
                            cursor:         "wait",
                            lineHeight:     "24px",
                        }
                    });
                jQuery("#submit_ceca_payment_form").click();
            ' );

            return '<form action="' . esc_url( $this->ceca_url ) . '" method="post" id="ceca_payment_form" target="_top">
                    ' . implode( '', $ceca_args_array ) . '
                    <!-- Button Fallback -->
                    <div class="payment_buttons">
                        <input type="submit" class="button alt" id="submit_ceca_payment_form" value="' . __( 'Pagar con CECABANK' ) . '" /> <a class="button cancel" href="' . esc_url( $order->get_cancel_order_url() ) . '">' . __( 'Cancel order &amp; restore cart', 'woocommerce' ) . '</a>
                    </div>
                    <script type="text/javascript">
                        //jQuery(".payment_buttons").hide();
                    </script>
                </form>';

        }

        function process_payment( $order_id ) {
            global $woocommerce;

            $order = new WC_Order( $order_id );
            
            return array(
                'result'    => 'success',
                'redirect'  => $order->get_checkout_payment_url( true )
            );
            
        }


    }
    
    /**
    * Add the Gateway to WooCommerce
    **/
    function woocommerce_add_gateway_ceca($methods) {
        $methods[] = 'WC_Gateway_Ceca';
        return $methods;
    }
    
    add_filter('woocommerce_payment_gateways', 'woocommerce_add_gateway_ceca' );
} 