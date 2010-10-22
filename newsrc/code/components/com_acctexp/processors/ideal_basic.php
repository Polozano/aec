<?php
/**
 * @version $Id: ideal_basic.php
 * @package AEC - Account Control Expiration - Membership Manager
 * @subpackage Processors - iDeal Basic
 * @author David Deutsch <skore@skore.de> & Team AEC - http://www.valanx.org
 * @copyright 2006-2010 Copyright (C) David Deutsch
 * @license GNU/GPL v.2 http://www.gnu.org/licenses/old-licenses/gpl-2.0.html or, at your option, any later version
 */

// Dont allow direct linking
( defined('_JEXEC') || defined( '_VALID_MOS' ) ) or die( 'Direct Access to this location is not allowed.' );

class processor_ideal_basic extends POSTprocessor
{
	function info()
	{
		$info = array();
		$info['name']					= 'ideal_basic';
		$info['longname']				= _CFG_IDEAL_BASIC_LONGNAME;
		$info['statement']				= _CFG_IDEAL_BASIC_STATEMENT;
		$info['description']			= _CFG_IDEAL_BASIC_DESCRIPTION;
		$info['currencies']				= 'EUR';
		$info['languages']				= 'NL';
		$info['cc_list']				= 'rabobank,ing';
		$info['recurring']				= 0;
		$info['notify_trail_thanks']	= 1;

		return $info;
	}

	function settings()
	{
		$settings = array();
		$settings['merchantid']		= "merchantid";
		$settings['testmode']		= 0;
		$settings['currency']		= 'EUR';
		$settings['testmodestage']	= 1;
		$settings['bank']			= "ing";
		$settings['subid']			= "0";
		$settings['language']		= "NL";
		$settings['key']			= "key";
		$settings['description']	= sprintf( _CFG_PROCESSOR_ITEM_NAME_DEFAULT, '[[cms_live_site]]', '[[user_name]]', '[[user_username]]' );
		$settings['customparams']	= "";

		return $settings;
	}

	function backend_settings()
	{
		$settings = array();
		$settings['aec_experimental']	= array( "p" );
		$settings['aec_insecure']		= array( "p" );
		$settings['merchantid']			= array( 'inputC' );
		$settings['testmode']			= array( 'list_yesno' );
		$settings['currency']			= array( 'list_currency' );
		$settings['testmodestage']		= array( 'inputC' );
		$settings['bank']				= array( 'list' );
		$settings['subid']				= array( 'inputC' );
		$settings['language']			= array( 'list_language' );
		$settings['key']				= array( 'inputC' );
		$settings['description']		= array( 'inputE' );
		$settings['customparams']		= array( 'inputD' );

 		$banks = array();
		$banks[] = JHTML::_('select.option', "ing", "ING" );
		$banks[] = JHTML::_('select.option', "rabo", "Rabobank" );

		if ( !empty( $this->settings['bank'] ) ) {
			$ba = $this->settings['bank'];
		} else {
			$ba = "ing";
		}

		$settings['lists']['bank']	= JHTML::_( 'select.genericlist', $banks, 'bank', 'size="2"', 'value', 'text', $ba );

		return $settings;
	}

	function createGatewayLink( $request )
	{
		if ( $this->settings['testmode'] ) {
			$sub = 'idealtest';
		} else {
			$sub = 'ideal';
		}

		if ( $this->settings['bank'] == 'ing' ) {
			$var['post_url']		= "https://" . $sub . ".secure-ing.com/ideal/mpiPayInitIng.do";
		} else {
			$var['post_url']		= "https://" . $sub . ".rabobank.nl/ideal/mpiPayInitRabo.do";
		}

		$var['merchantID']			= $this->settings['merchantid'];
		$var['subID']				= $this->settings['subid'];
		$var['purchaseID']			= substr( $request->invoice->invoice_number, 1 );

		if ( $this->settings['testmode'] ) {
			$var['amount']			= max( 1, min( 7, (int) $this->settings['testmodestage'] ) ) . '00';
		} else {
			$var['amount']			= (int) $request->int_var['amount'] * 100;
		}

		$var['currency']			= $this->settings['currency'];
		$var['language']			= strtolower( $this->settings['language'] );
		$var['description']			= substr( $this->settings['description'], 0, 32);
		$var['itemNumber1']			= $request->metaUser->userid;
		$var['itemDescription1']	= substr( $this->settings['description'], 0, 32);
		$var['itemQuantity1']		= 1;
		$var['itemPrice1']			= $var['amount'];
		$var['paymentType']			= 'ideal';
		$var['validUntil']			= date('Y-m-d\TG:i:s\Z', strtotime('+1 hour'));

		$shastring = $this->settings['key']
					.$var['merchantID']
					.$var['subID']
					.$var['amount']
					.$var['purchaseID']
					.$var['paymentType']
					.$var['validUntil']
					.$var['itemNumber1']
					.$var['itemDescription1']
					.$var['itemQuantity1']
					.$var['itemPrice1'];

		$shastring = html_entity_decode( $shastring );

		$shastring = str_replace( array("\t", "\n", "\r", " "), '', $shastring );

		$var['hash']				= sha1( $shastring );
		$var['urlSuccess']			= AECToolbox::deadsureURL( 'index.php?option=com_acctexp&amp;task=ideal_basicnotification' );
		$var['urlCancel']			= AECToolbox::deadsureURL( 'index.php?option=com_acctexp&amp;task=cancel' );
		$var['urlError']			= AECToolbox::deadsureURL( 'index.php?option=com_acctexp&amp;task=cancel' );
		$var['urlService']			= AECToolbox::deadsureURL( 'index.php' );

		return $var;
	}

	function parseNotification( $post )
	{
		$response = array();
		$response['invoice'] = 'I'.$_GET['ideal']['order'];

		return $response;
	}

	function validateNotification( $response, $post, $invoice )
	{
		$response['valid'] = 0;
		if ( !isset( $_GET['ideal']['status'] ) ) {
			return $response;
		}

		switch ( strtolower( $_GET['ideal']['status'] ) ) {
			case 'success':
				$response['valid'] = 1;
				break;
			case 'error':
				$response['error'] = $_GET['ideal'];
				break;
		}

		return $response;
	}

}

?>
