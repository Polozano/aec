<?php
/**
 * @version $Id: paypal_wpp.php
 * @package AEC - Account Control Expiration - Membership Manager
 * @subpackage Processors - PayPal Website Payments Pro
 * @copyright 2007-2008 Copyright (C) David Deutsch
 * @author David Deutsch <skore@skore.de> & Team AEC - http://www.valanx.org
 * @license GNU/GPL v.2 http://www.gnu.org/licenses/old-licenses/gpl-2.0.html or, at your option, any later version
 */

// Dont allow direct linking
( defined('_JEXEC') || defined( '_VALID_MOS' ) ) or die( 'Direct Access to this location is not allowed.' );

class processor_paypal_wpp extends XMLprocessor
{
	function info()
	{
		$info = array();
		$info['name']				= 'paypal_wpp';
		$info['longname']			= JText::_('CFG_PAYPAL_WPP_LONGNAME');
		$info['statement']			= JText::_('CFG_PAYPAL_WPP_STATEMENT');
		$info['description']		= JText::_('CFG_PAYPAL_WPP_DESCRIPTION');
		$info['currencies']			= 'EUR,USD,GBP,AUD,CAD,JPY,NZD,CHF,HKD,SGD,SEK,DKK,PLN,NOK,HUF,CZK,MXN,ILS,BRL,MYR,PHP,TWD,THB,ZAR';
		$info['languages']			= AECToolbox::getISO3166_1a2_codes();
		$info['cc_list']			= 'visa,mastercard,discover,americanexpress,echeck,giropay';
		$info['recurring']			= 2;
		$info['actions']			= array( 'cancel' => array( 'confirm' ) );
		$info['secure']				= 1;
		$info['recurring_buttons']	= 2;

		return $info;
	}

	function getActions( $invoice, $subscription )
	{
		$actions = parent::getActions( $invoice, $subscription );

		if ( ( $subscription->status == 'Cancelled' ) || ( $invoice->transaction_date == '0000-00-00 00:00:00' ) ) {
			if ( isset( $actions['cancel'] ) ) {
				unset( $actions['cancel'] );
			}
		}

		return $actions;
	}

	function settings()
	{
		$settings = array();
		$settings['testmode']			= 0;
		$settings['allow_express_checkout'] = 1;
		$settings['brokenipnmode']		= 0;
		$settings['currency']			= 'USD';

		$settings['api_user']			= '';
		$settings['api_password']		= '';
		$settings['use_certificate']	= '';
		$settings['certificate_path']	= '';
		$settings['signature']			= '';
		$settings['country']			= 'US';

		$settings['item_name']			= sprintf( JText::_('CFG_PROCESSOR_ITEM_NAME_DEFAULT'), '[[cms_live_site]]', '[[user_name]]', '[[user_username]]' );

		return $settings;
	}

	function backend_settings()
	{
		$settings = array();
		$settings['testmode']				= array( 'list_yesno' );
		$settings['brokenipnmode']			= array( 'list_yesno' );
		$settings['allow_express_checkout']	= array( 'list_yesno' );
		$settings['currency']				= array( 'list_currency' );

		$settings['api_user']				= array( 'inputC' );
		$settings['api_password']			= array( 'inputC' );
		$settings['use_certificate']		= array( 'list_yesno' );
		$settings['certificate_path']		= array( 'inputC' );
		$settings['signature'] 				= array( 'inputC' );
		$settings['country'] 				= array( 'list' );

		$settings['cancel_note']			= array( 'inputE' );
		$settings['item_name']				= array( 'inputE' );

		$country_sel = array();
		$country_sel[] = JHTML::_('select.option', 'US', 'US' );
		//$country_sel[] = JHTML::_('select.option', 'UK', 'UK' );

		$settings['lists']['country'] = JHTML::_( 'select.genericlist', $country_sel, 'paypal_wpp_country', 'size="2"', 'value', 'text', $this->settings['country'] );

		$settings = AECToolbox::rewriteEngineInfo( null, $settings );

		return $settings;
	}

	function registerProfileTabs()
	{
		$tab			= array();
		$tab['details']	= JText::_('AEC_USERFORM_BILLING_DETAILS_NAME');

		return $tab;
	}

