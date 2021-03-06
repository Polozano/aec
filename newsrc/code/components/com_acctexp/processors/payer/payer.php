<?php
/**
 * @version $Id: payer.php
 * @package AEC - Account Control Expiration - Membership Manager
 * @subpackage Processors - Payer Sweden
 * @copyright 2006-2015 Copyright (C) David Deutsch
 * @author David Deutsch <skore@valanx.org> & Team AEC - http://www.valanx.org
 * @license GNU/GPL v.3 http://www.gnu.org/licenses/gpl.html or, at your option, any later version
 */

defined('_JEXEC') or die( 'Direct Access to this location is not allowed.' );

class processor_payer extends POSTprocessor
{
	public function info()
	{
		$info = array();
		$info['name']				= 'payer';
		$info['longname']			= JText::_('CFG_PAYER_LONGNAME');
		$info['statement']			= JText::_('CFG_PAYER_STATEMENT');
		$info['description'] 		= JText::_('CFG_PAYER_DESCRIPTION');
		$info['currencies']			= 'EUR,USD,GBP,AUD,CAD,JPY,NZD,CHF,HKD,SGD,SEK,DKK,PLN,NOK,HUF,CZK';
		$info['languages']			= 'GB,DE,FR,IT,ES,US,SV';
		$info['cc_list']			= 'visa,mastercard';
		$info['recurring']			= 0;

		return $info;
	}

	public function settings()
	{
		$settings = array();

		$settings['testmode']		= 0;
		$settings['debugmode']		= 'silent';

		$settings['agentid']		= '';
		$settings['key1']			= '';
		$settings['key2']			= '';
		$settings['invoice_tax']	= 0;
		$settings['tax']			= '';

		$settings['currency']		= 'SEK';
		$settings['language']		= 'sv';
		$settings['payment_method']	= 'card';
		$settings['item_name']		= sprintf( JText::_('CFG_PROCESSOR_ITEM_NAME_DEFAULT'), '[[cms_live_site]]', '[[user_name]]', '[[user_username]]' );

		return $settings;
	}

	public function backend_settings()
	{
		$settings = array();
		$settings['testmode']		= array( 'toggle' );
		$settings['debugmode']		= array( 'list' );

		$settings['agentid']		= array( 'inputC' );
		$settings['key1']			= array( 'inputE' );
		$settings['key2']			= array( 'inputE' );

		$settings['invoice_tax']	= array( 'toggle' );
		$settings['tax']			= array( 'inputA' );
		$settings['currency']		= array( 'list_currency' );
		$settings['language']		= array( 'list_language' );
		$settings['payment_method']	= array( 'list' );
		$settings['item_name']		= array( 'inputE' );

 		$debugmode = array();
		$debugmode[] = JHTML::_('select.option', "silent", "silent" );
		$debugmode[] = JHTML::_('select.option', "brief", "brief" );
		$debugmode[] = JHTML::_('select.option', "verbose", "verbose" );

		$settings['lists']['debugmode'] = JHTML::_( 'select.genericlist', $debugmode, 'payer_debugmode', 'size="3"', 'value', 'text', $this->settings['debugmode'] );

 		$payment_method = array();
		$payment_method[] = JHTML::_('select.option', "sms", "sms" );
		$payment_method[] = JHTML::_('select.option', "card", "card" );
		$payment_method[] = JHTML::_('select.option', "bank", "bank" );
		$payment_method[] = JHTML::_('select.option', "phone", "phone" );
		$payment_method[] = JHTML::_('select.option', "invoice", "invoice" );

		$pm = explode( ';', $this->settings['payment_method'] );
		foreach ( $pm as $name ) {
			$selected_methods[] = JHTML::_('select.option', $name, $name );
		}

		$settings['lists']['payment_method'] = JHTML::_( 'select.genericlist', $payment_method, 'payer_payment_method', 'size="5" multiple="multiple"', 'value', 'text', $selected_methods );

		$settings = AECToolbox::rewriteEngineInfo( null, $settings );

		return $settings;
	}

