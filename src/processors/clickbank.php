<?php
/**
 * @version $Id: clickbank.php
 * @package AEC - Account Control Expiration - Membership Manager
 * @subpackage Processors - Clickbank
 * @copyright 2007-2008 Copyright (C) David Deutsch
 * @author David Deutsch <skore@skore.de> & Team AEC - http://www.globalnerd.org, initial help by Pasapum Naonan
 * @license GNU/GPL v.2 http://www.gnu.org/licenses/old-licenses/gpl-2.0.html or, at your option, any later version
 */

// Dont allow direct linking
( defined('_JEXEC') || defined( '_VALID_MOS' ) ) or die( 'Direct Access to this location is not allowed.' );

class processor_clickbank extends URLprocessor
{
	function info()
	{
		$info = array();
		$info['name']					= 'clickbank';
		$info['longname'] 				= _CFG_CLICKBANK_LONGNAME;
		$info['statement'] 				= _CFG_CLICKBANK_STATEMENT;
		$info['description'] 			= _CFG_CLICKBANK_DESCRIPTION;
		$info['cc_list'] 				= "visa,mastercard,americanexpress,discover,dinersclub,jcb,paypal";
		$info['currencies']				= "USD";
		$info['recurring'] 				= 2;
		$info['notify_trail_thanks']	= 1;

		return $info;
	}

	function settings()
	{
		$settings = array();
		$settings['testmode']			= 0;
		$settings['publisher']			= 'clickbank';
		$settings['secret_key']			= 'secret_key';
		$settings['info']				= "";
		$settings['customparams']		= "";

		return $settings;
	}

	function backend_settings()
	{
		$settings = array();
		$settings['testmode']			= array( 'list_yesno' );
		$settings['publisher']			= array( 'inputC' );
		$settings['secret_key']			= array( 'inputC' );
		$settings['info']				= array( 'fieldset' );
		$settings['customparams']		= array( 'inputD' );

		$settings = AECToolbox::rewriteEngineInfo( null, $settings );

		return $settings;
	}

	function CustomPlanParams()
	{
		$p = array();
		$p['item_number']	= array( 'inputC' );

		return $p;
	}

	function createGatewayLink( $request )
	{
		global $mosConfig_live_site;

		$item_number			= $var['allowedTypes'] = $request->int_var['planparams']['item_number'];

		$var['post_url']		= 'http://'.$item_number.'.'.$this->settings['publisher'].'.pay.clickbank.net?';

		// pass internal invoice to clickbank, so it will pass back to us for internal checking
		$var['invoice']			= $request->int_var['invoice'];

		$var['cart_order_id']	= AECToolbox::rewriteEngineRQ( $this->settings['item_name'], $request );

		return $var;
	}

	function parseNotification( $request )
	{
		$response = array();
		$response['invoice']			= aecGetParam( 'invoice' );

		$amount = aecGetParam( 'ctransamount', '', true, array( 'word' ) );

		if ( !empty( $amount ) ) {
			$response['amount_paid']	= $amount / 100;
		}

		return $response;
	}

	function validateNotification( $response, $request, $invoice )
	{
		$response['valid'] = 0;

		if ( !isset( $_REQUEST['cverify'] ) ) {
			/**
			 * option,com_acctexp
			 * task,clickbanknotification
			 * item,1
			 * cbreceipt,TESTHER2
			 * time,1227402795
			 * cbpop,05115E23
			 * cbaffi,0
			 * cname,Bruno+Bruno
			 * cemail,bruno%40bruno.com
			 * czip,90210
			 * ccountry,US
			 * allowedTypes,1
			 * invoice,IM2E2MTNiYTM2YTIz/
			 */

			// It seems this is the crude postback. Trying to decypher.

			$key='YOUR SECRET KEY';

			$check = array();
			$check[] = $this->settings['secret_key'];
			$check[] = $_REQUEST['cbreceipt'];
			$check[] = $_REQUEST['time'];
			$check[] = $_REQUEST['item'];

			$code = sha1( implode( '|', $check) );

			$xxpop = strtoupper( substr( $code,0 ,8 ) );

			if ( $_REQUEST['cbpop'] == $xxpop ) {
				$response['valid']	= 1;
			} else {
				$response['pending_reason'] = 'verification error';
			}
		} else {
			// Standard parameters that Clickbank will send back (leaving out 'cverify')
			$stdParams = array( 'ccustname', 'ccuststate', 'ccustcc', 'ccustemail',
								'cproditem', 'cprodtitle', 'cprodtype', 'ctransaction',
								'ctransaffiliate', 'ctransamount', 'ctranspaymentmethod', 'ctranspublisher',
								'ctransreceipt', 'caffitid', 'cvendthru', 'ctranstime' );

			$params = array();
			foreach ( $stdParams as $name ) {
				$params[] = aecGetParam( $name, '', true, array( 'word', 'string' ) );
			}

			$params[] = $this->settings['secret_key'];

			$verify = strtoupper( substr( implode( '|', $params ), 0, 8 ) );

			if ( aecGetParam( 'cverify' ) == $verify ) {
				switch ( $request[''] ) {
					// The purchase of a standard product or the initial purchase of recurring billing product.
					case 'SALE':
						$response['valid']	= 1;
						break;
					// The purchase of a standard product or the initial purchase of recurring billing product.
					case 'BILL':
						$response['valid']	= 1;
						break;
					// The refunding of a standard or recurring billing product. Recurring billing products that are refunded also result in a "CANCEL-REBILL" action.
					case 'RFND':
						$response['delete']	= 1;
						break;
					// A chargeback for a standard or recurring product.
					case 'CGBK':
						$response['delete']	= 1;
						break;
					// A chargeback for a standard or recurring product.
					case 'INSF':
						$response['delete']	= 1;
						break;
					// The cancellation of a recurring billing product. Recurring billing products that are canceled do not result in any other action.
					case 'CANCEL-REBILL':
						$response['cancel']	= 1;
						break;
					// Reversing the cancellation of a recurring billing product.
					case 'UNCANCEL-REBILL':
						$response['cancel']	= 1;
						break;
					// Triggered by using the test link on the site page.
					case 'TEST':
						if ( $this->settings['secret_key'] ) {
							$response['valid']	= 1;
						} else {
							$response['pending_reason'] = 'testmode claimed when not activated';
						}
						break;
				}
			} else {
				$response['pending_reason'] = 'verification error';
			}

			return $response;
		}
	}

}
?>