	function customtab_details( $request )
	{
		$profileid = $request->invoice->params['paypal_wpp_customerProfileId'];

		$billfirstname	= aecGetParam( 'billFirstName', null );
		$billcardnumber	= aecGetParam( 'cardNumber', null );

		$updated = null;

		if ( !empty( $billfirstname ) && !empty( $billcardnumber ) && ( strpos( $billcardnumber, 'X' ) === false ) ) {
			$var['Method']					= 'UpdateRecurringPaymentsProfile';
			$var['Profileid']				= $profileid;

			$var['card_type']				= aecGetParam( 'cardType' );
			$var['card_number']				= aecGetParam( 'cardNumber' );
			$var['expDate']					= str_pad( aecGetParam( 'expirationMonth' ), 2, '0', STR_PAD_LEFT ) . aecGetParam( 'expirationYear' );
			$var['CardVerificationValue']	= aecGetParam( 'cardVV2' );

			$udata = array( 'firstname' => 'billFirstName', 'lastname' => 'billLastName', 'street' => 'billAddress', 'street2' => 'billAddress2',
							'city' => 'billCity', 'state' => 'billState', 'zip' => 'billZip', 'country' => 'billCountry'
							);

			foreach ( $udata as $authvar => $aecvar ) {
				$value = trim( aecGetParam( $aecvar ) );

				if ( !empty( $value ) ) {
					$var[$authvar] = $value;
				}
			}

			$result = $this->ProfileRequest( $request, $profileid, $var );

			$updated = true;
		}

		if ( $profileid ) {
			$var['Method']				= 'GetRecurringPaymentsProfileDetails';
			$var['Profileid']			= $profileid;

			$vars = $this->ProfileRequest( $request, $profileid, $var );

			$vcontent = array();
			$vcontent['card_type']		= strtolower( $vars['CREDITCARDTYPE'] );
			$vcontent['card_number']	= 'XXXX' . $vars['ACCT'];
			$vcontent['firstname']		= $vars['FIRSTNAME'];
			$vcontent['lastname']		= $vars['LASTNAME'];

			if ( isset( $vars['STREET1'] ) ) {
				$vcontent['address']		= $vars['STREET1'];
				$vcontent['address2']		= $vars['STREET2'];
			} else {
				$vcontent['address']		= $vars['STREET'];
			}

			$vcontent['city']			= $vars['CITY'];
			$vcontent['state_usca']		= $vars['STATE'];
			$vcontent['zip']			= $vars['ZIP'];
			$vcontent['country_list']	= $vars['COUNTRY'];
		} else {
			$vcontent = array();
		}

		$var = $this->checkoutform( $request, $vcontent, $updated );

		$return = '<form action="' . AECToolbox::deadsureURL( 'index.php?option=com_acctexp&amp;task=paypal_wpp_details', true ) . '" method="post">' . "\n";
		$return .= $this->getParamsHTML( $var ) . '<br /><br />';
		$return .= '<input type="hidden" name="userid" value="' . $request->metaUser->userid . '" />' . "\n";
		$return .= '<input type="hidden" name="task" value="subscriptiondetails" />' . "\n";
		$return .= '<input type="hidden" name="sub" value="paypal_wpp_details" />' . "\n";
		$return .= '<input type="submit" class="button" value="' . JText::_('BUTTON_APPLY') . '" /><br /><br />' . "\n";
		$return .= '</form>' . "\n";

		return $return;
	}

