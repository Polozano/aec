<?php
/**
 * @version $Id: subscriptiondetails/html.php
 * @package AEC - Account Control Expiration - Membership Manager
 * @subpackage Main Frontend
 * @copyright 2012 Copyright (C) David Deutsch
 * @author David Deutsch <skore@valanx.org> & Team AEC - http://www.valanx.org
 * @license GNU/GPL v.2 http://www.gnu.org/licenses/old-licenses/gpl-2.0.html or, at your option, any later version
 */

// Dont allow direct linking
( defined('_JEXEC') || defined( '_VALID_MOS' ) ) or die( 'Direct Access to this location is not allowed.' );

if ( !$metaUser->userid ) {
	return notAllowed( $option );
}

$db = &JFactory::getDBO();

// Redirect to SSL if the config requires it
if ( !empty( $aecConfig->cfg['ssl_profile'] ) && empty( $_SERVER['HTTPS'] ) && !$aecConfig->cfg['override_reqssl'] ) {
	aecRedirect( AECToolbox::deadsureURL( "index.php?option=" . $option . "&task=subscriptiondetails", true, false ) );
	exit();
}

// Load metaUser and invoice data
$metaUser	= new metaUser( $user->id );
$invoiceno	= AECfetchfromDB::InvoiceCountbyUserID( $metaUser->userid );
$properties	= array();

$properties['showcheckout'] = false;

// Do not let the user in without a subscription or at least an invoice
if ( !$metaUser->hasSubscription && empty( $invoiceno ) ) {
	subscribe( $option );
	return;
} elseif ( !$metaUser->hasSubscription && !empty( $invoiceno ) ) {
	$properties['showcheckout'] = AECfetchfromDB::lastUnclearedInvoiceIDbyUserID( $metaUser->userid );
}

// Prepare Main Tabs
$tabs = array();
foreach ( array( 'overview', 'invoices' ) as $fname ) {
	$tabs[$fname] = JText::_( strtoupper( 'aec_subdetails_tab_' . $fname ) );
}

// If we have a cart, we want to link to it
$cart = aecCartHelper::getCartbyUserid( $metaUser->userid );

$properties['hascart']	= $cart->id;
$properties['alert']	= $metaUser->getAlertLevel();

// Load a couple of basic variables
$subscriptions	= array();
$pplist			= array();
$excludedprocs	= array( 'free', 'error' );
$custom			= null;
$mi_info		= null;

// Start off the processor list with objSubscription
if ( !empty( $metaUser->objSubscription->type ) ) {
	$pplist = array( $metaUser->objSubscription->type );
}

// The upgrade button might only show on some occasions
$properties['upgrade_button'] = true;
if ( $aecConfig->cfg['renew_button_never'] ) {
	$properties['upgrade_button'] = false;
} elseif ( $aecConfig->cfg['renew_button_nolifetimerecurring'] ) {
	if ( !empty( $metaUser->objSubscription->lifetime ) ) {
		$properties['upgrade_button'] = false;
	} elseif ( $metaUser->isRecurring() ) {
		$properties['upgrade_button'] = false;
	}
}

// Build the User Subscription List
$sList = $metaUser->getSecondarySubscriptions();
if ( !empty( $metaUser->objSubscription->plan ) ) {
	$sList = array_merge( array( $metaUser->objSubscription ), $sList );
}

$subList = array();

// Prepare Payment Processors attached to active subscriptions
if ( !empty( $sList ) ) {
	foreach( $sList as $usid => $subscription ) {
		if ( empty( $subscription->id ) || empty( $subscription->plan ) ) {
			continue;
		}

		$subList[$usid] = $subscription;

		$subList[$usid]->objPlan = new SubscriptionPlan( $db );
		$subList[$usid]->objPlan->load( $subscription->plan );

		if ( !empty( $subscription->type ) ) {
			if ( !in_array( $subscription->type, $pplist ) ) {
				$pplist[] = $subscription->type;
			}
		}
	}
}

$pagesize = 15;

$invoiceList = AECfetchfromDB::InvoiceIdList( $metaUser->userid, $page*$pagesize, $pagesize );

$properties['invoice_pages'] = (int) ( $invoiceno / $pagesize );
$properties['invoice_page'] = $page;

$invoices = array();
foreach ( $invoiceList as $invoiceid ) {
	$invoices[$invoiceid] = array();

	$invoice = new Invoice( $db );
	$invoice->load( $invoiceid );

	$rowstyle		= '';
	$actionsarray	= array();

	if ( !in_array( $invoice->method, $excludedprocs ) ) {
		$actionsarray[] = array( 	'task'	=> 'invoicePrint',
									'add'	=> 'invoice=' . $invoice->invoice_number,
									'text'	=> JText::_('HISTORY_ACTION_PRINT'),
									'insert' => ' target="_blank" ' );
	}

	if ( ( $invoice->transaction_date == '0000-00-00 00:00:00' ) || ( $invoice->subscr_id  ) ) {
		if ( $invoice->transaction_date == '0000-00-00 00:00:00' ) {
			$actionsarray[] = array( 	'task'	=> 'repeatPayment',
										'add'	=> 'invoice=' . $invoice->invoice_number . '&'. JUtility::getToken() .'=1',
										'text'	=> JText::_('HISTORY_ACTION_REPEAT') );

			if ( is_null( $invoice->fixed ) || !$invoice->fixed ) {
				$actionsarray[] = array('task'	=> 'cancelPayment',
										'add'	=> 'invoice=' . $invoice->invoice_number,
										'text'	=> JText::_('HISTORY_ACTION_CANCEL') );
			}
		}

		$rowstyle = ' style="background-color:#fee;"';
	}

	if ( !in_array( $invoice->method, $pplist ) ) {
		$pplist[] = $invoice->method;
	}

	$invoice->formatInvoiceNumber();

	$invoices[$invoiceid]['object']				= $invoice;
	$invoices[$invoiceid]['invoice_number']		= $invoice->invoice_number;
	$invoices[$invoiceid]['amount']				= $invoice->amount;
	$invoices[$invoiceid]['currency_code']		= $invoice->currency;
	$invoices[$invoiceid]['actions']			= $actionsarray;
	$invoices[$invoiceid]['rowstyle']			= $rowstyle;
	$invoices[$invoiceid]['transactiondate']	= $invoice->getTransactionStatus();
}

