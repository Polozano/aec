<?php
/**
 * @version $Id: payboxat.php
 * @package AEC - Account Control Expiration - Membership Manager
 * @subpackage Processors - Paybox.ch
 * @copyright 2007-2008 Copyright (C) David Deutsch
 * @author David Deutsch <skore@skore.de> & Team AEC - http://www.valanx.org
 * @license GNU/GPL v.2 http://www.gnu.org/licenses/old-licenses/gpl-2.0.html or, at your option, any later version
 */

// Dont allow direct linking
( defined('_JEXEC') || defined( '_VALID_MOS' ) ) or die( 'Direct Access to this location is not allowed.' );

class processor_payboxat extends SOAPprocessor
{
	function info()
	{
		$info = array();
		$info['name'] 			= 'payboxat';
		$info['longname']	 	= _CFG_PAYBOXAT_LONGNAME;
		$info['statement']		= _CFG_PAYBOXAT_STATEMENT;
		$info['description']	= _CFG_PAYBOXAT_DESCRIPTION;
		$info['currencies']		= AECToolbox::aecCurrencyField( true, true, true, true );
		$info['languages']		= AECToolbox::getISO4271_codes();
		$info['cc_list'] 		= "visa,mastercard,discover,americanexpress,echeck,jcb,dinersclub";
		$info['recurring'] 		= 0;

		return $info;
	}

	function settings()
	{
		$settings = array();
		$settings['testmode']			= 0;
		$settings['username']			= "your_username";
		$settings['password']			= "your_password";
		$settings['merchant_phone']		= "your_phone_number";
		$settings['currency']			= "EUR";
		$settings['language']			= "DE";
		$settings['item_name']			= sprintf( _CFG_PROCESSOR_ITEM_NAME_DEFAULT, '[[cms_live_site]]', '[[user_name]]', '[[user_username]]' );
		$settings['customparams']		= '';

		return $settings;
	}

	function backend_settings()
	{
		$settings = array();
		$settings['testmode']			= array( "list_yesno" );
		$settings['username'] 			= array( "inputC" );
		$settings['password'] 			= array( "inputC" );
		$settings['merchant_phone']		= array( "inputC" );
		$settings['currency']			= array( "list_currency" );
		$settings['language']			= array( "list_language" );
		$settings['item_name']			= array( "inputE" );
		$settings['customparams']		= array( 'inputD' );

		$settings = AECToolbox::rewriteEngineInfo( null, $settings );

		return $settings;
	}

	function checkoutform( $request )
	{
		$var = $this->getUserform( array(), array( 'phone' ) );

		return $var;
	}

	function createRequestXML( $request )
	{
		global $mosConfig_live_site;

		$a = array();

		$a['language']		= strtolower( $this->settings['language'] );
		$a['isTest']		= $this->settings['testmode'] ? true : false;
		$a['payer']			= $request->int_var['params']['phone'];
		$a['payee']			= $this->settings['merchant_phone'];
		$a['amount']		= (int) ( $request->int_var['amount'] * 100 );
		$a['currency']		= $this->settings['currency'];
		$a['timestamp']		= strftime("%H:%M:%S.%Y%m%d");
		$a['orderId']		= (int) $request->invoice->id;
		$a['text']			= $request->int_var['invoice'];

		$a = $this->customParams( $this->settings['customparams'], $a, $request );

		return $a;
	}

	function transmitRequestXML( $content, $request )
	{
		$path = "/gw-tx/services/PayboxServices?wsdl";

		$url = "https://" . $this->settings['username'] . ":" . $this->settings['password'] . "@www.paybox.at" . $path;

		$headers = '<credentials>'
					. '<username>' . $this->settings['username'] . '</username>'
					. '<password>' . $this->settings['password'] . '</password>'
					. '</credentials>';

		echo "<p>Bitte warten Sie w&auml;hrend das paybox-System versucht Sie anzurufen.</p>";

		$response = $this->transmitRequest( $url, $path, 'payment', $content, $headers );
aecDebug( $this->soapclient );
		$return['valid']	= false;
		$return['raw']		= $response;
aecDebug( $response );
		if ( $response ) {
			$payment_error = $response['error'];
			$payment_description = $response['errorDescription'];
aecDebug( $response['error'] );aecDebug( $response['errorDescription'] );
			$payment_id	= new soapval( 'transactionRef', 'long', $response['idTransaction'] );
			$language	= new soapval( 'language', 'string', $this->settings['language'] );

			$payment_authorization = $response['authorization'];
aecDebug( $this->soapclient );
			if ( empty( $response['error'] ) ) {
				// acknowledge the transaction to Paybox
				$this->soapclient->call('acknowledge', array($payment_id, $language));

				$return['valid'] = true;
			} else {
				$return['error'] = $response['errorDescription'];
			}
		}

		return $return;
	}
}
?>