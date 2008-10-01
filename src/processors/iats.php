<?php
/**
 * @version $Id: iats.php
 * @package AEC - Account Control Expiration - Membership Manager
 * @subpackage Processors - iATS
 * @copyright 2007-2008 Copyright (C) David Deutsch
 * @author David Deutsch <skore@skore.de> & Team AEC - http://www.globalnerd.org
 * @license GNU/GPL v.2 http://www.gnu.org/licenses/old-licenses/gpl-2.0.html or, at your option, any later version
 */

// Dont allow direct linking
defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );

class processor_iats extends XMLprocessor
{
	function info()
	{
		$info = array();
		$info['name']			= 'iats';
		$info['longname']		= _CFG_IATS_LONGNAME;
		$info['statement']		= _CFG_IATS_STATEMENT;
		$info['description']	= _CFG_IATS_DESCRIPTION;
		$info['currencies']		= 'USD';
		$info['languages']		= 'GB';
		$info['cc_list']		= 'visa,mastercard,discover,americanexpress';
		$info['recurring']		= 2;
		$info['actions']		= array('cancel');
		$info['secure']			= 1;

		return $info;
	}

	function settings()
	{
		$settings = array();
		$settings['testmode']				= 0;
		$settings['currency']				= 'USD';

		$settings['api_user']				= '';
		$settings['api_password']			= '';
		$settings['use_certificate']		= '';
		$settings['certificate_path']	= '';
		$settings['signature']				= '';
		$settings['country']				= 'US';

		$settings['item_name']				= sprintf( _CFG_PROCESSOR_ITEM_NAME_DEFAULT, '[[cms_live_site]]', '[[user_name]]', '[[user_username]]' );

		return $settings;
	}

	function backend_settings()
	{
		$settings = array();
		$settings['testmode']				= array( 'list_yesno' );
		$settings['currency']				= array( 'list_currency' );

		$settings['api_user']				= array( 'inputC' );
		$settings['api_password']			= array( 'inputC' );
		$settings['use_certificate']		= array( 'list_yesno' );
		$settings['certificate_path']		= array( 'inputC' );
		$settings['signature'] 				= array( 'inputC' );
		$settings['country'] 				= array( 'list' );

		$settings['cancel_note']			= array( 'inputE' );
		$settings['item_name']				= array( 'inputE' );

		$settings = AECToolbox::rewriteEngineInfo( null, $settings );

		return $settings;
	}

	function registerProfileTabs()
	{
		$tab			= array();
		$tab['details']	= _AEC_USERFORM_BILLING_DETAILS_NAME;

		return $tab;
	}

	function customtab_details( $request )
	{
		$invoiceparams = $request->invoice->getParams();
		$profileid = $invoiceparams['iats_customerProfileId'];

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

		$return = '<form action="' . AECToolbox::deadsureURL( '/index.php?option=com_acctexp&amp;task=iats_details', true ) . '" method="post">' . "\n";
		$return .= $this->getParamsHTML( $var ) . '<br /><br />';
		$return .= '<input type="hidden" name="userid" value="' . $request->metaUser->userid . '" />' . "\n";
		$return .= '<input type="hidden" name="task" value="subscriptiondetails" />' . "\n";
		$return .= '<input type="hidden" name="sub" value="iats_details" />' . "\n";
		$return .= '<input type="submit" class="button" value="' . _BUTTON_APPLY . '" /><br /><br />' . "\n";
		$return .= '</form>' . "\n";

		return $return;
	}

	function checkoutform( $request, $vcontent=null, $updated=null )
	{
		global $mosConfig_live_site;

		$var = array();

		if ( !empty( $vcontent ) ) {
			if ( !empty( $updated ) ) {
				$msg = _AEC_CCFORM_UPDATE2_DESC;
			} else {
				$msg = _AEC_CCFORM_UPDATE_DESC;
			}

			$var['params']['billUpdateInfo'] = array( 'p', _AEC_CCFORM_UPDATE_NAME, $msg, '' );
		}

		$values = array( 'card_type', 'card_number', 'card_exp_month', 'card_exp_year', 'card_cvv2' );
		$var = $this->getCCform( $var, $values, $vcontent );

		$values = array( 'firstname', 'lastname', 'address', 'address2', 'city', 'state_usca', 'zip', 'country_list' );
		$var = $this->getUserform( $var, $values, $request->metaUser, $vcontent );

		return $var;
	}

