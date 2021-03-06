<?php
/**
 * @version $Id: mollie_wallie.php
 * @package AEC - Account Control Expiration - Membership Manager
 * @subpackage Processors - Mollie Wallie
 * @author Thailo van Ree, David Deutsch <skore@valanx.org> & Team AEC - http://www.valanx.org
 * @copyright 2006-2015 Copyright (C) David Deutsch
 * @license GNU/GPL v.3 http://www.gnu.org/licenses/gpl.html or, at your option, any later version
 */

// Dont allow direct linking
defined('_JEXEC') or die( 'Direct Access to this location is not allowed.' );

class processor_mollie_wallie extends XMLprocessor
{
	public function info()
	{
		$info = array();
		$info['name']					= 'mollie_wallie';
		$info['longname']				= JText::_('CFG_MOLLIE_WALLIE_LONGNAME');
		$info['statement']				= JText::_('CFG_MOLLIE_WALLIE_STATEMENT');
		$info['description']			= JText::_('CFG_MOLLIE_WALLIE_DESCRIPTION');
		$info['currencies']				= 'EUR';
		$info['languages']				= 'NL';
		$info['recurring']	   			= 0;
		$info['notify_trail_thanks']	= true;

		return $info;
	}

	public function settings()
	{
		$settings = array();

		$settings['testmode']		= 0;
		$settings['partner_id']		= "00000";
		$settings['currency']		= 'EUR';
		$settings['description']	= sprintf( JText::_('CFG_PROCESSOR_ITEM_NAME_DEFAULT'), '[[cms_live_site]]', '[[user_name]]', '[[user_username]]' );

		$settings['customparams']	= "";

		return $settings;
	}

	public function backend_settings()
	{
		$settings = array();
		// note: the mollie-wallie API is currently NOT equipped with a test interface!!!
		//$settings['testmode']		= 0;
		$settings['partner_id']		= array( 'inputC' );
		$settings['currency']		= array( 'list_currency' );
		$settings['description']	= array( 'inputE' );

		$settings = AECToolbox::rewriteEngineInfo( null, $settings );

		return $settings;
	}


	public function checkoutform( $request )
	{
		$var = array();
		return $var;
	}

	public function createRequestXML( $request )
	{
		return "";
	}

	public function transmitRequestXML( $xml, $request )
	{
		require_once( dirname(__FILE__) . '/lib/cls.wallie.php' );

		$response			= array();
		$response['valid']	= false;

		$report_url		= AECToolbox::deadsureURL( "index.php?option=com_acctexp&task=mollie_wallienotification" );
		$return_url		= $request->int_var['return_url'];
		$amount			= $request->int_var['amount']*100;

		$mollieWallie = new Mollie_Wallie( $this->settings['partner_id'] );

		if ( $mollieWallie->createPayment( $amount, $report_url, $return_url ) ) {

			// ...Request valid transaction id from Mollie and store it...
			$request->invoice->secondary_ident = $mollieWallie->getTransactionId();
			$request->invoice->storeload();

			// Redirect to Wallie platform
			aecRedirect( $mollieWallie->getWallieUrl() );
		} else {

			// error handling
			$this->___logError( "Mollie_Wallie::createPayment failed",
								$mollieWallie->getErrorCode(),
								$mollieWallie->getErrorMessage()
								);

			return $response;
		}

		return null;
	}

	public function parseNotification( $post )
	{
		$response				= array();
		$response['valid']		= false;
		$response['invoice']	= aecGetParam( 'transaction_id', '', true, array( 'word', 'string', 'clear_nonalnum' ) );
		$response['amount_paid']= aecGetParam( 'amount', '', true, array( 'word', 'string', 'clear_nonalnum' ) );

		return $response;
	}

	public function validateNotification( $response, $post, $invoice )
	{
		require_once( dirname(__FILE__) . '/lib/cls.wallie.php' );

		$response				= array();
		$response['valid']		= false;
		$transaction_id 		= aecGetParam( 'transaction_id', '', true, array( 'word', 'string', 'clear_nonalnum' ) );
		$amount 				= aecGetParam( 'amount', '', true, array( 'word', 'string', 'clear_nonalnum' ) );

		if ( strlen( $transaction_id ) ) {

			$mollieWallie = new Mollie_Wallie( $this->settings['partner_id'] );

			if ( $mollieWallie->checkPayment( $transaction_id, $amount ) ) {
				$response['valid'] = true;
			} else {
				// error handling
				$response['error']		= true;
				$response['errormsg']	= 'Mollie_Wallie::checkPayment failed';

				$this->___logError( "Mollie_Wallie::checkPayment failed",
									$mollieWallie->getErrorCode(),
									$mollieWallie->getErrorMessage()
									);
			}
		}

		return $response;
	}

	/**
	 * @param string $shortdesc
	 */
	public function ___logError( $shortdesc, $errorcode, $errordesc )
	{
		$this->fileError( $shortdesc . '; Error code: ' . $errorcode . '; Error(s): ' . $errordesc );
	}
}
