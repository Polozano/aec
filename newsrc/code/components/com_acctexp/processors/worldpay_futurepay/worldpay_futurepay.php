<?php
/**
 * @version $Id: worldpay_futurepay.php
 * @package AEC - Account Control Expiration - Membership Manager
 * @subpackage Processors - Worldpay Futurepay
 * @copyright 2007-2015 Copyright (C) David Deutsch
 * @author David Deutsch <skore@valanx.org> & Team AEC - http://www.valanx.org
 * @license GNU/GPL v.3 http://www.gnu.org/licenses/gpl.html or, at your option, any later version
 */

// Dont allow direct linking
defined('_JEXEC') or die( 'Direct Access to this location is not allowed.' );

class processor_worldpay_futurepay extends POSTprocessor
{
	public function info()
	{
		$info = array();
		$info['name']				= 'worldpay_futurepay';
		$info['longname']			= JText::_('CFG_WORLDPAY_FUTUREPAY_LONGNAME');
		$info['statement']			= JText::_('CFG_WORLDPAY_FUTUREPAY_STATEMENT');
		$info['description']		= JText::_('CFG_WORLDPAY_FUTUREPAY_DESCRIPTION');
		$info['currencies']			= AECToolbox::aecCurrencyField( true, true, true, true );
		$info['cc_list']			= 'visa,mastercard,discover,americanexpress,echeck,giropay';
		$info['recurring']			= 0;

		return $info;
	}

	public function settings()
	{
		$settings = array();
		$settings['instId']			= 'instID';
		$settings['testmode'] 		= 0;
		$settings['currency'] 		= 'USD';
		$settings['item_name']		= sprintf( JText::_('CFG_PROCESSOR_ITEM_NAME_DEFAULT'), '[[cms_live_site]]', '[[user_name]]', '[[user_username]]' );
		$settings['customparams']	= "";
		$settings['callbackPW'] 	= '';

		return $settings;
	}

	public function backend_settings()
	{
		$settings = array();

		$settings['testmode']		= array( 'toggle' );
		$settings['instId']			= array( 'inputC' );
		$settings['currency']		= array( 'list_currency' );
		$settings['info']			= array( 'fieldset' );
		$settings['item_name']		= array( 'inputE' );
		$settings['customparams']	= array( 'inputD' );
 		$settings['callbackPW']		= array( 'inputC' );

		$settings = AECToolbox::rewriteEngineInfo( null, $settings );

		return $settings;
	}

	public function createGatewayLink( $request )
	{
		$var['post_url']	= 'https://select.worldpay.com/wcc/purchase';
		if ( $this->settings['testmode'] ) {
			$var['testMode'] = '100';
		}

		$var['instId']		= $this->settings['instId'];
		$var['currency']	= $this->settings['currency'];
		$var['cartId']		= $request->invoice->invoice_number;
		$var['desc']		= AECToolbox::rewriteEngineRQ( $this->settings['item_name'], $request );

		$var['futurePayType']		= 'regular';
		$var['option']		= '0';

		$units = array( 'D' => '1', 'W' => '2', 'M' => '3', 'Y' => '4' );

		if ( isset( $units[$request->int_var['amount']['unit3']] ) ) {
			$var['intervalUnit'] = $units[$request->int_var['amount']['unit3']];
		} else {
			$var['intervalUnit'] = '1';
		}

		$var['intervalMult'] = $request->int_var['amount']['period3'];

		if ( isset( $request->int_var['amount']['amount1'] ) ) {
			$var['initialAmount'] = $request->int_var['amount']['amount1'];
		}

		$var['normalAmount'] = $request->int_var['amount']['amount1'];

		return $var;
	}

	public function parseNotification( $post )
	{
		$response = array();
		$response['invoice']			= $post['cartId'];
		$response['amount_paid']		= $post['authAmount'];
		$response['amount_currency']	= $post['authCurrency'];

		return $response;
	}

	public function validateNotification( $response, $post, $invoice )
	{
		$response['valid'] = 0;
		$response['valid'] = ( strcmp( $post['transStatus'], 'Y') === 0 );

		if ( $response['valid'] ) {
			if ( !empty( $this->settings['callbackPW'] ) ) {
				if ( isset( $post['callbackPW'] ) ) {
					if ( $this->settings['callbackPW'] != $post['callbackPW'] ) {
						$response['valid'] = 0;
						$response['pending_reason'] = 'callback Password set wrong at either Worldpay or within the AEC';
					}
				} else {
					$response['valid'] = 0;
					$response['pending_reason'] = 'no callback Password set at Worldpay!!!';
				}
			}
		}

		return $response;
	}

}