	function createRequestXML( $request )
	{
		global $mosConfig_live_site, $mosConfig_offset_user;

		$ppParams = $request->metaUser->meta->getProcessorParams( $request->parent->id );

		$var = array();

		$var['AgentCode']			= $this->settings['agent_code'];
		$var['Password']			= $this->settings['password'];
		$var['CustCode']			= $request->int_var['params']['customer_id'];

		$var['FirstName']			= trim( $request->int_var['params']['billFirstName'] );
		$var['LastName']			= trim( $request->int_var['params']['billLastName'] );

		$var['Address']				= $request->int_var['params']['billAddress'];
		$var['City']				= $request->int_var['params']['billCity'];
		$var['State']				= $request->int_var['params']['billState'];
		$var['ZipCode']				= $request->int_var['params']['billZip'];

		if ( is_array( $request->int_var['amount'] ) ) {
         $params = $params . "&Amount1="      .  $this->dollarAmount;

         $params = $params . "&BeginDate1="   .  $this->beginDate;
         $params = $params . "&EndDate1="     .  $this->endDate;
         $params = $params . "&ScheduleType1="     .  $this->scheduleType;
         $params = $params . "&ScheduleDate1="     .  $this->scheduleDate;
         $params = $params . "&Reoccurring1="      .  $this->reoccuringStatus;

			$var['MOP1']				= $request->int_var['params']['cardType'];
			$var['CCNum1']				= $request->int_var['params']['cardNumber'];
			$var['CCEXPIRY1']			= str_pad( $request->int_var['params']['expirationMonth'], 2, '0', STR_PAD_LEFT ).'/'.$request->int_var['params']['expirationYear'];

			$var['CVV2']				= $request->int_var['params']['cardVV2'];

			$var['Amount1']				= $request->int_var['amount'];
			$var['Reoccurring1']		= $request->int_var['amount'];

			if ( isset( $request->int_var['amount']['amount1'] ) ) {

				switch ( $unit ) {
					case 'D':
						$request->int_var['amount']['period1'] = 1;
					case 'Y':
					case 'M':
					case 'W':
						$unit =  'WEEKLY';
						break;
						return 'MONTHLY';
						break;
						return 'YEARLY';
						break;
				}

				$timestamp = time() - ($mosConfig_offset_user*3600) + $offset;
			} else {
				$timestamp = time() - $mosConfig_offset_user*3600;
			}

			$var['ProfileStartDate']    = date( 'Y-m-d', $timestamp ) . 'T' . date( 'H:i:s', $timestamp ) . 'Z';

			$full = $this->convertPeriodUnit( $request->int_var['amount']['period3'], $request->int_var['amount']['unit3'] );

			$var['ScheduleType1']		= $full['unit'];
			$var['BillingFrequency']	= $full['period'];
			$var['amt']					= $request->int_var['amount']['amount3'];
			$var['ProfileReference']	= $request->int_var['invoice'];
		} else {
			$var['Total']				= $request->int_var['amount'];
		}

		$var['InvoiceNum']				= $request->int_var['invoice'];

		$var['Version']				= "1.30";

		$content = array();
		foreach ( $var as $name => $value ) {
			$content[] .= strtoupper( $name ) . '=' . urlencode( stripslashes( $value ) );
		}

		return implode( '&', $content );
	}

	function transmitToTicketmaster( $xml, $request )
	{
		$path = "/itravel/itravel.pro";

		if ( $this->settings['server_type'] == 1 ) {
			$iats = 'iatsuk';
		} else {
			$iats = 'iats';
		}

		if ( $this->settings['testmode'] ) {
			$url = "http://www." . $iats . ".ticketmaster.com" . $path;
			$port = 80;
		} else {
			$url = "https://www." . $iats . ".ticketmaster.com" . $path;
			$port = 443;
		}

		$user_agent = "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)";

		$curlextra = array();
		$curlextra[CURLOPT_SSL_VERIFYHOST] = 2;
		$curlextra[CURLOPT_USERAGENT] = $user_agent;
		$curlextra[CURLOPT_SSL_VERIFYHOST] = 1;

		$cookieFile = "cookie" .date("his"). ".txt";

		 if ( $this->settings['server_type'] == 1 ) {
				$curlextra[CURLOPT_COOKIEFILE] = $cookieFile;
		 } else {
		 		$curlextra[CURLOPT_USERPWD] = $this->settings['agent_code'] . ":" . $this->settings['password'];
		 }

		return $this->transmitRequest( $url, $path, $xml, $port, $curlextra );
	}