$pps = PaymentProcessorHandler::getObjectList( $pplist, true );

// Get the tabs information from the plan
if ( !empty( $subList ) ) {
	foreach( $subList as $usid => $subscription ) {
		$mis = $subscription->objPlan->micro_integrations;

		if ( !count( $mis ) ) {
			continue;
		}

		foreach ( $mis as $mi_id ) {
			if ( $mi_id ) {
				$mi = new MicroIntegration( $db );
				$mi->load( $mi_id );

				if ( !$mi->callIntegration() ) {
					continue;
				}

				$info = $mi->profile_info( $metaUser );
				if ( $info !== false ) {
					$mi_info .= '<div class="' . $mi->class_name . ' mi_' . $mi->id . '">' . $info . '</div>';
				}
			}

			$addtabs = $mi->registerProfileTabs();

			if ( empty( $addtabs ) ) {
				continue;
			}

			foreach ( $addtabs as $atk => $atv ) {
				$action = $mi->class_name . '_' . $atk;
				if ( isset( $subfields[$action] ) ) {
					continue;
				}

				$subfields[$action] = $atv;

				if ( $action == $sub ) {
					$custom = $mi->customProfileTab( $atk, $metaUser );
				}
			}
		}
	}
}

// Add Details tab for MI Stuff
if ( !empty( $mi_info ) ) {
	$tabs['details'] = JText::_('AEC_SUBDETAILS_TAB_DETAILS');
}

$invoiceactionlink = 'index.php?option=' . $option . '&amp;task=%s&amp;%s';

$handledsubs = array();
foreach ( $invoiceList as $invoiceid ) {
	$invoice = $invoices[$invoiceid]['object'];

	$actionsarray = $invoices[$invoiceid]['actions'];

	if ( ( $invoice->method != 'free' ) && isset( $pps[$invoice->method] ) ) {
		$pp = $pps[$invoice->method];
	} else {
		$pp = null;
	}

	if ( !empty( $pp->info['longname'] ) ) {
		$invoices[$invoiceid]['processor'] = $pp->info['longname'];
	} else {
		$invoices[$invoiceid]['processor'] = $invoice->method;
	}

	if ( !empty( $metaUser->objSubscription->status ) ) {
		$activeortrial = ( ( strcmp( $metaUser->objSubscription->status, 'Active' ) === 0 ) || ( strcmp( $metaUser->objSubscription->status, 'Trial' ) === 0 ) );
	} else {
		$activeortrial = false;
	}

	$found = false;
	if ( !in_array( $invoice->subscr_id, $handledsubs ) && !empty( $subList ) ) {
		foreach ( $subList as $ssub ) {
			if ( $ssub->id == $invoice->subscr_id ) {
				$tempsubscription = $ssub;

				$found = true;

				$handledsubs[] = $ssub->id;
				continue;
			}
		}
	}

	if ( $found ) {
		if ( !empty( $pp->info['actions'] ) && $activeortrial ) {
			$actions = $pp->getActions( $invoice, $tempsubscription );

			foreach ( $actions as $action ) {
				$actionsarray[] = array('task'		=> 'planaction',
										'add'		=> 'action=' . $action['action'] . '&amp;subscr=' . $tempsubscription->id,
										'insert'	=> $action['insert'],
										'text'		=> $action['action'] );
			}
		}
	}

	if ( !empty( $actionsarray ) ) {
		foreach ( $actionsarray as $aid => $a ) {
			if ( is_array( $a ) ) {
				$link = AECToolbox::deadsureURL( sprintf( $invoiceactionlink, $a['task'], $a['add'] ), !empty( $aecConfig->cfg['ssl_profile'] ) );

				$insert = '';
				if ( !empty( $a['insert'] ) ) {
					$insert = $a['insert'];
				}

				$actionsarray[$aid] = '<a href="' . $link . '"' . $insert . '>' . $a['text'] . '</a>';
			}
		}

		$actions = implode( ' | ', $actionsarray );
	} else {
		$actions = ' - - - ';
	}

	$invoices[$invoiceid]['actions']			= $actions;
}

// Get Custom Processor Tabs
foreach ( $pps as $pp ) {
	$pptabs = $pp->getProfileTabs();

	foreach ( $pptabs as $tname => $tcontent ) {
		if ( $sub == $tname ) {
			$custom = $pp->customProfileTab( $sub, $metaUser );
		}

		$tabs[$tname] = $tcontent;
	}
}

$trial = false;
if ( !empty( $metaUser->objSubscription->status ) ) {
	$trial = $metaUser->objSubscription->status == 'Trial';
}

$document=& JFactory::getDocument();

$document->setTitle( html_entity_decode( JText::_('MYSUBSCRIPTION_TITLE') . ' - ' . $tabs[$sub], ENT_COMPAT, 'UTF-8' ) );

if ( !empty( $tmpl->cfg['tos'] ) ) {
	$js = 'function show_confirm( msg )
			{
				var r = confirm(msg);
				return r;
			}';

	$tmpl->addScriptDeclaration( $js );
}

$tmpl->addDefaultCSS();

@include( $tmpl->tmpl( 'subscriptiondetails' ) );
