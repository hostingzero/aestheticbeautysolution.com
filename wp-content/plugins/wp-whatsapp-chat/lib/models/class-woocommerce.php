<?php

namespace QuadLayers\QLWAPP\Models;

class WooCommerce extends Base {

	protected $table = 'woocommerce';

	public function get_args() {
		$args = array(
			'layout'            => 'button',
			'box'               => 'no',
			'position'          => 'none',
			'text'              => esc_html__( 'How can I help you?', 'wp-whatsapp-chat' ),
			'message'           => sprintf( esc_html__( 'Hello! I\'m testing the %1$s plugin %2$s', 'wp-whatsapp-chat' ), QLWAPP_PLUGIN_NAME, QLWAPP_LANDING_URL ),
			'icon'              => 'qlwapp-whatsapp-icon',
			'type'              => 'phone',                 // here we define the type of button, can be 'phone' or 'group'
			'phone'             => QLWAPP_PHONE_NUMBER,
			'group'             => '',
			'developer'         => 'no',
			'rounded'           => 'yes',
			'timefrom'          => '00:00',
			'timeto'            => '00:00',
			'timedays'          => array(),
			'timezone'          => qlwapp_get_current_timezone(),
			'visibility'        => 'readonly',
			'timeout'           => 'readonly', /* TODO: delete */
			'position_priority' => 10,
			'animation-name'    => '',
			'animation-delay'   => '',
		);
		return $args;
	}

	public function save( $scheme = null ) {
		return parent::save_data( $this->table, $scheme );
	}

	public function get() {

		$result = $this->get_all( $this->table );

		// if ( isset( $result['text'] ) ) {
		// $result['text'] = $this->replacements( $result['text'] );
		// }

		// if ( isset( $result['message'] ) ) {
		// $result['message'] = $this->replacements( $result['message'] );
		// }

		return wp_parse_args( $result, $this->get_args() );
	}

	public function replacements( $text ) {

		if ( is_product() ) {
			$product = wc_get_product();
			$replace = array(
				'PRODUCT'  => $product->get_name(),
				'SKU'      => $product->get_sku(),
				'REGULAR'  => $this->get_regular_price( $product ),
				'PRICE'    => $this->get_price( $product ),
				'DISCOUNT' => $this->get_discount( $product ),
			);

			$text = array_merge( $text, $replace );
		}

		return $text;
	}

	public function get_regular_price( $product ) {

		$price = 'variable' === $product->get_type() ? $product->get_variation_regular_price( 'min' ) : $product->get_regular_price();

		return $this->format_price( $product, $price );

	}


	public function get_price( $product ) {

		$price = 'variable' === $product->get_type() ? $product->get_variation_price( 'min' ) : $product->get_price();

		return $this->format_price( $product, $price );

	}

	public function get_discount( $product ) {

		$regular_price = 'variable' === $product->get_type() ? $product->get_variation_regular_price( 'min' ) : $product->get_regular_price();
		$sale_price    = 'variable' === $product->get_type() ? $product->get_variation_price( 'min' ) : $product->get_price();

		$percentage = '';
		if ( is_numeric( $regular_price ) && is_numeric( $sale_price ) && $regular_price > 0 ) {
			$percentage = round( ( ( $regular_price - $sale_price ) / $regular_price ) * 100 );
		}

		return $percentage ? "-$percentage%" : '';

	}

	public function format_price( $product, $price ) {
		$string = wp_strip_all_tags( wc_price( wc_get_price_to_display( $product, array( 'price' => $price ) ) ) );
		return str_replace( '$', '\$', $string );

	}
}