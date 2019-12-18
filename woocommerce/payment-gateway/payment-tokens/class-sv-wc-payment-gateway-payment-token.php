<?php
/**
 * WooCommerce Payment Gateway Framework
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the plugin to newer
 * versions in the future. If you wish to customize the plugin for your
 * needs please refer to http://www.skyverge.com
 *
 * @package   SkyVerge/WooCommerce/Payment-Gateway/Classes
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2019, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_5_1;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_5_1\\SV_WC_Payment_Gateway_Payment_Token' ) ) :


/**
 * WooCommerce Payment Gateway Token
 *
 * Represents a credit card or check payment token
 */
class SV_WC_Payment_Gateway_Payment_Token {


	/** @var string payment gateway token ID */
	protected $id;

	/**
	 * @var array associated token data
	 */
	protected $data;

	/**
	 * @var string payment type image url
	 */
	protected $img_url;

	/**
	 * @var array key-value array to map WooCommerce core token props to framework token `$data` keys
	 */
	private $props = [
		'gateway_id'   => 'gateway_id',
		'user_id'      => 'user_id',
		'is_default'   => 'default',
		'type'         => 'type',
		'last4'        => 'last_four',
		'expiry_year'  => 'exp_year',
		'expiry_month' => 'exp_month',
		'card_type'    => 'card_type',
	];

	/**
	 * @var null|\WC_Payment_Token WooCommerce core token corresponding to the framework token, if set
	 */
	private $token;


	/**
	 * Initializes a payment token.
	 *
	 * The token $data is expected to have the following members:
	 *
	 * gateway_id   - string identifier of the gateway the token belongs to (in WooCommerce core tokens this also identifies the environment of the gateway)
	 * user_id      - int identifier of the customer user associated to this token
	 * default      - boolean optional indicates this is the default payment token
	 * type         - string one of 'credit_card' or 'echeck' ('check' for backwards compatibility)
	 * last_four    - string last four digits of account number
	 * card_type    - string credit card type: visa, mc, amex, disc, diners, jcb, etc (credit card only)
	 * exp_month    - string optional expiration month MM (credit card only)
	 * exp_year     - string optional expiration year YYYY (credit card only)
	 * account_type - string one of 'checking' or 'savings' (checking gateway only)
	 *
	 * @since 1.0.0
	 *
	 * @param string $id the payment gateway token ID
	 * @param array|\WC_Payment_Token $data associated data array or WC core token
	 */
	public function __construct( $id, $data ) {

		if ( $data instanceof \WC_Payment_Token ) {

			$this->token = $data;

			$this->read( $this->token );

		} else {

			if ( isset( $data['type'] ) && 'credit_card' == $data['type'] ) {

				// normalize the provided card type to adjust for possible abbreviations if set
				if ( isset( $data['card_type'] ) && $data['card_type'] ) {

					$data['card_type'] = SV_WC_Payment_Gateway_Helper::normalize_card_type( $data['card_type'] );

				// otherwise, get the payment type from the account number
				} elseif ( isset( $data['account_number'] ) ) {

					$data['card_type'] = SV_WC_Payment_Gateway_Helper::card_type_from_account_number( $data['account_number'] );
				}
			}

			// remove account number so it's not saved to the token
			unset( $data['account_number'] );

			$this->data = $data;
		}

		$this->id = $id;
	}


	/**
	 * Gets the payment token string.
	 *
	 * @since 1.0.0
	 * @deprecated 4.0.0
	 *
	 * @return string payment token string
	 */
	public function get_token() {

		wc_deprecated_function( __METHOD__, '4.0.0', __CLASS__ . '::get_id()' );

		return $this->get_id();
	}


	/**
	 * Returns the payment token string
	 *
	 * @since 4.0.0
	 * @return string payment token string
	 */
	public function get_id() {

		return $this->id;
	}


	/**
	 * Gets the gateway ID for the token.
	 *
	 * @since 5.6.0-dev.1
	 *
	 * @return string
	 */
	public function get_gateway_id() {

		return isset( $this->data['gateway_id'] ) ? $this->data['gateway_id'] : '';
	}


	/**
	 * Sets the gateway ID for the token.
	 *
	 * @since 5.6.0-dev.1
	 *
	 * @param string $gateway_id
	 */
	public function set_gateway_id( $gateway_id ) {

		$this->data['gateway_id'] = $gateway_id;
	}