	function checkoutAction( $request, $InvoiceFactory=null )
	{
		$return = "";

		if ( !empty( $_REQUEST['PayerID'] ) && !empty( $_REQUEST['token'] ) && $this->settings['allow_express_checkout'] ) {
			$return .= '<table id="aec_checkout_params"><tbody><tr><td>';
			$return .= '<p style="float:left;text-align:left;"><strong>' . JText::_('CFG_PAYPAL_WPP_CHECKOUT_NOTE_RETURN') . '</strong></p>';
			$return .= '<form action="' . AECToolbox::deadsureURL( 'index.php?option=com_acctexp&amp;task=checkout', $this->info['secure'] ) . '" method="post">' . "\n";
			$return .= $this->getStdFormVars( $request );
			$return .= '<input type="hidden" name="express" value="1" />' . "\n";
			$return .= '<input type="hidden" name="token" value="' . $_REQUEST['token'] . '" />' . "\n";
			$return .= '<input type="hidden" name="PayerID" value="' . $_REQUEST['PayerID'] . '" />' . "\n";
			$return .= '<input type="submit" class="button" id="aec_checkout_btn" value="' . JText::_('BUTTON_CHECKOUT') . '" /><br /><br />' . "\n";
			$return .= '</form>' . "\n";
			$return .= '</td></tr></tbody></table>';
		} else {
			if ( $this->settings['allow_express_checkout'] ) {
				$return .= '<table id="aec_checkout_params"><tbody><tr><td>';
				$return .= '<p style="float:left;text-align:left;"><strong>' . JText::_('CFG_PAYPAL_WPP_CHECKOUT_NOTE_HEADLINE') . '</strong></p><p style="float:left;text-align:left;">' . JText::_('CFG_PAYPAL_WPP_CHECKOUT_NOTE_NOTE') . '</p>';
				$return .= '<form action="' . AECToolbox::deadsureURL( 'index.php?option=com_acctexp&amp;task=checkout', $this->info['secure'] ) . '" method="post">' . "\n";
				$return .= $this->getStdFormVars( $request );
				$return .= '<input type="hidden" name="express" value="1" />' . "\n";
				$return .= '<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif" class="button" id="aec_checkout_btn" value="' . JText::_('BUTTON_CHECKOUT') . '" /><br /><br />' . "\n";
				$return .= '</form>' . "\n";
				$return .= '</td></tr></tbody></table>';
			}

			$return .= parent::checkoutAction( $request, $InvoiceFactory );
		}

		return $return;
	}

	function checkoutform( $request, $vcontent=null, $updated=null )
	{
		$var = array();

		if ( !empty( $vcontent ) ) {
			if ( !empty( $updated ) ) {
				$msg = JText::_('AEC_CCFORM_UPDATE2_DESC');
			} else {
				$msg = JText::_('AEC_CCFORM_UPDATE_DESC');
			}

			$var['params']['billUpdateInfo'] = array( 'p', JText::_('AEC_CCFORM_UPDATE_NAME'), $msg, '' );
		}

		$values = array( 'card_type', 'card_number', 'card_exp_month', 'card_exp_year', 'card_cvv2' );
		$var = $this->getCCform( $var, $values, $vcontent );

		$values = array( 'firstname', 'lastname', 'address', 'address2', 'city', 'state_usca', 'zip', 'country_list' );
		$var = $this->getUserform( $var, $values, $request->metaUser, $vcontent );

		return $var;
	}