	function transmitRequestXML( $xml, $request )
	{
		$response = $this->transmitToTicketmaster( $xml, $request );

		$return = array();
		$return['valid'] = false;
		$return['raw'] = $response;

		$iatsReturn = stristr( $response, "AUTHORIZATION RESULT:" );
		$iatsReturn = substr( $iatsReturn, strpos( $iatsReturn, ":" ) + 1, strpos( $iatsReturn , "<" ) - strpos( $iatsReturn , ":" ) - 1 );

		if ( $response ) {
			if ( isset( $nvpResArray['PROFILEID'] ) ) {
				$return['invoiceparams'] = array( "iats_customerProfileId" => $nvpResArray['PROFILEID'] );
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
			} else {
				$count = 0;
				while ( isset( $nvpResArray["L_SHORTMESSAGE".$count] ) ) {
						$return['error'] .= 'Error ' . $nvpResArray["L_ERRORCODE".$count] . ' = ' . $nvpResArray["L_SHORTMESSAGE".$count] . ' (' . $nvpResArray["L_LONGMESSAGE".$count] . ')' . "\n";
						$count++;
				}
			}
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

		$invoiceparams = $request->invoice->getParams();
		$profileid = $invoiceparams['iats_customerProfileId'];

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

	function deformatNVP( $nvpstr )
	{

		$intial=0;
	 	$nvpArray = array();
		while ( strlen( $nvpstr ) ) {
			//postion of Key
			$keypos= strpos($nvpstr,'=');
			//position of value
			$valuepos = strpos($nvpstr,'&') ? strpos($nvpstr,'&'): strlen($nvpstr);

			/*getting the Key and Value values and storing in a Associative Array*/
			$keyval=substr($nvpstr,$intial,$keypos);
			$valval=substr($nvpstr,$keypos+1,$valuepos-$keypos-1);
			//decoding the respose
			$nvpArray[urldecode($keyval)] =urldecode( $valval);
			$nvpstr=substr($nvpstr,$valuepos+1,strlen($nvpstr));
		}

		return $nvpArray;
	}

	function parseNotification( $post )
	{
		global $database;

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

		foreach ( $post as $key => $value ) {
			$value = urlencode( stripslashes( $value ) );
			$req .= "&$key=$value";
		}

		$fp = null;
		$fp = $this->transmitRequest( $ppurl, $path, $req, $port=443, $curlextra=null );

		$res = $fp;

		$response['responsestring'] = 'paypal_verification=' . $res . "\n" . $response['responsestring'];

		$txn_type			= $post['txn_type'];
		$receiver_email		= $post['receiver_email'];
		$payment_status		= $post['payment_status'];
		$payment_type		= $post['payment_type'];

		$response['valid'] = 0;

		if ( strcmp( $receiver_email, $this->settings['business'] ) != 0 && $this->settings['checkbusiness'] ) {
			$response['pending_reason'] = 'checkbusiness error';
		} elseif ( strcmp( $res, 'VERIFIED' ) == 0 ) {
			// Process payment: Paypal Subscription & Buy Now
			if ( ( $txn_type == 'web_accept' ) || ( $txn_type == 'subscr_payment' ) || ( $txn_type == 'recurring_payment' ) ) {

				$recurring = ( ( $txn_type == 'subscr_payment' ) || ( $txn_type == 'recurring_payment' ) );

				if ( ( strcmp( $payment_type, 'instant' ) == 0 ) && ( strcmp( $payment_status, 'Pending' ) == 0 ) ) {
					$response['pending_reason'] = $post['pending_reason'];
				} elseif ( strcmp( $payment_type, 'instant' ) == 0 && strcmp( $payment_status, 'Completed' ) == 0 ) {
					$response['valid']			= 1;
				} elseif ( strcmp( $payment_type, 'echeck' ) == 0 && strcmp( $payment_status, 'Pending' ) == 0 ) {
					if ( $this->settings['acceptpendingecheck'] ) {
						if ( is_object( $invoice ) ) {
							$invoice->setParams( array( 'acceptedpendingecheck' => 1 ) );
							$invoice->check();
							$invoice->store();
						}

						$response['valid']			= 1;
						$response['pending_reason'] = 'echeck';
					} else {
						$response['pending']		= 1;
						$response['pending_reason'] = 'echeck';
					}
				} elseif ( strcmp( $payment_type, 'echeck' ) == 0 && strcmp( $payment_status, 'Completed' ) == 0 ) {
					if ( $this->settings['acceptpendingecheck'] ) {
						if ( is_object( $invoice ) ) {
							$invoiceparams = $invoice->getParams();

							if ( isset( $invoiceparams['acceptedpendingecheck'] ) ) {
								$response['valid']		= 0;
								$response['duplicate']	= 1;

								$invoice->delParams( array( 'acceptedpendingecheck' ) );
								$invoice->check();
								$invoice->store();
							}
						} else {
							$response['valid']			= 1;
						}
					} else {
						$response['valid']			= 1;
					}
				}
			} elseif ( ( strcmp( $txn_type, 'subscr_signup' ) == 0 ) || ( strcmp( $txn_type, 'recurring_payment_profile_created' ) == 0 ) ) {
				$response['pending']		= 1;
				$response['pending_reason'] = 'silent_signup';
			} elseif ( strcmp( $txn_type, 'subscr_eot' ) == 0 ) {
				$response['eot']				= 1;
			} elseif ( strcmp( $txn_type, 'subscr_cancel' ) == 0 ) {
				$response['cancel']				= 1;
			}
		} else {
			$response['pending_reason']			= 'error: ' . $res;
		}

		return $response;
	}

}

?>