	/**
	 * Gets the ID of the user associated with the token.
	 *
	 * @since 5.6.0-dev.1
	 *
	 * @return int
	 */
	public function get_user_id() {

		return isset( $this->data['user_id'] ) ? absint( $this->data['user_id'] ) : 0;
	}


	/**
	 * Sets the ID of the user associated with the token.
	 *
	 * @since 5.6.0-dev.1
	 *
	 * @param int $user_id
	 */
	public function set_user_id( $user_id ) {

		$this->data['user_id'] = is_numeric( $user_id ) ? absint( $user_id ) : 0;
	}


	/**
	 * Returns true if this payment token is default
	 *
	 * @since 1.0.0
	 * @return boolean true if this payment token is default
	 */
	public function is_default() {

		return isset( $this->data['default'] ) && $this->data['default'];
	}


	/**
	 * Makes this payment token the default or a non-default one
	 *
	 * @since 1.0.0
	 * @param boolean $default true or false
	 */
	public function set_default( $default ) {

		$this->data['default'] = $default;
	}


	/**
	 * Returns true if this payment token represents a credit card
	 *
	 * @since 1.0.0
	 * @return boolean true if this payment token represents a credit card
	 */
	public function is_credit_card() {

		return 'credit_card' === $this->data['type'];
	}


	/**
	 * Determines if this payment token represents an eCheck.
	 *
	 * @since 1.0.0
	 * @deprecated since 4.0.0
	 *
	 * @return bool
	 */
	public function is_check() {

		wc_deprecated_function( __METHOD__, '4.0.0', __CLASS__ . '::is_echeck()' );

		return $this->is_echeck();
	}


	/**
	 * Returns true if this payment token represents an eCheck
	 *
	 * @since 4.0.0
	 * @return boolean true if this payment token represents an eCheck
	 */
	public function is_echeck() {

		return ! $this->is_credit_card();
	}


	/**
	 * Returns the payment type, one of 'credit_card' or 'echeck'
	 *
	 * @since 1.0.0
	 * @return string the payment type
	 */
	public function get_type() {

		return $this->data['type'];
	}


	/**
	 * Returns the card type ie visa, mc, amex, disc, diners, jcb, etc
	 *
	 * Credit card gateway only
	 *
	 * @since 1.0.0
	 * @return string the payment type
	 */
	public function get_card_type() {

		return isset( $this->data['card_type'] ) ? $this->data['card_type'] : null;
	}


	/**
	 * Set the card type
	 *
	 * Credit Card gateway only
	 *
	 * @since 4.0.0
	 * @param string $card_type
	 */
	public function set_card_type( $card_type ) {

		$this->data['card_type'] = $card_type;
	}


	/**
	 * Determines the credit card type from the full account number.
	 *
	 * @since 1.0.0
	 * @deprecated 4.0.0
	 * @see SV_WC_Payment_Gateway_Helper::card_type_from_account_number()
	 *
	 * @param string $account_number the credit card account number
	 * @return string the credit card type
	 */
	public static function type_from_account_number( $account_number ) {

		wc_deprecated_function( __METHOD__, '4.0.0', __CLASS__, '::card_type_from_account_number()' );

		return SV_WC_Payment_Gateway_Helper::card_type_from_account_number( $account_number );
	}


	/**
	 * Returns the bank account type, one of 'checking' or 'savings'
	 *
	 * eCheck gateway only
	 *
	 * @since 1.0.0
	 * @return string the payment type
	 */
	public function get_account_type() {

		return isset( $this->data['account_type'] ) ? $this->data['account_type'] : null;
	}


	/**
	 * Set the account type
	 *
	 * eCheck gateway only
	 *
	 * @since 4.0.0
	 * @param string $account_type
	 */
	public function set_account_type( $account_type ) {

		$this->data['account_type'] = $account_type;
	}


	/**
	 * Returns the full payment type, ie Visa, MasterCard, American Express, Discover, Diners, JCB, eCheck, etc.
	 *
	 * @since 1.0.0
	 *
	 * @return string the payment type
	 */
	public function get_type_full() {

		if ( $this->is_credit_card() ) {
			$type = $this->get_card_type()    ?: 'card';
		} else {
			$type = $this->get_account_type() ?: 'bank';
		}

		return SV_WC_Payment_Gateway_Helper::payment_type_to_name( $type );
	}