	public function createGatewayLink( $request )
	{
		$baseurl		= AECToolbox::deadsureURL( 'index.php?option=com_acctexp&amp;task=payernotification', false, true );
		$Auth_url		= $baseurl . '&action=authenticate';
		$Settle_url		= $baseurl . '&action=settle';
		$Success_url	= $request->int_var['return_url'];
		$Shop_url		= JURI::root() . "index.php";

			// Explode Name
			$namearray		= explode( " ", $request->metaUser->cmsUser->name );
			$firstfirstname	= $namearray[0];
			$maxname		= count($namearray) - 1;
			$lastname		= $namearray[$maxname];
			unset( $namearray[$maxname] );
			$firstname = implode( ' ', $namearray );

		// Header
		$xml  = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>";
		$xml .= "<payread_post_api_0_2 ".
				"xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" ".
				"xsi:noNamespaceSchemaLocation=\"payread_post_api_0_2.xsd\"".
				">";
		// Seller details
		$xml .= "<seller_details>" .
					"<agent_id>"		. htmlspecialchars( $this->settings['agentid'] )		. "</agent_id>" .
				"</seller_details>";
		// Buyer details
		$xml .= "<buyer_details>" .
					"<first_name>"		. htmlspecialchars($firstname)			. "</first_name>" .
					"<last_name>"		. htmlspecialchars($lastname)			. "</last_name>" .
					"<address_line_1>"	. htmlspecialchars("AddressLine1")		. "</address_line_1>" .
					"<address_line_2>"	. htmlspecialchars("AddressLine2")		. "</address_line_2>" .
					"<postal_code>"		. htmlspecialchars("Postalcode")		. "</postal_code>" .
					"<city>"			. htmlspecialchars("City")				. "</city>" .
					"<country_code>"	. htmlspecialchars("CountryCode")		. "</country_code>" .
					"<phone_home>"		. htmlspecialchars("PhoneHome")			. "</phone_home>" .
					"<phone_work>"		. htmlspecialchars("PhoneWork")			. "</phone_work>" .
					"<phone_mobile>"	. htmlspecialchars("PhoneMobile")		. "</phone_mobile>" .
					"<email>"			. $request->metaUser->cmsUser->email	. "</email>" .
				"</buyer_details>";
		// Purchase
		$xml .= "<purchase>" .
					"<currency>"		. $this->settings['currency']		. "</currency>";
		// Add RefId if used
		$xml .=		"<reference_id>" . $request->invoice->invoice_number		. "</reference_id>";
		// Start the Purchase list
		$xml .=		"<purchase_list>";

		$desc = AECToolbox::rewriteEngineRQ( $this->settings['item_name'], $request );

		if ( !empty( $this->settings['invoice_tax'] ) ) {
			foreach ( $request->items->tax as $tax ) {
				$tax += $tax['cost'];
			}
		} else {
			$tax = $this->settings['tax'];
		}

		$tax = AECToolbox::correctAmount( $tax );

		// Purchase list (freeform purchases)
		$xml .= 		"<freeform_purchase>" .
							"<line_number>"			.  htmlspecialchars(1)								. "</line_number>" .
							"<description>"			.  htmlspecialchars($desc)							. "</description>" .
							"<price_including_vat>"	.  htmlspecialchars($request->int_var['amount'])	. "</price_including_vat>" .
							"<vat_percentage>"		.  htmlspecialchars($tax)							. "</vat_percentage>" .
							"<quantity>"			.  htmlspecialchars(1)								. "</quantity>" .
						"</freeform_purchase>";

		$xml .= 	"</purchase_list>" .
				"</purchase>";
		//Processing control
		$xml .=	"<processing_control>" .
					"<success_redirect_url>"		. htmlspecialchars($this->mySuccessRedirectUrl)			. "</success_redirect_url>" .
					"<authorize_notification_url>"	.  htmlspecialchars($this->myAuthorizeNotificationUrl)	. "</authorize_notification_url>" .
					"<settle_notification_url>"		.  htmlspecialchars($this->mySettleNotificationUrl)		. "</settle_notification_url>" .
					"<redirect_back_to_shop_url>" 	.  htmlspecialchars($this->myRedirectBackToShopUrl)		. "</redirect_back_to_shop_url>" .
				"</processing_control>";

		// Database overrides
		$xml .= "<database_overrides>";

		// Payment methods
		$xml .= 	"<accepted_payment_methods>";
		$methods = explode( ';', $this->settings["payment_method"] );
		foreach ( $methods as $method )	{
			$xml .=		"<payment_method>"		. $method		. "</payment_method>";
		}
		$xml .= 	"</accepted_payment_methods>";

		// Debug mode
		$xml .= 	"<debug_mode>"		. $this->settings['debugmode']	. "</debug_mode>";
		// Test mode
		$xml .=		"<test_mode>"		. $this->settings['testmode']		. "</test_mode>";
		// Language
		$xml .=		"<language>"		. $this->settings['language']		. "</language>";
		$xml .=		"</database_overrides>";

		// Footer
		$xml .= "</payread_post_api_0_2>";

		$var['post_url']			= "https://secure.pay-read.se/PostAPI_V1/InitPayFlow";
		$var['payread_agentid']		= $this->settings['agentid'];
		$var['payread_xml_writer']	= "payread_php_0_2";
		$var['payread_data']		= base64_encode( $xml );
		$var['payread_checksum']	= md5( $this->settings['key1'] . $xml . $this->settings['key2'] );

		return $var;
	}

	public function parseNotification( $post )
	{
		$db = JFactory::getDBO();

		$response = array();
		$response['valid']		= false;
		$response['invoice']	= aecGetParam('Invoice');

		$allowed = array( '83.241.130.100', '83.241.130.101', '10.4.49.11', '192.168.100.222', '127.0.0.1', '217.151.207.84', '83.241.130.102' );

		if ( in_array( $_SERVER["REMOTE_ADDR"], $allowed ) ) {
			$requesturl = ( ( $_SERVER["SERVER_PORT"] == "80" ) ? "http://" : "https://" ) . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];

			$strippedUrl = substr( $requesturl, 0, strpos( $requesturl, "&md5sum" ) );
			$md5 = strtolower( md5( $this->settings['key1'] . $strippedUrl . $this->settings['key2'] ) );

			if ( strpos( strtolower( $requesturl ), $md5 ) >= 7 ) {
				if ( aecGetParam('action') == 'authenticate' ) {
					$response['pending']		= 1;
					$response['pending_reason']	= 'authentication';
				} elseif ( aecGetParam('action') == 'settle' ) {
					$response['valid']			= 1;
				}
			}
		}

		return $response;
	}

}