	function checkoutProcess( $request, $InvoiceFactory )
	{
		$this->sanitizeRequest( $request );

		if ( !empty( $request->int_var['params']['express'] ) && $this->settings['allow_express_checkout'] ) {
			if ( !empty( $request->int_var['params']['token'] ) ) {
				// The user has already returned from Paypal - finish the deal
				$var = $this->getPayPalVars( $request, false );

				$var['Method']			= 'DoExpressCheckoutPayment';
				$var['Version']			= '52.0';
				$var['token']			= $request->int_var['params']['token'];
				$var['PayerID']			= $request->int_var['params']['PayerID'];

				$var = $this->getPaymentVars( $var, $request );

				$xml = $this->arrayToNVP( $var );

				$response = $this->transmitRequestXML( $xml, $request );
			} else {
				$var = $this->getPayPalVars( $request, false );

				$var['Method']			= 'SetExpressCheckout';
				$var['Version']			= '52.0';
				$var['ReturnUrl']		= AECToolbox::deadsureURL( 'index.php?option=com_acctexp&task=repeatPayment&invoice='.$request->invoice->invoice_number, $this->info['secure'], true );
				$var['CancelUrl']		= AECToolbox::deadsureURL( 'index.php?option=com_acctexp&task=cancel', $this->info['secure'], true );

				$xml = $this->arrayToNVP( $var );

				$response = $this->transmitRequestXML( $xml, $request );

				if ( isset( $response['correlationid'] ) && isset( $response['token'] ) ) {
					$var = array();
					$var['cmd']			= '_express-checkout';
					$var['token']		= $response['token'];

					$var = $this->getPaymentVars( $var, $request );

					$var['RETURNURL']	= AECToolbox::deadsureURL( 'index.php?option=com_acctexp&task=repeatPayment&invoice='.$request->invoice->invoice_number, $this->info['secure'], true );
					$var['CANCELURL']	= AECToolbox::deadsureURL( 'index.php?option=com_acctexp&task=cancel', $this->info['secure'], true );

					$get = $this->arrayToNVP( $var, true );

					if ( $this->settings['testmode'] ) {
						return aecRedirect( 'https://www.sandbox.paypal.com/webscr?' . $get );
					} else {
						return aecRedirect( 'https://www.paypal.com/webscr?' . $get );
					}

					unset( $response['correlationid'] );
					unset( $response['token'] );
				} elseif ( !empty( $response['error'] ) ) {
					$response['error'] .= " - Could not retrieve token";
				} else {
					$response['error'] = "Could not retrieve token";
				}
			}
		} else {
			$db = &JFactory::getDBO();

			// Create the xml string
			$xml = $this->createRequestXML( $request );

			// Transmit xml to server
			$response = $this->transmitRequestXML( $xml, $request );

			if ( empty( $response['invoice'] ) ) {
				$response['invoice'] = $request->invoice->invoice_number;
			}

			if ( $request->invoice->invoice_number != $response['invoice'] ) {
				$request->invoice = new Invoice( $db );
				$request->invoice->loadInvoiceNumber( $response['invoice'] );
			}

			if ( isset( $response['correlationid'] ) ) {
				unset( $response['correlationid'] );
			}
		}

		return $this->checkoutResponse( $request, $response, $InvoiceFactory );
	}

	function createRequestXML( $request )
	{
		$app = JFactory::getApplication();

		$var = $this->getPayPalVars( $request );

		return $this->arrayToNVP( $var );
	}

	function getPayPalVars( $request, $regular=true )
	{
		$app = JFactory::getApplication();

		if ( is_array( $request->int_var['amount'] ) ) {
			$var['Method']			= 'CreateRecurringPaymentsProfile';
		} else {
			$var['Method']			= 'DoDirectPayment';
		}

		if ( is_array( $request->int_var['amount'] ) ) {
			$var['Version']			= '50.0';
		} else {
			$var['Version']			= '3.2';
		}

		$var['user']				= $this->settings['api_user'];
		$var['pwd']					= $this->settings['api_password'];
		$var['signature']			= $this->settings['signature'];

		$var['paymentAction']		= 'Sale';
		$var['IPaddress']			= $_SERVER['REMOTE_ADDR'];

		if ( $regular ) {
			$var['firstName']			= trim( $request->int_var['params']['billFirstName'] );
			$var['lastName']			= trim( $request->int_var['params']['billLastName'] );
			$var['creditCardType']		= $request->int_var['params']['cardType'];
			$var['acct']				= $request->int_var['params']['cardNumber'];
			$var['expDate']				= str_pad( $request->int_var['params']['expirationMonth'], 2, '0', STR_PAD_LEFT ).$request->int_var['params']['expirationYear'];

			$var['CardVerificationValue'] = $request->int_var['params']['cardVV2'];
			$var['cvv2']				= $request->int_var['params']['cardVV2'];

			$var['street']				= $request->int_var['params']['billAddress'];

			if ( !empty( $request->int_var['params']['billAddress2'] ) ) {
				$var['street2']			= $request->int_var['params']['billAddress2'];
			}

			$var['city']				= $request->int_var['params']['billCity'];
			$var['state']				= $request->int_var['params']['billState'];
			$var['zip']					= $request->int_var['params']['billZip'];
			$var['countrycode']			= $request->int_var['params']['billCountry'];
		}

		$var = $this->getPaymentVars( $var, $request );

		$var['NotifyUrl']			= AECToolbox::deadsureURL( 'index.php?option=com_acctexp&task=paypal_wppnotification', $this->info['secure'], true );
		$var['desc']				= AECToolbox::rewriteEngineRQ( $this->settings['item_name'], $request );
		$var['InvNum']				= $request->invoice->invoice_number;

		return $var;
	}