	/**
	 * Returns the last four digits of the credit card or check account number
	 *
	 * @since 1.0.0
	 * @return string last four of account
	 */
	public function get_last_four() {

		return isset( $this->data['last_four'] ) ? $this->data['last_four'] : null;
	}


	/**
	 * Set the account last four
	 *
	 * @since 4.0.0
	 * @param string $last_four
	 */
	public function set_last_four( $last_four ) {

		$this->data['last_four'] = $last_four;
	}


	/**
	 * Returns the expiration month of the credit card.  This should only be
	 * called for credit card tokens
	 *
	 * @since 1.0.0
	 * @return string expiration month as a two-digit number
	 */
	public function get_exp_month() {

		return isset( $this->data['exp_month'] ) ? $this->data['exp_month'] : null;
	}


	/**
	 * Set the expiration month
	 *
	 * @since 4.0.0
	 * @param string $month
	 */
	public function set_exp_month( $month ) {

		$this->data['exp_month'] = $month;
	}


	/**
	 * Returns the expiration year of the credit card.  This should only be
	 * called for credit card tokens
	 *
	 * @since 1.0.0
	 * @return string expiration year as a four-digit number
	 */
	public function get_exp_year() {

		return isset( $this->data['exp_year'] ) ? $this->data['exp_year'] : null;
	}


	/**
	 * Set the expiration year
	 *
	 * @since 4.0.0
	 * @param string $year
	 */
	public function set_exp_year( $year ) {

		$this->data['exp_year'] = $year;
	}


	/**
	 * Returns the expiration date in the format MM/YY, suitable for use
	 * in order notes or other customer-facing areas
	 *
	 * @since 1.0.0
	 * @return string formatted expiration date
	 */
	public function get_exp_date() {

		return $this->get_exp_month() . '/' . substr( $this->get_exp_year(), -2 );
	}


	/**
	 * Set the full image URL based on the token payment type.  Note that this
	 * is available for convenience during a single request and will not be
	 * included in persistent storage
	 *
	 * @see SV_WC_Payment_Gateway_Payment_Token::get_image_url()
	 * @since 1.0.0
	 * @param string $url the full image URL
	 */
	public function set_image_url( $url ) {

		$this->img_url = $url;
	}


	/**
	 * Get the full image URL based on teh token payment type.
	 *
	 * @see SV_WC_Payment_Gateway_Payment_Token::set_image_url()
	 * @since 1.0.0
	 * @return string the full image URL
	 */
	public function get_image_url() {

		return $this->img_url;
	}


	/**
	 * Gets the payment method nickname.
	 *
	 * @since 5.1.0
	 *
	 * @return string
	 */
	public function get_nickname() {

		return isset( $this->data['nickname'] ) ? $this->data['nickname'] : '';
	}


	/**
	 * Sets the payment method nickname.
	 *
	 * @since 5.1.0
	 *
	 * @param string $value nickname value
	 */
	public function set_nickname( $value ) {

		$this->data['nickname'] = $value;
	}


	/**
	 * Gets the billing address hash.
	 *
	 * @since 5.3.0
	 *
	 * @return string
	 */
	public function get_billing_hash() {

		return isset( $this->data['billing_hash'] ) ? $this->data['billing_hash'] : '';
	}


	/**
	 * Sets the billing hash.
	 *
	 * @since 5.3.0
	 *
	 * @param string $value billing hash
	 */
	public function set_billing_hash( $value ) {

		$this->data['billing_hash'] = $value;
	}


	/**
	 * Gets the WooCommerce core payment token object related to this framework token.
	 *
	 * @since 5.6.0-dev.1
	 *
	 * @return \WC_Payment_Token|null
	 */
	public function get_woocommerce_payment_token() {

		$token = null;

		if ( $this->token instanceof \WC_Payment_Token ) {

			$token = $this->token;

		} else {

			// see if there is already a token with this ID saved for this customer and gateway
			$saved_tokens = \WC_Payment_Tokens::get_customer_tokens( $this->get_user_id(), $this->get_gateway_id() );

			foreach ( $saved_tokens as $saved_token ) {

				if ( $saved_token->get_token() === $this->get_id() ) {
					$token = $saved_token;
					break;
				}
			}
		}

		return $token;
	}


	/**
	 * Returns a representation of this token suitable for persisting to a
	 * datastore
	 *
	 * @since 1.0.0
	 * @return mixed datastore representation of token
	 */
	public function to_datastore_format() {

		return $this->data;
	}


