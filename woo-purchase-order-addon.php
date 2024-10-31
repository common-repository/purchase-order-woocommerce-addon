<?php
/**
 * Plugin Name: Purchase Order WooCommerce Addon
 * Plugin URI: 
 * Description: This plugin adds a purchase order in WooCommerce for customers to complete the order.
 * Version: 1.0
 * Author: Syed Nazrul Hassan
 * Author URI: https://nazrulhassan.wordpress.com/
 * Author Email: nazrulhassanmca@gmail.com
 * License: GPLv2
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
function purchaseorder_init()
{
	function add_purchaseorder_gateway_class( $methods ) 
	{
		$methods[] = 'WC_Purchaseorder_Gateway'; 
		return $methods;
	}
	add_filter( 'woocommerce_payment_gateways', 'add_purchaseorder_gateway_class' );

	if(class_exists('WC_Payment_Gateway'))
	{
		class WC_Purchaseorder_Gateway extends WC_Payment_Gateway 
		{
			public function __construct()
			{

			$this->id               = 'purchaseorder';
			$this->has_fields       = true;
			$this->method_title     = 'Purchase Order Settings';		
			$this->init_form_fields();
			$this->init_settings();
			$this->supports         = array('products');

			$this->title			= $this->get_option( 'po_title' );
			$this->description      = $this->get_option( 'po_description');
			
			
			add_action( 'woocommerce_admin_order_data_after_order_details', array( $this, 'display_po_number_inorder' ) );
			add_action( 'woocommerce_email_after_order_table', array( $this, 'display_po_number_inorder' ) );
			add_action( 'woocommerce_order_details_after_order_table', array( $this, 'display_po_number_inorder' ) );
		

				if (is_admin()) 
				{
					add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
				}

		    }


		    public function init_form_fields () {
	   		$this->form_fields = array(
	            'enabled'  		=> array(
	                'title' 	=> __( 'Enable/Disable', 'woocommerce' ),
	                'type' 		=> 'checkbox',
	                'label' 	=> __( 'Enable Purchase Orders.', 'woocommerce' ),
	                'default' 	=> 'no' ),

	            'po_title' => array(
	                'title' 	  => __( 'Title:', 'woocommerce' ),
	                'type'		  => 'text',
	                'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
	                'default'     => __( 'Purchase Order', 'woocommerce' ) ),

	            'po_description'  => array(
	                'title'       => __( 'Description:', 'woocommerce' ),
	                'type'        => 'textarea',
	                'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce' ),
	                'default'     => __( 'Please add your P.O. Number to the purchase order field.', 'woocommerce' ) ),


	        	);
			} 


			public function admin_options () {
				echo '<h3>'.__( 'Purchase Order WooCommerce Addon', 'woocommerce' ) . '</h3>';
				echo '<table class="form-table">';
				
				$this->generate_settings_html();
				echo '</table>';
			} 


			public function display_po_number_inorder ( $order ) {
				if ( isset( $order->payment_method ) && 'purchaseorder' == $order->payment_method ) {
					$po_number = get_post_meta( $order->id, '_po_number', true );
					$po_note   = get_post_meta( $order->id, '_po_note'  , true );

					echo "<h3>Purchase Order Details</h3>";
					if ( '' != $po_number ) {
						echo '<p class="form-field form-field-wide"><label>' . __( 'PO Number:', 'woocommerce' ) . '</label><p>' . $po_number . '</p>' . "\n";
					}

					if ( '' != $po_note ) {
						echo '<p class="form-field form-field-wide"><label>' . __( 'PO Note:', 'woocommerce' ) . '</label><p>' . $po_note . '</p>' . "\n";
					}
				}
			} // End display_po_number_inorder()

			public function payment_fields () {
	        if( $this->description ) echo wpautop( wptexturize( $this->description ) );
	      
			$po_number = '';
			if ( isset( $_REQUEST[ 'post_data' ] ) ) {
				parse_str( $_REQUEST[ 'post_data' ], $post_data );
		        if ( isset( $post_data[ 'po_number_field' ] ) ) {
					$po_number = $post_data[ 'po_number_field' ];
		        }

		        if ( isset( $post_data[ 'po_note_field' ] ) ) {
					$po_note   = $post_data[ 'po_note_field' ];
		        }
			}  ?>

			<fieldset>
				<p class="form-row form-row-first">
					<label for="poorder"><?php _e( 'Purchase Order', 'woocommerce' ); ?> <span class="required">*</span></label>
					<input type="text" class="input-text" value="<?php echo esc_attr( $po_number ); ?>" id="po_number_field" name="po_number_field" />

					<label for="poordernote"><?php _e( 'Purchase Order Note', 'woocommerce' ); ?></label>
					<input type="text" class="input-text" value="<?php echo esc_attr( $po_note ); ?>" id="po_note_field" name="po_note_field" />
				</p>
			</fieldset>

			<?php

		   }

			public function __clone () {
				_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), '1.0' );
			} // End __clone()

		   public function __wakeup () {
				_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), '1.0' );
			} // End __wakeup()



			private function get_post ( $name ) {
				if( isset( $_POST[$name] ) ) {
					return $_POST[$name];
				} else {
					return NULL;
				}
			} // End get_post()

			public function validate_fields () {
				$poorder = $this->get_post( 'po_number_field' );
				if( ! $poorder ) {
					
					wc_add_notice ( __ ( 'Please enter your PO Number.', 'woocommerce' ), 'error' ); 
					return false;
				} else {
					return true;
				}
			} // End validate_fields()


			public function process_payment( $order_id ) {
			$order = new WC_Order( $order_id );

			$poorder = $this->get_post( 'po_number_field' );
			if ( isset( $poorder ) ) update_post_meta( $order_id, '_po_number', esc_attr( $poorder ) );

			$poorder_note  = $this->get_post( 'po_note_field' );
			if ( isset( $poorder_note ) ) update_post_meta( $order_id, '_po_note', esc_attr( $poorder_note ) );

			$order->update_status( 'on-hold', __( 'Waiting to be processed', 'woocommerce' ) );

			// Reduce stock levels
			$order->reduce_order_stock();

			// Remove cart
			WC()->cart->empty_cart();

			// Return thankyou redirect
			return array(
			'result' 	=> 'success',
			'redirect'	=> $this->get_return_url( $order )
			);
			} // End process_payment()



		}
	}

}

add_action( 'plugins_loaded', 'purchaseorder_init' );