	function getPaymentVars( $var, $request )
	{
		$app = JFactory::getApplication();

		if ( is_array( $request->int_var['amount'] ) ) {
			// $var['InitAmt'] = 'Initial Amount'; // Not Supported Yet
			// $var['FailedInitAmtAction'] = 'ContinueOnFailure'; // Not Supported Yet (optional)

			if ( isset( $request->int_var['amount']['amount1'] ) ) {
				/* For now, this is not working, we have to wait until PayPal fixes this
				$trial = $this->convertPeriodUnit( $request->int_var['amount']['period1'], $request->int_var['amount']['unit1'] );

				$var['TrialBillingPeriod']		= $trial['unit'];
				$var['TrialBillingFrequency']	= $trial['period'];
				$var['TrialAmt']				= $request->int_var['amount']['amount1'];
				$var['TrialTotalBillingCycles'] = 1; // Not Fully Supported Yet
				*/

				switch ( $request->int_var['amount']['unit1'] ) {
					case 'D': $offset = $request->int_var['amount']['period1'] * 3600 * 24; break;
					case 'W': $offset = $request->int_var['amount']['period1'] * 3600 * 24 * 7; break;
					case 'M': $offset = $request->int_var['amount']['period1'] * 3600 * 24 * 31; break;
					case 'Y': $offset = $request->int_var['amount']['period1'] * 3600 * 24 * 356; break;
				}

				$timestamp = ( (int) gmdate('U') ) + $offset;
			} else {
				$timestamp = (int) gmdate('U');
			}

			$var['ProfileStartDate']    = date( 'Y-m-d', $timestamp ) . 'T' . date( 'H:i:s', $timestamp ) . 'Z';

			$full = $this->convertPeriodUnit( $request->int_var['amount']['period3'], $request->int_var['amount']['unit3'] );

			$var['BillingPeriod']		= $full['unit'];
			$var['BillingFrequency']	= $full['period'];
			$var['amt']					= $request->int_var['amount']['amount3'];
			$var['ProfileReference']	= $request->invoice->invoice_number;
		} else {
			$var['amt']					= $request->int_var['amount'];
		}

		$var['currencyCode']			= $this->settings['currency'];

		return $var;
	}

	function transmitToPayPal( $xml, $request )
	{
		$path = "/nvp";

		$url = $this->getPayPalURL( $path );

		$curlextra = array();
		$curlextra[CURLOPT_VERBOSE] = 1;
		$curlextra[CURLOPT_HEADER]	= true;

		return $this->transmitRequest( $url, $path, $xml, 443, $curlextra );
	}

	function getPayPalURL( $path )
	{
		$url = "https://api" . ( $this->settings['use_certificate'] ? "" : "-3t" );

		$url .= ( $this->settings['testmode'] ? ".sandbox" : "" );

		$url .= ".paypal.com" . $path;

		return $url;
	}

	function transmitRequestXML( $xml, $request )
	{
		$response = trim( $this->transmitToPayPal( $xml, $request ) );

		$return = array();
		$return['valid'] = false;
		$return['raw'] = $response;

		// converting NVPResponse to an Associative Array
		$nvpResArray = $this->NVPtoArray( $response );

		if ( !empty( $response ) ) {
			if ( isset( $nvpResArray['PROFILEID'] ) ) {
				$return['invoiceparams'] = array( "paypal_wpp_customerProfileId" => $nvpResArray['PROFILEID'] );
			}

			if ( strcmp( strtoupper( $nvpResArray['ACK'] ), 'SUCCESS' ) === 0 ) {
				if ( is_array( $request->int_var['amount'] ) ) {
					if ( !isset( $nvpResArray['STATUS'] ) ) {
						$return['valid'] = 1;
					} elseif ( strtoupper( $response['STATUS'] ) == 'ACTIVEPROFILE' ) {
						$return['valid'] = 1;
					} else {
						$response['pending_reason'] = 'pending';
					}
				} else {
					$return['valid'] = 1;
				}

				if ( isset( $nvpResArray['CORRELATIONID'] ) ) {
					$return['correlationid'] = $nvpResArray['CORRELATIONID'];
				}

				if ( isset( $nvpResArray['TOKEN'] ) ) {
					$return['token'] = $nvpResArray['TOKEN'];
				}
			} else {
				$return['error'] = '';

				$count = 0;
				while ( isset( $nvpResArray["L_SHORTMESSAGE".$count] ) ) {
						$return['error'] .= 'Error ' . $nvpResArray["LJText::_('ERRORCODE')".$count] . ' = ' . $nvpResArray["L_SHORTMESSAGE".$count] . ' (' . $nvpResArray["L_LONGMESSAGE".$count] . ')' . "\n";
						$count++;
				}
			}
		} else {
			$return['error'] = 'No Response from the PayPal Server';
		}

		return $return;
	}