	/**
	 * Reads the properties and meta data of a WooCommerce core token.
	 *
	 * Sets the found key-values as an array in the data property.
	 *
	 * @since 5.6.0-dev.1
	 *
	 * @param \WC_Payment_Token $core_token
	 */
	private function read( \WC_Payment_Token $core_token ) {

		$token_data = $core_token->get_data();
		$meta_data  = $token_data['meta_data'] ?: [];

		unset( $token_data['meta_data'] );

		foreach ( $meta_data as $meta_datum ) {
			$token_data[ $meta_datum->key ] = $meta_datum->value;
		}

		foreach ( $token_data as $core_key => $value ) {

			if ( array_key_exists( $core_key, $this->props ) ) {

				$framework_key = $this->props[ $core_key ];

				$this->data[ $framework_key ] = $value;
			}
		}
	}


	/**
	 * Stores the token data in the database.
	 *
	 * Stores the token as a Woocommerce payment token.
	 * @see \WC_Payment_Token::save()
	 *
	 * @since 5.6.0-dev.1
	 *
	 * @retur int ID of the token saved as returned by {@see \WC_Payment_Token::save()}
	 */
	public function save() {

		$token = $this->get_woocommerce_payment_token();

		if ( ! $token instanceof \WC_Payment_Token ) {

			// instantiate a new token: if it's not a credit card, we default to echeck, so there's always an instance
			if ( $this->is_credit_card() ) {
				$token = new \WC_Payment_Token_CC();
			} elseif ( $this->is_echeck() ) {
				$token = new \WC_Payment_Token_ECheck();
			}

			// mark the token as migrated
			$this->data['migrated'] = true;

			// update legacy token to mark it migrated
			if ( $tokens_handler = $this->get_tokens_handler() ) {
				$tokens_handler->update_legacy_token( $this->get_user_id(), $this );
			}
		}

		$token->set_token( $this->get_id() );

		foreach ( $this->data as $key => $value ) {

			// prefix the expiration year if needed (WC_Payment_Token requires it to be 4 digits long)
			// TODO: figure out how to handle cards expiring before 2000 {DM 2019-12-13}
			if ( 'exp_year' === $key && 2 === strlen( $value ) ) {
				$value = '20' . $value;
			}

			$core_key = array_search( $key, $this->props, false );

			if ( false !== $core_key ) {
				$token->set_props( [ $core_key => $value ] );
			} else {
				$token->update_meta_data( $key, $value, true );
			}
		}

		return $token->save();
	}


	/**
	 * Deletes the token from the database.
	 *
	 * Also deletes the token from the legacy user meta.
	 *
	 * @see \WC_Payment_Token::delete()
	 * @see SV_WC_Payment_Gateway_Payment_Tokens_Handler::delete_legacy_token()
	 *
	 * @since 5.6.0-dev.1
	 *
	 * @param bool $force_delete argument mapped to {@see \WC_Data::delete()}
	 * @return bool whether a token was deleted (note: it will not evaluate deletion of legacy data)
	 */
	public function delete( $force_delete = false ) {

		$deleted = false;

		// delete the core token from WooCommerce tables
		if ( $token = $this->get_woocommerce_payment_token() ) {
			$deleted = $token->delete( $force_delete );
		}

		// delete legacy token in WordPress user meta table
		if ( $tokens_handler = $this->get_tokens_handler() ) {
			$tokens_handler->delete_legacy_token( $this->get_user_id(), $this );
		}

		return $deleted;
	}


	/**
	 * Gets the gateway tokens handler.
	 *
	 * @since 5.6.0-dev.1
	 *
	 * @return SV_WC_Payment_Gateway_Payment_Tokens_Handler|null
	 */
	protected function get_tokens_handler() {

		$handler    = null;
		$gateway_id = $this->get_gateway_id();

		if ( ! empty( $gateway_id ) ) {

			$gateways = WC()->payment_gateways()->payment_gateways();

			if ( $gateways && isset( $gateways[ $gateway_id ] ) ) {

				$gateway = $gateways[ $gateway_id ];

				if ( $gateway instanceof SV_WC_Payment_Gateway ) {

					$handler = $gateway->get_payment_tokens_handler();
				}
			}
		}

		return $handler;
	}


}


endif;