	function convertPeriodUnit( $period, $unit )
	{
		$return = array();
		switch ( $unit ) {
			case 'D':
				$return['unit'] = 'Day';
				$return['period'] = $period;
				break;
			case 'W':
				$return['unit'] = 'Week';
				$return['period'] = $period;
				break;
			case 'M':
				$return['unit'] = 'Month';
				$return['period'] = $period;
				break;
			case 'Y':
				$return['unit'] = 'Year';
				$return['period'] = $period;
				break;
		}

		return $return;
	}

	function customaction_cancel( $request )
	{
		$var['Method']				= 'ManageRecurringPaymentsProfileStatus';
		$var['action']				= 'Cancel';
		$var['note']				= $this->settings['cancel_note'];

		$profileid = $request->invoice->params['paypal_wpp_customerProfileId'];

		$response = $this->ProfileRequest( $request, $profileid, $var );

		if ( !empty( $response ) ) {
			$return['invoice'] = $request->invoice->invoice_number;

			if ( isset( $response['PROFILEID'] ) ) {
				if ( $response['PROFILEID'] == $profileid ) {
					$return['valid'] = 0;
					$return['cancel'] = true;
				} else {
					$return['valid'] = 0;
					$return['error'] = 'Could not transmit Cancel Message - Wrong Profile ID returned';
				}
			} else {
				$return['valid'] = 0;
				$return['error'] = 'Could not transmit Cancel Message - General Failure';
			}

			return $return;
		} else {
			Payment_HTML::error( 'com_acctexp', $request->metaUser->cmsUser, $request->invoice, "An error occured while cancelling your subscription. Please contact the system administrator!", true );
		}
	}

	function ProfileRequest( $request, $profileid, $var )
	{
		$var['Version']				= '50.0';
		$var['user']				= $this->settings['api_user'];
		$var['pwd']					= $this->settings['api_password'];
		$var['signature']			= $this->settings['signature'];

		$var['profileid']			= $profileid;

		$content = array();
		foreach ( $var as $name => $value ) {
			$content[] .= strtoupper( $name ) . '=' . urlencode( $value );
		}

		$xml = implode( '&', $content );

		$response = $this->transmitToPayPal( $xml, $request );

		return $this->deformatNVP( $response );
	}

	function parseNotification( $post )
	{
		$db = &JFactory::getDBO();

		$mc_gross			= $post['mc_gross'];
		if ( $mc_gross == '' ) {
			$mc_gross 		= $post['mc_amount1'];
		}
		$mc_currency		= $post['mc_currency'];

		$response = array();

		if ( !empty( $post['invoice'] ) ) {
			$response['invoice'] = $post['invoice'];
		} elseif ( !empty( $post['rp_invoice_id'] ) ) {
			$response['invoice'] = $post['rp_invoice_id'];
		}

		$response['amount_paid'] = $mc_gross;
		$response['amount_currency'] = $mc_currency;

		return $response;
	}

	function validateNotification( $response, $post, $invoice )
	{
		$path = '/cgi-bin/webscr';
		if ($this->settings['testmode']) {
			$ppurl = 'www.sandbox.paypal.com' . $path;
		} else {
			$ppurl = 'www.paypal.com' . $path;
		}

		$req = 'cmd=_notify-validate';

		if ( isset( $post['planparams'] ) ) {
			unset( $post['planparams'] );
		}

		foreach ( $post as $key => $value ) {
			$value = urlencode( stripslashes( $value ) );
			$req .= "&$key=$value";
		}

		$res = $this->transmitRequest( $ppurl, $path, $req, $port=443, $curlextra=null );

		$response['fullresponse']['paypal_verification'] = $res;

		$receiver_email	= null;
		$txn_type		= null;
		$payment_type	= null;
		$payment_status	= null;
		$reason_code	= null;
		$pending_reason	= null;

		$getposts = array( 'txn_type', 'receiver_email', 'payment_status', 'payment_type', 'reason_code', 'pending_reason' );

		foreach ( $getposts as $n ) {
			if ( isset( $post[$n] ) ) {
				$$n = $post[$n];
			} else {
				$$n = null;
			}
		}

		$response['valid'] = 0;

		if ( ( strtotime( $invoice->transaction_date ) + ( 60*60*24 ) ) > ( (int) gmdate('U') ) ) {
			// Double call -> duplicate
			$response['duplicate'] = true;

			return $response;
		}

		if ( strcmp( $receiver_email, $this->settings['business'] ) != 0 && $this->settings['checkbusiness'] ) {
			$response['pending_reason'] = 'checkbusiness error';
		} elseif ( ( strcmp( $res, 'VERIFIED' ) == 0 ) || ( empty( $res ) && !empty( $this->settings['brokenipnmode'] ) ) ) {
			if ( empty( $res ) && !empty( $this->settings['brokenipnmode'] ) ) {
				$response['fullresponse']['paypal_verification'] = "MANUAL_OVERRIDE";
			}

			$recurring = ( $txn_type == 'subscr_payment' ) || ( $txn_type == 'recurring_payment' );

			// Process payment: Paypal Subscription & Buy Now
			if ( ( $txn_type == 'web_accept' ) || $recurring ) {

				if ( ( strcmp( $payment_type, 'instant' ) == 0 ) && ( strcmp( $payment_status, 'Pending' ) == 0 ) ) {
					$response['pending_reason'] = $post['pending_reason'];
				} elseif ( strcmp( $payment_type, 'instant' ) == 0 && strcmp( $payment_status, 'Completed' ) == 0 ) {
					$response['valid']			= 1;
				} elseif ( strcmp( $payment_type, 'echeck' ) == 0 && strcmp( $payment_status, 'Pending' ) == 0 ) {
					if ( $this->settings['acceptpendingecheck'] ) {
						if ( is_object( $invoice ) ) {
							$invoice->addParams( array( 'acceptedpendingecheck' => 1 ) );
							$invoice->storeload();
						}

						$response['valid']			= 1;
					} else {
						$response['pending']		= 1;
						$response['pending_reason'] = 'echeck';
					}
				} elseif ( strcmp( $payment_type, 'echeck' ) == 0 && strcmp( $payment_status, 'Completed' ) == 0 ) {
					$response['valid']		= 1;

					if ( is_object( $invoice ) ) {
						if ( isset( $invoice->params['acceptedpendingecheck'] ) ) {
							$response['valid']		= 0;
							$response['duplicate']	= 1;
						}
					}
				}
			} elseif ( strcmp( $txn_type, 'subscr_signup' ) == 0 ) {
				$response['pending']			= 1;
				$response['pending_reason']	 = 'signup';
			} elseif ( ( strcmp( $txn_type, 'paymentreview' ) == 0 ) || ( strcmp( $pending_reason, 'paymentreview' ) == 0 ) ) {
				$response['pending']			= 1;
				$response['pending_reason']	 = 'paymentreview';
			} elseif ( strcmp( $txn_type, 'subscr_eot' ) == 0 ) {
				$response['eot']				= 1;
			} elseif ( strcmp( $txn_type, 'subscr_failed' ) == 0 ) {
				$response['null']				= 1;
				$response['explanation']		= 'Subscription Payment Failed';
			} elseif ( strcmp( $txn_type, 'subscr_cancel' ) == 0 ) {
				$response['cancel']				= 1;
			} elseif ( strcmp( $reason_code, 'refund' ) == 0 ) {
				$response['delete']				= 1;
			}
		} else {
			$response['pending_reason']			= 'error: ' . $res;
		}

		return $response;
	}

}

?>