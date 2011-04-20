<?php
/**
 * @version $Id: admin.acctexp.php
 * @package AEC - Account Control Expiration - Membership Manager
 * @subpackage Main Backend
 * @copyright 2006-2010 Copyright (C) David Deutsch
 * @author David Deutsch <skore@skore.de> & Team AEC - http://www.valanx.org
 * @license GNU/GPL v.2 http://www.gnu.org/licenses/old-licenses/gpl-2.0.html or, at your option, any later version
 */

// no direct access
( defined('_JEXEC') || defined( '_VALID_MOS' ) ) or die( 'Restricted access' );

global $aecConfig;

$app = JFactory::getApplication();

require_once( JApplicationHelper::getPath( 'class' ) );
require_once( JApplicationHelper::getPath( 'admin_html' ) );

$lang =& JFactory::getLanguage();

$lang->load( 'com_acctexp.admin' );

JLoader::register('JPaneTabs',  JPATH_LIBRARIES.DS.'joomla'.DS.'html'.DS.'pane.php');

if ( !defined( '_EUCA_DEBUGMODE' ) ) {
	define( '_EUCA_DEBUGMODE', $aecConfig->cfg['debugmode'] );
}

if ( _EUCA_DEBUGMODE ) {
	global $eucaDebug;

	$eucaDebug = new eucaDebug();
}

aecACLhandler::adminBlock();

$task			= trim( aecGetParam( 'task', null ) );
$returnTask 	= trim( aecGetParam( 'returnTask', null ) );
$userid			= aecGetParam( 'userid', null );
$subscriptionid	= aecGetParam( 'subscriptionid', null );
$id				= aecGetParam( 'id', null );

if ( !is_null( $id ) ) {
	if ( !is_array( $id ) ) {
		$savid = $id;
		$id = array();
		$id[0] = $savid;
	}
}

$db = &JFactory::getDBO();

// Auto Heartbeat renew every one hour to make sure that the admin gets a view as recent as possible
$heartbeat = new aecHeartbeat( $db );
$heartbeat->backendping();

switch( strtolower( $task ) ) {
	case 'heartbeat':
	case 'beat':
		// Manual Heartbeat
		$heartbeat = new aecHeartbeat( $db );
		$heartbeat->beat();
		echo "wolves teeth";
		break;

	case 'edit':
		if ( !empty( $userid ) && !is_array( $userid ) ) {
			$temp = $userid;
			$userid = array( 0 => $temp );
		}

		if ( !empty( $subscriptionid ) ) {
			if ( !is_array( $subscriptionid ) ) {
				$sid = $subscriptionid;
				$subscriptionid = array( 0 => $sid );
			}

			$userid[0] = AECfetchfromDB::UserIDfromSubscriptionID( $subscriptionid[0] );
		}

		$page	= trim( aecGetParam( 'page', '0' ) );

		editUser( $option, $userid, $subscriptionid, $returnTask, $page );
		break;

	case 'save':
		saveUser( $option );
		break;

	case 'apply':
		saveUser( $option, 1 );
		break;

	case 'cancel':
		cancel( $option );
		break;

	case 'showcentral':
		aecCentral( $option );
		break;

	case 'clearpayment':
		$invoice	= trim( aecGetParam( 'invoice', '' ) );
		$applyplan	= trim( aecGetParam( 'applyplan', '0' ) );

		clearInvoice( $option, $invoice, $applyplan, $returnTask );
		break;

	case 'cancelpayment':
		$invoice	= trim( aecGetParam( 'invoice', '' ) );

		cancelInvoice( $option, $invoice, $returnTask );
		break;

	case 'removeclosed':
		removeClosedSubscription( $userid, $option );
		break;

	case 'removeuser':
		removeUser( $userid, $option );
		break;

	case 'removepending':
		removePendingSubscription( $userid, $option );
		break;

	case 'activatepending':
		activatePendingSubscription( $userid, $option, 0 );
		break;

	case 'renewoffline':
		activatePendingSubscription( $userid, $option, 1 );
		break;

	case 'closeactive':
		closeActiveSubscription( $userid, $option, $returnTask );
		break;

	case 'showsubscriptions':
		$planid	= trim( aecGetParam( 'plan', null ) );

		listSubscriptions( $option, 'active', $subscriptionid, $userid, $planid );
		break;

	case 'showallsubscriptions':
		$planid	= trim( aecGetParam( 'plan', null ) );

		$groups = array( 'active', 'expired', 'pending', 'cancelled', 'hold', 'closed' );

		listSubscriptions( $option, $groups, $subscriptionid, $userid, $planid );
		break;

	case 'showexcluded':
		listSubscriptions( $option, 'excluded', $subscriptionid, $userid );
		break;

	case 'showactive':
		listSubscriptions( $option, 'active', $subscriptionid, $userid );
		break;

	case 'showexpired':
		$planid	= trim( aecGetParam( 'plan', null ) );

		listSubscriptions( $option, 'expired', $subscriptionid, $userid, $planid );
		break;

	case 'showpending':
		listSubscriptions( $option, 'pending', $subscriptionid, $userid );
		break;

	case 'showcancelled':
		listSubscriptions( $option, 'cancelled', $subscriptionid, $userid );
		break;

	case 'showhold':
		listSubscriptions( $option, 'hold', $subscriptionid, $userid );
		break;

	case 'showclosed':
		listSubscriptions( $option, 'closed', $subscriptionid, $userid );
		break;

	case 'showmanual':
		listSubscriptions( $option, 'notconfig', $subscriptionid, $userid );
		break;

	case 'showsettings':
		editSettings( $option );
		break;

	case 'savesettings':
		saveSettings( $option );
		break;

	case 'applysettings':
		saveSettings( $option, 1 );
		break;

	case 'cancelsettings':
		cancelSettings( $option );
		break;

	case 'showprocessors':
		listProcessors( $option );
		break;

	case 'newprocessor':
		editProcessor( 0, $option );
		break;

	case 'editprocessor':
		editProcessor( $id[0], $option );
		break;

	case 'saveprocessor':
		saveProcessor( $option );
		break;

	case 'applyprocessor':
		saveProcessor( $option, 1 );
		break;

	case 'cancelprocessor':
		cancelProcessor( $option );
		break;

	case 'publishprocessor':
		changeProcessor( $id, 1, 'active', $option );
		break;

	case 'unpublishprocessor':
		changeProcessor( $id, 0, 'active', $option );
		break;

	case 'showsubscriptionplans':
		listSubscriptionPlans( $option );
		break;

	case 'newsubscriptionplan':
		editSubscriptionPlan( 0, $option );
		break;

	case 'editsubscriptionplan':
		editSubscriptionPlan( $id[0], $option );
		break;

	case 'copysubscriptionplan':
			$db = &JFactory::getDBO();

			if ( is_array( $id ) ) {
				foreach ( $id as $pid ) {
					$row = new SubscriptionPlan( $db );
					$row->load( $pid );
					$row->id = 0;
					$row->storeload();

					$parents = ItemGroupHandler::parentGroups( $pid, 'item' );

					foreach ( $parents as $parentid ) {
						ItemGroupHandler::setChild( $row->id, $parentid, 'item' );
					}
				}
			}

			aecRedirect( 'index.php?option='. $option . '&task=showSubscriptionPlans' );
		break;

	case 'savesubscriptionplan':
		saveSubscriptionPlan( $option );
		break;

	case 'applysubscriptionplan':
		saveSubscriptionPlan( $option, 1 );
		break;

	case 'publishsubscriptionplan':
		changeSubscriptionPlan( $id, 1, 'active', $option );
		break;

	case 'unpublishsubscriptionplan':
		changeSubscriptionPlan( $id, 0, 'active', $option );
		break;

	case 'visiblesubscriptionplan':
		changeSubscriptionPlan( $id, 1, 'visible', $option );
		break;

	case 'invisiblesubscriptionplan':
		changeSubscriptionPlan( $id, 0, 'visible', $option );
		break;

	case 'removesubscriptionplan':
		removeSubscriptionPlan( $id, $option, $returnTask );
		break;

	case 'cancelsubscriptionplan':
		cancelSubscriptionPlan( $option );
		break;

		case 'orderplanup':
			$db = &JFactory::getDBO();
			$row = new SubscriptionPlan( $db );
			$row->load( $id[0] );
			$row->move( -1 );

			aecRedirect( 'index.php?option='. $option . '&task=showSubscriptionPlans' );

			break;

		case 'orderplandown':
			$db = &JFactory::getDBO();
			$row = new SubscriptionPlan( $db );
			$row->load( $id[0] );
			$row->move( 1 );

			aecRedirect( 'index.php?option='. $option . '&task=showSubscriptionPlans' );
			break;

	case 'showitemgroups':
		listItemGroups( $option );
		break;

	case 'newitemgroup':
		editItemGroup( 0, $option );
		break;

	case 'edititemgroup':
		editItemGroup( $id[0], $option );
		break;

	case 'copyitemgroup':
			$db = &JFactory::getDBO();

			if ( is_array( $id ) ) {
				foreach ( $id as $pid ) {
					$row = new ItemGroup( $db );
					$row->load( $pid );
					$row->id = 0;
					$row->storeload();

					$parents = ItemGroupHandler::parentGroups( $pid, 'group' );

					foreach ( $parents as $parentid ) {
						ItemGroupHandler::setChild( $row->id, $parentid, 'group' );
					}
				}
			}

			aecRedirect( 'index.php?option='. $option . '&task=showItemGroups' );
		break;

	case 'saveitemgroup':
		saveItemGroup( $option );
		break;

	case 'applyitemgroup':
		saveItemGroup( $option, 1 );
		break;

	case 'publishitemgroup':
		changeItemGroup( $id, 1, 'active', $option );
		break;

	case 'unpublishitemgroup':
		changeItemGroup( $id, 0, 'active', $option );
		break;

	case 'visibleitemgroup':
		changeItemGroup( $id, 1, 'visible', $option );
		break;

	case 'invisibleitemgroup':
		changeItemGroup( $id, 0, 'visible', $option );
		break;

	case 'removeitemgroup':
		removeItemGroup( $id, $option, $returnTask );
		break;

	case 'cancelitemgroup':
		cancelItemGroup( $option );
		break;

		case 'ordergroupup':
			$db = &JFactory::getDBO();
			$row = new ItemGroup( $db );
			$row->load( $id[0] );
			$row->move( -1 );

			aecRedirect( 'index.php?option='. $option . '&task=showItemGroups' );
			break;

		case 'ordergroupdown':
			$db = &JFactory::getDBO();
			$row = new ItemGroup( $db );
			$row->load( $id[0] );
			$row->move( 1 );

			aecRedirect( 'index.php?option='. $option . '&task=showItemGroups' );
			break;

	case 'showmicrointegrations':
		listMicroIntegrations( $option );
		break;

	case 'newmicrointegration':
		editMicroIntegration( 0, $option );
		break;

	case 'editmicrointegration':
		editMicroIntegration( $id[0], $option );
		break;

	case 'savemicrointegration':
		saveMicroIntegration( $option );
		break;

	case 'applymicrointegration':
		saveMicroIntegration( $option, 1 );
		break;

	case 'copymicrointegration':
			$db = &JFactory::getDBO();

			if ( is_array( $id ) ) {
				foreach ( $id as $pid ) {
				$row = new microIntegration( $db );
				$row->load( $pid );
				$row->id = 0;
				$row->check();
				$row->store();
				}
			}

			aecRedirect( 'index.php?option='. $option . '&task=showMicroIntegrations' );
		break;

	case 'publishmicrointegration':
		changeMicroIntegration( $id, 1, $option );
		break;

	case 'unpublishmicrointegration':
		changeMicroIntegration( $id, 0, $option );
		break;

	case 'removemicrointegration':
		removeMicroIntegration( $id, $option, $returnTask );
		break;

	case 'cancelmicrointegration':
		cancelMicroIntegration( $option );
		break;

		case 'ordermiup':
			$db = &JFactory::getDBO();
			$row = new microIntegration( $db );
			$row->load( $id[0] );
			$row->move( -1 );

			$app = JFactory::getApplication();

			$app->redirect( 'index.php?option='. $option . '&task=showMicroIntegrations' );

			break;

		case 'ordermidown':
			$db = &JFactory::getDBO();
			$row = new microIntegration( $db );
			$row->load( $id[0] );
			$row->move( 1 );

			$app = JFactory::getApplication();

			$app->redirect( 'index.php?option='. $option . '&task=showMicroIntegrations' );

			break;

	case 'showcoupons':
		listCoupons( $option, 0);
		break;

	case 'copycoupon':
			$db = &JFactory::getDBO();

			if ( is_array( $id ) ) {
				foreach ( $id as $pid ) {
				$row = new Coupon( $db, 0 );
				$row->load( $pid );
				$row->id = 0;
				$row->coupon_code = $row->generateCouponCode();
				$row->check();
				$row->store();
				}
			}

			aecRedirect( 'index.php?option='. $option . '&task=showCoupons' );
		break;

	case 'newcoupon':
		editCoupon( 0, $option, 1, 0 );
		break;

	case 'editcoupon':
		editCoupon( $id[0], $option, 0, 0 );
		break;

	case 'savecoupon':
		saveCoupon( $option, 0 );
		break;

	case 'applycoupon':
		saveCoupon( $option, 0, 1 );
		break;

	case 'publishcoupon':
		changeCoupon( $id, 1, $option, 0 );
		break;

	case 'unpublishcoupon':
		changeCoupon( $id, 0, $option, 0 );
		break;

	case 'removecoupon':
		removeCoupon( $id, $option, $returnTask, 0 );
		break;

	case 'cancelcoupon':
		cancelCoupon( $option, 0 );
		break;

	case 'showcouponsstatic':
		listCoupons( $option, 1);
		break;

	case 'copycouponstatic':
		$db = &JFactory::getDBO();

		if ( is_array( $id ) ) {
			foreach ( $id as $pid ) {
			$row = new Coupon( $db, 1 );
			$row->load( $pid );
			$row->id = 0;
			$row->coupon_code = $row->generateCouponCode();
			$row->check();
			$row->store();
			}
		}

		$app = JFactory::getApplication();

		$app->redirect( 'index.php?option='. $option . '&task=showCouponsStatic' );

		break;

	case 'newcouponstatic':
		editCoupon( 0, $option, 1, 1 );
		break;

	case 'editcouponstatic':
		editCoupon( $id[0], $option, 0, 1 );
		break;

	case 'savecouponstatic':
		saveCoupon( $option, 1 );
		break;

	case 'applycouponstatic':
		saveCoupon( $option, 1, 1 );
		break;

	case 'publishcouponstatic':
		changeCoupon( $id, 1, $option, 1 );
		break;

	case 'unpublishcouponstatic':
		changeCoupon( $id, 0, $option, 1 );
		break;

	case 'removecouponstatic':
		removeCoupon( $id, $option, $returnTask, 1 );
		break;

	case 'cancelcouponstatic':
		cancelCoupon( $option, 1 );
		break;

		case 'ordercouponup':
			$db = &JFactory::getDBO();
			$row = new coupon( $db, 0 );
			$row->load( $id[0] );
			$row->move( -1 );

			$app = JFactory::getApplication();

			$app->redirect( 'index.php?option='. $option . '&task=showCoupons' );

			break;

		case 'ordercoupondown':
			$db = &JFactory::getDBO();
			$row = new coupon( $db, 0 );
			$row->load( $id[0] );
			$row->move( 1 );

			$app = JFactory::getApplication();

			$app->redirect( 'index.php?option='. $option . '&task=showCoupons' );

			break;

		case 'ordercouponstaticup':
			$db = &JFactory::getDBO();
			$row = new coupon( $db, 1 );
			$row->load( $id[0] );
			$row->move( -1 );

			$app = JFactory::getApplication();

			$app->redirect( 'index.php?option='. $option . '&task=showCouponsStatic' );

			break;

		case 'ordercouponstaticdown':
			$db = &JFactory::getDBO();
			$row = new coupon( $db, 1 );
			$row->load( $id[0] );
			$row->move( 1 );

			$app = JFactory::getApplication();

			$app->redirect( 'index.php?option='. $option . '&task=showCouponsStatic' );

			break;

	case 'editcss':
		editCSS( $option );
		break;

	case 'savecss':
		saveCSS( $option );
		break;

	case 'cancelcss':
		cancelCSS( $option );
		break;

		case 'about':
		about( );
		break;

		case 'hacks':
		$undohack	= aecGetParam( 'undohack', 0 );
		$filename	= aecGetParam( 'filename', 0 );
		$check_hack	= $filename ? 0 : 1;

		hackcorefile( $option, $filename, $check_hack, $undohack );

		HTML_AcctExp::hacks( $option, hackcorefile( $option, 0, 1, 0 ) );
		break;

	case 'invoices':
		invoices( $option );
		break;

		case 'invoiceprint':
			$invoice	= aecGetParam( 'invoice', '', true, array( 'word', 'string', 'clear_nonalnum' ) );

			AdminInvoicePrintout( $option, $invoice );
			break;

	case 'history':
		history( $option );
		break;

	case 'eventlog':
		eventlog( $option );
		break;

	case 'readout':
		readout( $option );
		break;

	case 'export':
		exportData( $option );
		break;

	case 'loadexport':
		exportData( $option, 'load' );
		break;

	case 'applyexport':
		exportData( $option, 'apply' );
		break;

	case 'exportexport':
		exportData( $option, 'export' );
		break;

	case 'saveexport':
		exportData( $option, 'save' );
		break;

	case 'import':
		importData( $option );
		break;

	case 'toolbox':
		$cmd = trim( aecGetParam( 'cmd', null ) );

		toolBoxTool( $option, $cmd );
		break;

	case 'credits':
		HTML_AcctExp::credits();
		break;

	case 'quicklookup':
		$return = quicklookup( $option );

		if ( is_array( $return ) ) {
			aecCentral( $option, $return['return'], $return['search'] );
		} elseif ( strpos( $return, '</a>' ) || strpos( $return, '</div>' ) ) {
			aecCentral( $option, $return );
		} elseif ( !empty( $return ) ) {
			aecRedirect( 'index.php?option=' . $option . '&task=edit&userid=' . $return, JText::_('AEC_QUICKSEARCH_THANKS') );
		} else {
			aecRedirect( 'index.php?option=' . $option . '&task=showcentral', JText::_('AEC_QUICKSEARCH_NOTFOUND') );
		}
		break;

	case 'readnotice':
		$db = &JFactory::getDBO();

		$query = 'UPDATE #__acctexp_eventlog'
				. ' SET `notify` = \'0\''
				. ' WHERE `id` = \'' . $id[0] . '\''
				;
		$db->setQuery( $query	);
		$db->query();

		aecCentral( $option );
		break;

	case 'readallnotices':
		$db = &JFactory::getDBO();

		$query = 'UPDATE #__acctexp_eventlog'
				. ' SET `notify` = \'0\''
				. ' WHERE `notify` = \'1\''
				;
		$db->setQuery( $query	);
		$db->query();

		aecCentral( $option );
		break;

	case 'recallinstall':
		include_once( JPATH_SITE . '/administrator/components/com_acctexp/install.acctexp.php' );
		com_install();
		break;

	case 'add': editUser( null, $userid, $option, 'notconfig' ); break;

	default:
		aecCentral( $option );
		break;
}

/**
* Central Page
*/
function aecCentral( $option, $searchresult=null, $searchcontent=null )
{
	$db = &JFactory::getDBO();

	$app = JFactory::getApplication();

	$query = 'SELECT *'
			. ' FROM #__acctexp_eventlog'
			. ' WHERE `notify` = \'1\''
			. ' ORDER BY `datetime` DESC'
			. ' LIMIT 0, 10'
			;
	$db->setQuery( $query	);
	$notices = $db->loadObjectList();

 	HTML_AcctExp::central( $searchresult, $notices, $searchcontent );
}

/**
* Cancels an edit operation
*/
function cancel( $option )
{
	$db = &JFactory::getDBO();

	$app = JFactory::getApplication();

 	$limit		= $app->getUserStateFromRequest( "viewlistlimit", 'limit', $app->getCfg( 'list_limit' ) );
	$limitstart = $app->getUserStateFromRequest( "viewnotconf{$option}limitstart", 'limitstart', 0 );
	$nexttask	= aecGetParam( 'nexttask', 'config' ) ;

	$app->redirect( 'index.php?option=' . $option . '&task=' . $nexttask, JText::_('CANCELED') );
}

function editUser( $option, $userid, $subscriptionid, $task, $page=0 )
{
	$db = &JFactory::getDBO();

	$app = JFactory::getApplication();

	$lang = JFactory::getLanguage();

	if ( !empty( $subscriptionid[0] ) ) {
		$sid = $subscriptionid[0];
	} else {
		$sid = 0;
	}

	$lists = array();

	$metaUser = new metaUser( $userid[0] );

	if ( !empty( $sid ) ) {
		$metaUser->moveFocus( $sid );
	} else {
		if ( $metaUser->hasSubscription ) {
			$sid = $metaUser->focusSubscription->id;
		}
	}

	if ( $metaUser->loadSubscriptions() && !empty( $sid ) ) {
		foreach ( $metaUser->allSubscriptions as $s_id => $s_c ) {
			if ( $s_c->id == $sid ) {
				$metaUser->allSubscriptions[$s_id]->current_focus = true;
				continue;
			}
		}
	}

	$invoices_limit = 15;

	$invoice_ids = AECfetchfromDB::InvoiceIdList( $metaUser->userid, $page*$invoices_limit, $invoices_limit );

	$group_selection = array();
	$group_selection[] = JHTML::_('select.option', '',			JText::_('EXPIRE_SET') );
	$group_selection[] = JHTML::_('select.option', 'now',		JText::_('EXPIRE_NOW') );
	$group_selection[] = JHTML::_('select.option', 'exclude',	JText::_('EXPIRE_EXCLUDE') );
	$group_selection[] = JHTML::_('select.option', 'include',	JText::_('EXPIRE_INCLUDE') );
	$group_selection[] = JHTML::_('select.option', 'close',		JText::_('EXPIRE_CLOSE') );
	$group_selection[] = JHTML::_('select.option', 'hold',		JText::_('EXPIRE_HOLD') );

	$lists['set_status'] = JHTML::_('select.genericlist', $group_selection, 'set_status', 'class="inputbox" size="1"', 'value', 'text', '' );

	$invoices = array();
	$couponsh = array();
	$invoice_counter = 0;

	foreach ( $invoice_ids as $inv_id ) {
		$invoice = new Invoice( $db );
		$invoice->load ($inv_id );

		if ( !empty( $invoice->coupons ) ) {
			foreach( $invoice->coupons as $coupon_code ) {
				if ( !isset( $couponsh[$coupon_code] ) ) {
					$couponsh[$coupon_code] = couponHandler::idFromCode( $coupon_code );
				}

				$couponsh[$coupon_code]['invoices'][] = $invoice->invoice_number;
			}
		}

		if ( $invoice_counter >= $invoices_limit && ( strcmp( $invoice->transaction_date, '0000-00-00 00:00:00' ) !== 0 ) ) {
			continue;
		} else {
			$invoice_counter++;
		}

		$status = aecHTML::Icon( 'add.png' ) . HTML_AcctExp::DisplayDateInLocalTime( $invoice->created_date ) . '<br />';

		$current_status = 'uncleared';

		if ( isset( $invoice->params['deactivated'] ) ) {
			$status .= aecHTML::Icon( 'delete.png' ) . 'deactivated';
		} elseif ( isset( $invoice->params['pending_reason'] ) ) {
			if ( $lang->hasKey( 'PAYMENT_PENDING_REASON_' . strtoupper( $invoice->params['pending_reason'] ) ) ) {
				$status .= aecHTML::Icon( 'error.png' ) . JText::_( 'PAYMENT_PENDING_REASON_' . strtoupper($invoice->params['pending_reason'] ) );
			} else {
				$status .= aecHTML::Icon( 'error.png' ) . $invoice->params['pending_reason'];
			}
		} elseif ( strcmp( $invoice->transaction_date, '0000-00-00 00:00:00' ) === 0 ) {
			$status .= aecHTML::Icon( 'hourglass.png' ) . 'uncleared';
		}

		$actions	= '';
		$rowstyle	= '';

		if ( strcmp( $invoice->transaction_date, '0000-00-00 00:00:00' ) === 0 ) {
			$actions .= '<a href="'
			. AECToolbox::deadsureURL( 'index.php?option=' . $option . '&task=repeatPayment&invoice='
			. $invoice->invoice_number ) . '">'
			. aecHTML::Icon( 'arrow_redo.png' ) . "&nbsp;"
			. JText::_('USERINVOICE_ACTION_REPEAT') . '</a>'
			. '<br />'
			. '<a href="'
			. AECToolbox::deadsureURL( 'administrator/index.php?option=' . $option . '&task=cancelpayment&invoice='
			. $invoice->invoice_number . '&returnTask=edit&userid=' . $metaUser->userid ) . '">'
			. aecHTML::Icon( 'delete.png' ) . '&nbsp;'
			. JText::_('USERINVOICE_ACTION_CANCEL') . '</a>'
			. '<br />'
			. '<a href="'
			. AECToolbox::deadsureURL( 'administrator/index.php?option=' . $option . '&task=clearpayment&invoice='
			. $invoice->invoice_number . '&returnTask=edit&userid=' . $metaUser->userid ) . '">'
			. aecHTML::Icon( 'coins.png' ) . '&nbsp;'
			. JText::_('USERINVOICE_ACTION_CLEAR') . '</a>'
			. '<br />'
			. '<a href="'
			. AECToolbox::deadsureURL( 'administrator/index.php?option=' . $option . '&task=clearpayment&invoice='
			. $invoice->invoice_number . '&applyplan=1&returnTask=edit&userid=' . $metaUser->userid ) . '">'
			. aecHTML::Icon( 'coins_add.png' ) . '&nbsp;'
			. JText::_('USERINVOICE_ACTION_CLEAR_APPLY') . '</a>'
			. '<br />';
			$rowstyle = ' style="background-color:#fee;"';
		} else {
			$status .= aecHTML::Icon( 'coins.png' ) . HTML_AcctExp::DisplayDateInLocalTime( $invoice->transaction_date );
		}

		$actions	.= '<a href="'
			. AECToolbox::deadsureURL( 'administrator/index.php?option=' . $option . '&task=invoiceprint&invoice='
			. $invoice->invoice_number ) . '" target="_blank">'
			. aecHTML::Icon( 'printer.png' ) . '&nbsp;'
			. JText::_('HISTORY_ACTION_PRINT') . '</a>';

		$non_formatted = $invoice->invoice_number;
		$invoice->formatInvoiceNumber();
		$is_formatted = $invoice->invoice_number;

		if ( $non_formatted != $is_formatted ) {
			$is_formatted = $non_formatted . "\n" . '(' . $is_formatted . ')';
		}

		$invoices[$inv_id] = array();
		$invoices[$inv_id]['rowstyle']			= $rowstyle;
		$invoices[$inv_id]['invoice_number']	= $is_formatted;
		$invoices[$inv_id]['amount']			= $invoice->amount . '&nbsp;' . $invoice->currency;
		$invoices[$inv_id]['status']			= $status;
		$invoices[$inv_id]['processor']			= $invoice->method;
		$invoices[$inv_id]['usage']				= $invoice->usage;
		$invoices[$inv_id]['actions']			= $actions;
	}

	$coupons = array();

	$coupon_counter = 0;
	foreach ( $couponsh as $coupon_code => $coupon ) {
		if ( $coupon_counter >= 10 ) {
			continue;
		} else {
			$coupon_counter++;
		}

		$cc = array();
		$cc['coupon_code']	= '<a href="index.php?option=com_acctexp&amp;task=' . ( $coupon['type'] ? 'editcouponstatic' : 'editcoupon' ) . '&amp;id=' . $coupon['id'] . '">' . $coupon_code . '</a>';
		$cc['invoices']		= implode( ", ", $coupon['invoices'] );

		$coupons[] = $cc;
	}

	// get available plans
	$available_plans	= SubscriptionPlanHandler::getActivePlanList();

	$lists['assignto_plan'] = JHTML::_('select.genericlist', $available_plans, 'assignto_plan', 'size="5"', 'value', 'text', 0 );

	$userMIs = $metaUser->getUserMIs();

	$mi					= array();
	$mi['profile']		= array();
	$mi['admin']		= array();
	$mi['profile_form']	= array();
	$mi['admin_form']	= array();

	foreach ( $userMIs as $m ) {
		$pref = 'mi_'.$m->id.'_';

		$ui = $m->profile_info( $metaUser );
		if ( !empty( $ui ) ) {
			$mi['profile'][] = array( 'name' => $m->info['name'] . ' - ' . $m->name, 'info' => $ui );
		}

		$uf = $m->profile_form( $metaUser );
		if ( !empty( $uf ) ) {
			foreach ( $uf as $k => $v ) {
				$mi['profile_form'][] = $pref.$k;
				$params[$pref.$k] = $v;
			}
		}

		$ai = $m->admin_info( $metaUser );
		if ( !empty( $ai ) ) {
			$mi['admin'][] = array( 'name' => $m->info['name'] . ' - ' . $m->name, 'info' => $ai );
		}

		$af = $m->admin_form( $metaUser );
		if ( !empty( $af ) ) {
			foreach ( $af as $k => $v ) {
				$mi['admin_form'][] = $pref.$k;
				$params[$pref.$k] = $v;
			}
		}
	}

	if ( !empty( $params ) ) {
		$settings = new aecSettings ( 'userForm', 'mi' );
		$settings->fullSettingsArray( $params, array(), $lists ) ;

		// Call HTML Class
		$aecHTML = new aecHTML( $settings->settings, $settings->lists );
	} else {
		$aecHTML = new stdClass();
	}

	$aecHTML->invoice_pages	= (int) ( AECfetchfromDB::InvoiceCountbyUserID( $metaUser->userid ) / $invoices_limit );
	$aecHTML->invoice_page	= $page;
	$aecHTML->sid			= $sid;

	HTML_AcctExp::userForm( $option, $metaUser, $invoices, $coupons, $mi, $lists, $task, $aecHTML );
}

function saveUser( $option, $apply=0 )
{
	$db = &JFactory::getDBO();

	$app = JFactory::getApplication();

	$post = $_POST;

	$metaUser = new metaUser( $post['userid'] );

	if ( $metaUser->hasSubscription && !empty( $post['id'] ) ) {
		$metaUser->moveFocus( $post['id'] );
	}

	$ck_primary = aecGetParam( 'ck_primary', 'off' );

	if ( ( strcmp( $ck_primary, 'on' ) == 0 ) && !$metaUser->focusSubscription->primary ) {
		$metaUser->focusSubscription->makePrimary();
	}

	if ( !empty( $post['assignto_plan'] ) ) {
		$plan = new SubscriptionPlan( $db );
		$plan->load( $post['assignto_plan'] );

		$metaUser->establishFocus( $plan );

		$metaUser->focusSubscription->applyUsage( $post['assignto_plan'], 'none', 1 );

		// We have to reload the metaUser object because of the changes
		$metaUser = new metaUser( $post['userid'] );

		$metaUser->hasSubscription = true;
	}

	$ck_lifetime = aecGetParam( 'ck_lifetime', 'off' );

	$set_status = trim( aecGetParam( 'set_status', null ) );

	if ( !$metaUser->hasSubscription ) {
		if ( $set_status == 'exclude' ) {
			$metaUser->focusSubscription = new Subscription( $db );
			$metaUser->focusSubscription->createNew( $metaUser->userid, 'none', 0 );

			$metaUser->hasSubscription = true;
		} else {
			echo "<script> alert('".JText::_('AEC_ERR_NO_SUBSCRIPTION')."'); window.history.go(-1); </script>\n";
			exit();
		}
	}

	if ( empty( $post['assignto_plan'] ) ) {
		if ( strcmp( $ck_lifetime, 'on' ) == 0 ) {
			$metaUser->focusSubscription->expiration	= '9999-12-31 00:00:00';
			$metaUser->focusSubscription->status		= 'Active';
			$metaUser->focusSubscription->lifetime	= 1;
		} elseif ( !empty( $post['expiration'] ) ) {
			if ( $post['expiration'] != $post['expiration_check'] ) {
				if ( strpos( $post['expiration'], ':' ) === false ) {
					$metaUser->focusSubscription->expiration = $post['expiration'] . ' 00:00:00';
				} else {
					$metaUser->focusSubscription->expiration = $post['expiration'];
				}

				if ( $metaUser->focusSubscription->status == 'Trial' ) {
					$metaUser->focusSubscription->status = 'Trial';
				} else {
					$metaUser->focusSubscription->status = 'Active';
				}

				$metaUser->focusSubscription->lifetime = 0;
			}
		}
	}

	if ( !is_null( $set_status ) ) {
		if ( strcmp( $set_status, 'now' ) === 0 ) {
			$metaUser->focusSubscription->expire();
		} else {
			$statusstatus = array( 'exclude' => 'Excluded', 'close' => 'Closed', 'include' => 'Active', 'hold' => 'Hold' );

			if ( isset( $statusstatus[$set_status] ) ) {
				$metaUser->focusSubscription->setStatus( $statusstatus[$set_status] );
			}
		}
	}

	if ( !empty( $post['notes'] ) ) {
		$metaUser->focusSubscription->customparams['notes'] = $post['notes'];

		unset( $post['notes'] );
	}

	if ( $metaUser->hasSubscription ) {
		$metaUser->focusSubscription->storeload();
	}

	$userMIs = $metaUser->getUserMIs();

	if ( !empty( $userMIs ) ) {
		foreach ( $userMIs as $m ) {
			$params = array();

			$pref = 'mi_'.$m->id.'_';

			$uf = $m->profile_form( $metaUser );
			if ( !empty( $uf ) ) {
				foreach ( $uf as $k => $v ) {
					if ( isset( $post[$pref.$k] ) ) {
						$params[$k] = $post[$pref.$k];
					}
				}

				$m->profile_form_save( $metaUser, $params );
			}

			$admin_params = array();

			$af = $m->admin_form( $metaUser );
			if ( !empty( $af ) ) {
				foreach ( $af as $k => $v ) {
					if ( isset( $post[$pref.$k] ) ) {
						$admin_params[$k] = $post[$pref.$k];
					}
				}

				$m->admin_form_save( $metaUser, $admin_params );
			}

			if ( empty( $params ) ) {
				continue;
			}

			$metaUser->meta->setMIParams( $m->id, null, $params, true );
		}

		$metaUser->meta->storeload();
	}

 	$limit		= $app->getUserStateFromRequest( "viewlistlimit", 'limit', $app->getCfg( 'list_limit' ) );
	$limitstart	= $app->getUserStateFromRequest( "viewnotconf{$option}limitstart", 'limitstart', 0 );

	$nexttask	= aecGetParam( 'nexttask', 'config' ) ;
	if ( $apply ) {
		$subID = !empty($post['id']) ? $post['id'] : $metaUser->focusSubscription->id;
		aecRedirect( 'index.php?option=' . $option . '&task=edit&subscriptionid=' . $subID, JText::_('AEC_MSG_SUCESSFULLY_SAVED') );
	} else {
		aecRedirect( 'index.php?option=' . $option . '&task=' . $nexttask, JText::_('SAVED') );
	}
}

function removeUser( $userid, $option )
{
	$db = &JFactory::getDBO();

	$user = &JFactory::getUser();

	// $userid contains values corresponding to id field of #__acctexp table
	if ( !is_array( $userid ) || count( $userid ) < 1 ) {
		echo "<script> alert('" . JText::_('AEC_ALERT_SELECT_FIRST') . "'); window.history.go(-1);</script>\n";
		exit;
	}

	$userids	= implode( ',', $userid );
	$msg		= JText::_('REMOVED');

	if ( count( $userid ) ) {
		$obj = new JTableUser( $db );
		foreach ( $userid as $id ) {
			// Get REAL UserID
			$query = 'SELECT userid'
					. ' FROM #__acctexp'
					. ' WHERE `id` = ' . $id
					;
			$uid = null;
			$db->setQuery( $query );
			$uid = $db->loadResult();

			if ( $uid ) {
				$msg = aecACLhandler::userDelete( $uid, $msg );
			}
		}
	}

	aecRedirect( 'index.php?option=' . $option . '&task=showManual', $msg );
}

function removeClosedSubscription( $userid, $option )
{
	$db = &JFactory::getDBO();

	$user = &JFactory::getUser();

	$app = JFactory::getApplication();

	// $userid contains values corresponding to id field of #__acctexp table
		if ( !is_array( $userid ) || count( $userid ) < 1 ) {
			echo "<script> alert('" . JText::_('AEC_ALERT_SELECT_FIRST') . "'); window.history.go(-1);</script>\n";
			exit;
		}

	$userids = implode(',', $userid);
	$query  = 'DELETE FROM #__acctexp'
			. ' WHERE `userid` IN (' . $userids . ')'
			;
 	$db->setQuery( $query );
	if ( !$db->query() ) {
		echo "<script> alert('".$db->getErrorMsg()."'); window.history.go(-1); </script>\n";
	}

	// Delete from the payment history
	$query = 'DELETE FROM #__acctexp_log_history'
			. ' WHERE `user_id` IN (' . $userids . ')'
			;
 	$db->setQuery( $query );
	if ( !$db->query() ) {
		echo "<script> alert('".$db->getErrorMsg()."'); window.history.go(-1); </script>\n";
	}

	// CB&CBE Integration
	$tables	= array();
	$tables	= $db->getTableList();

	if ( GeneralInfoRequester::detect_component('CB') && GeneralInfoRequester::detect_component('CBE') ) {
		$query = 'DELETE FROM #__comprofiler'
				. ' WHERE `id` IN (' . $userids . ')'
				;
		$db->setQuery($query);
		$db->query();
	}

	$msg = JText::_('REMOVED');
	if ( count( $userid ) ) {
		foreach ( $userid as $id ) {
			$msg = aecACLhandler::userDelete( $userid, $msg );
		}
	}

	$query = 'DELETE FROM #__acctexp_subscr'
			. ' WHERE `userid` IN (' . $userids . ')'
			;
 	$db->setQuery( $query );
	if ( !$db->query() ) {
		echo "<script> alert('".$db->getErrorMsg()."'); window.history.go(-1); </script>\n";
	}

	aecRedirect( 'index.php?option=' . $option . '&task=showClosed', $msg );

}

function removePendingSubscription( $userid, $option )
{
	$db = &JFactory::getDBO();

	$user = &JFactory::getUser();

	$app = JFactory::getApplication();

	// $userid contains values corresponding to id field of #__acctexp table
		if ( !is_array( $userid ) || count( $userid ) < 1 ) {
			echo "<script> alert('" . JText::_('AEC_ALERT_SELECT_FIRST') . "'); window.history.go(-1);</script>\n";
			exit;
		}

	$userids = implode(',', $userid);

	$query = 'DELETE FROM #__acctexp'
			. ' WHERE `userid` IN (' . $userids . ')'
			;
 	$db->setQuery( $query );
	if ( !$db->query() ) {
		echo "<script> alert('".$db->getErrorMsg()."'); window.history.go(-1); </script>\n";
	}

	$query = 'DELETE FROM #__acctexp_log_history'
			. ' WHERE `user_id` IN (' . $userids . ')'
			;
 	$db->setQuery( $query );
	if ( !$db->query() ) {
		echo "<script> alert('".$db->getErrorMsg()."'); window.history.go(-1); </script>\n";
	}

	// CB&CBE Integration
	$tables	= array();
	$tables	= $db->getTableList();

	if ( GeneralInfoRequester::detect_component( 'CB' ) && GeneralInfoRequester::detect_component( 'CBE' ) ) {
		$query = 'DELETE FROM #__comprofiler'
				. ' WHERE `id` IN (' . $userids . ')'
				;
		$db->setQuery( $query );
		$db->query();
	}

	$msg = JText::_('REMOVED');
	if ( count( $userid ) ) {
		foreach ( $userid as $id ) {
			$msg = aecACLhandler::userDelete( $userid, $msg );
		}
	}

	$query = 'DELETE FROM #__acctexp_subscr'
			. ' WHERE `userid` IN (' . $userids . ')'
			;
 	$db->setQuery( $query );
	if ( !$db->query() ) {
		echo "<script> alert('".$db->getErrorMsg()."'); window.history.go(-1); </script>\n";
	}

	aecRedirect( 'index.php?option=' . $option . '&task=showPending', $msg );

}

function activatePendingSubscription( $userid, $option, $renew )
{
	$db = &JFactory::getDBO();

		if (!is_array( $userid ) || count( $userid ) < 1) {
			echo "<script> alert('" . JText::_('AEC_ALERT_SELECT_FIRST') . "'); window.history.go(-1);</script>\n";
			exit;
		}

	$n = 0;

	foreach ( $userid as $id ) {
		$n++;

		$user_subscription = new Subscription( $db );

		if ( $userid ) {
			$user_subscription->loadUserID( $id );
		} else {
			return;
		}

		$invoiceid = AECfetchfromDB::lastUnclearedInvoiceIDbyUserID( $id );

		if ( $invoiceid ) {
			$invoice = new Invoice( $db );
			$invoice->load( $invoiceid );
			$plan = $invoice->usage;
			$invoice->setTransactionDate();
		} else {
			$plan = $user_subscription->plan;
		}

		$renew = $user_subscription->applyUsage( $plan, 'none', 1 );
	}
	if ( $renew ) {
		// Admin confirmed an offline payment for a renew
		// He is working on the Active queue
		$msg = $n . ' ' . JText::_('AEC_MSG_SUBS_RENEWED');
		aecRedirect( 'index.php?option=' . $option . '&task=showActive', $msg );
	} else {
		// Admin confirmed an offline payment for a new subscription
		// He is working on the Pending queue
		$msg = $n . ' ' . JText::_('AEC_MSG_SUBS_ACTIVATED');
		aecRedirect( 'index.php?option=' . $option . '&task=showPending', $msg );
	}
}

function listSubscriptions( $option, $set_group, $subscriptionid, $userid=array(), $planid=null )
{
	$db = &JFactory::getDBO();

	$app = JFactory::getApplication();

	$limit			= $app->getUserStateFromRequest( "viewlistlimit", 'limit', $app->getCfg( 'list_limit' ) );
	$limitstart		= $app->getUserStateFromRequest( "viewconf{$option}limitstart", 'limitstart', 0 );

	$orderby		= $app->getUserStateFromRequest( "orderby_subscr{$option}", 'orderby_subscr', 'name ASC' );
	$search			= $app->getUserStateFromRequest( "search{$option}", 'search', '' );
	$search			= $db->getEscaped( trim( strtolower( $search ) ) );

	if ( empty( $planid ) ) {
		$filter_planid	= intval( $app->getUserStateFromRequest( "filter_planid{$option}", 'filter_planid', 0 ) );
	} else {
		$filter_planid	= $planid;
	}

	if ( !empty( $_REQUEST['groups'] ) ) {
		if ( is_array($_REQUEST['groups'] ) ) {
			$groups 	= $_REQUEST['groups'];
			$set_group	= $_REQUEST['groups'][0];
		}
	} else {
		if ( is_array( $set_group ) ) {
			$groups		= $set_group;
			$set_group	= $groups[0];
		} else {
			$groups		= array();
			$groups[]	= $set_group;
		}
	}

	if ( !empty( $orderby ) ) {
		if ( $set_group == "manual" ) {
			$forder = array(	'name ASC', 'name DESC', 'lastname ASC', 'lastname DESC', 'username ASC', 'username DESC',
								'signup_date ASC', 'signup_date DESC', 'lastpay_date ASC', 'lastpay_date DESC',
								);
		} else {
			$forder = array(	'expiration ASC', 'expiration DESC', 'lastpay_date ASC', 'lastpay_date DESC',
								'name ASC', 'name DESC', 'lastname ASC', 'lastname DESC', 'username ASC', 'username DESC',
								'signup_date ASC', 'signup_date DESC', 'lastpay_date ASC', 'lastpay_date DESC',
								'plan_name ASC', 'plan_name DESC', 'status ASC', 'status DESC', 'type ASC', 'type DESC'
								);
		}

		if ( !in_array( $orderby, $forder ) ) {
			$orderby = 'name ASC';
		}
	}

	// define displaying at html
	$action = array();
	switch( $set_group ){
		case 'active':
			$action[0]	= 'active';
			$action[1]	= JText::_('AEC_HEAD_ACTIVE_SUBS');
			break;

		case 'excluded':
			$action[0]	= 'excluded';
			$action[1]	= JText::_('AEC_HEAD_EXCLUDED_SUBS');
			break;

		case 'expired':
			$action[0]	= 'expired';
			$action[1]	= JText::_('AEC_HEAD_EXPIRED_SUBS');
			break;

		case 'pending':
			$action[0]	= 'pending';
			$action[1]	= JText::_('AEC_HEAD_PENDING_SUBS');
			break;

		case 'cancelled':
			$action[0]	= 'cancelled';
			$action[1]	= JText::_('AEC_HEAD_CANCELLED_SUBS');
			break;

		case 'hold':
			$action[0]	= 'hold';
			$action[1]	= JText::_('AEC_HEAD_HOLD_SUBS');
			break;

		case 'closed':
			$action[0]	= 'closed';
			$action[1]	= JText::_('AEC_HEAD_CLOSED_SUBS');
		break;

		case 'notconfig':
			$action[0]	= 'manual';
			$action[1]	= JText::_('AEC_HEAD_MANUAL_SUBS');
			break;
	}

	$filter		= '';
	$where		= array();
	$where_or	= array();
	$notconfig	= false;

	$planid = trim( aecGetParam( 'assign_planid', null ) );

	$users_selected = ( ( is_array( $subscriptionid ) && count( $subscriptionid ) ) || ( is_array( $userid ) && count( $userid ) ) );

	if ( !empty( $planid ) && $users_selected ) {
		$plan = new SubscriptionPlan( $db );
		$plan->load( $planid );

		if ( !empty( $subscriptionid ) ) {
			foreach ( $subscriptionid as $sid ) {
				$metaUser = new metaUser( false, $sid );

				$metaUser->establishFocus( $plan );

				$metaUser->focusSubscription->applyUsage( $planid, 'none', 1 );
			}
		}

		if ( !empty( $userid ) ) {
			foreach ( $userid as $uid ) {
				$metaUser = new metaUser( $uid );

				$metaUser->establishFocus( $plan );

				$metaUser->focusSubscription->applyUsage( $planid, 'none', 1 );

				$subscriptionid[] = $metaUser->focusSubscription->id;
			}
		}

		// Also show active users now
		if ( !in_array( 'active', $groups ) ) {
			$groups[] = 'active';
		}
	}

	$expire = trim( aecGetParam( 'set_expiration', null ) );
	if ( !is_null( $expire ) && is_array( $subscriptionid ) && count( $subscriptionid ) > 0 ) {
		foreach ( $subscriptionid as $k ) {
			$subscriptionHandler = new Subscription( $db );

			if ( !empty( $k ) ) {
				$subscriptionHandler->load( $k );
			} else {
				$subscriptionHandler->createNew( $k, '', 1 );
			}

			if ( strcmp( $expire, 'now' ) === 0) {
				$subscriptionHandler->expire();

				if ( !in_array( 'expired', $groups ) ) {
					$groups[] = 'expired';
				}
			} elseif ( strcmp( $expire, 'exclude' ) === 0 ) {
				$subscriptionHandler->setStatus( 'Excluded' );

				if ( !in_array( 'excluded', $groups ) ) {
					$groups[] = 'excluded';
				}
			} elseif ( strcmp( $expire, 'close' ) === 0 ) {
				$subscriptionHandler->setStatus( 'Closed' );

				if ( !in_array( 'closed', $groups ) ) {
					$groups[] = 'closed';
				}
			} elseif ( strcmp( $expire, 'hold' ) === 0 ) {
				$subscriptionHandler->setStatus( 'Hold' );

				if ( !in_array( 'hold', $groups ) ) {
					$groups[] = 'hold';
				}
			} elseif ( strcmp( $expire, 'include' ) === 0 ) {
				$subscriptionHandler->setStatus( 'Active' );

				if ( !in_array( 'active', $groups ) ) {
					$groups[] = 'active';
				}
			} elseif ( strcmp( $expire, 'lifetime' ) === 0 ) {
				if ( !$subscriptionHandler->is_lifetime() ) {
					$subscriptionHandler->expiration = '9999-12-31 00:00:00';
					$subscriptionHandler->lifetime = 1;
				}

				$subscriptionHandler->setStatus( 'Active' );

				if ( !in_array( 'active', $groups ) ) {
					$groups[] = 'active';
				}
			} elseif ( strpos( $expire, 'set' ) === 0 ) {
				$subscriptionHandler->setExpiration( 'M', substr( $expire, 4 ), 0 );

				$subscriptionHandler->lifetime = 0;
				$subscriptionHandler->setStatus( 'Active' );

				if ( !in_array( 'active', $groups ) ) {
					$groups[] = 'active';
				}
			} elseif ( strpos( $expire, 'add' ) === 0 ) {
				if ( $subscriptionHandler->lifetime) {
					$subscriptionHandler->setExpiration( 'M', substr( $expire, 4 ), 0 );
				} else {
					$subscriptionHandler->setExpiration( 'M', substr( $expire, 4 ), 1 );
				}

				$subscriptionHandler->lifetime = 0;
				$subscriptionHandler->setStatus( 'Active' );

				if ( !in_array( 'active', $groups ) ) {
					$groups[] = 'active';
				}
			}
		}
	}

	if ( is_array(  $groups ) ) {
		if ( in_array( 'notconfig', $groups ) ) {
 			$notconfig = true;
 			$groups = array( 'notconfig' );
		} else {
			if ( in_array( 'excluded', $groups ) ) {
				$where_or[] = "a.status = 'Excluded'";
			}
			if ( in_array( 'expired', $groups ) ) {
				$where_or[] = "a.status = 'Expired'";
			}
			if ( in_array( 'active', $groups ) ) {
				$where_or[] = "(a.status = 'Active' || a.status = 'Trial')";
			}
			if ( in_array( 'pending', $groups ) ) {
				$where_or[] = "a.status = 'Pending'";
			}
			if ( in_array( 'cancelled', $groups ) ) {
				$where_or[] = "a.status = 'Cancelled'";
			}
			if ( in_array( 'hold', $groups ) ) {
				$where_or[] = "a.status = 'Hold'";
			}
			if ( in_array( 'closed', $groups ) ) {
	 			$where_or[] = "a.status = 'Closed'";
			}
		}
	}

	if ( isset( $search ) && $search!= '' ) {
		if ( $notconfig ) {
			$where[] = "(username LIKE '%$search%' OR name LIKE '%$search%')";
		} else {
			$where[] = "(b.username LIKE '%$search%' OR b.name LIKE '%$search%')";
		}
	}

	if ( isset( $filter_planid ) && $filter_planid > 0 ) {
		if ( !$notconfig ) {
			$where[] = "(a.plan='$filter_planid')";
		}
	}

	// get the total number of records
	if ( $notconfig ) {
		$where[] = 'b.status is null';

		$query = 'SELECT count(*)'
				. ' FROM #__users AS a'
				. ' LEFT JOIN #__acctexp_subscr AS b ON a.id = b.userid'
				. (count( $where ) ? ' WHERE ' . implode( ' AND ', $where ) : '' )
				;
	} else {
		$query = 'SELECT count(*)'
				. ' FROM #__acctexp_subscr AS a'
				. ' INNER JOIN #__users AS b ON a.userid = b.id'
				;

		if ( count( $where_or ) ) {
			$where[] = ( count( $where_or ) ? '(' . implode( ' OR ', $where_or ) . ')' : '' );
		}

		$query .= (count( $where ) ? ' WHERE ' . implode( ' AND ', $where ) : '' );
	}

	$db->setQuery( $query );
	$total = $db->loadResult();

	jimport('joomla.html.pagination');
	$pageNav = new JPagination( $total, $limitstart, $limit );

	// get the subset (based on limits) of required records
	if ( $notconfig ) {
		$forder = array(	'name ASC', 'name DESC', 'lastname ASC', 'lastname DESC', 'username ASC', 'username DESC',
							'signup_date ASC', 'signup_date DESC' );

		if ( !in_array( $orderby, $forder ) ) {
			$orderby = 'name ASC';
		}

		if ( strpos( $orderby, 'lastname' ) !== false ) {
			$orderby = str_replace( 'lastname', 'SUBSTRING_INDEX(name, \' \', -1)', $orderby );
		}

		$query = 'SELECT a.id, a.name, a.username, a.registerDate as signup_date'
				. ' FROM #__users AS a'
				. ' LEFT JOIN #__acctexp_subscr AS b ON a.id = b.userid'
				. (count( $where ) ? ' WHERE ' . implode( ' AND ', $where ) : '' )
				. ' ORDER BY ' . str_replace( 'signup_date', 'registerDate', $orderby )
				. ' LIMIT ' . $pageNav->limitstart . ',' . $pageNav->limit
				;

		if ( strpos( $orderby, 'SUBSTRING_INDEX' ) !== false ) {
			$orderby = str_replace( 'SUBSTRING_INDEX(name, \' \', -1)', 'lastname', $orderby );
		}
	} else {
		if ( strpos( $orderby, 'lastname' ) !== false ) {
			$orderby = str_replace( 'lastname', 'SUBSTRING_INDEX(b.name, \' \', -1)', $orderby );
		}

		$query = 'SELECT a.*, b.name, b.username, b.email, c.name AS plan_name'
				. ' FROM #__acctexp_subscr AS a'
				. ' INNER JOIN #__users AS b ON a.userid = b.id'
				. ' LEFT JOIN #__acctexp_plans AS c ON a.plan = c.id'
				. ( count( $where ) ? ' WHERE ' . implode( ' AND ', $where ) : '' )
				. ' ORDER BY ' . $orderby
				. ' LIMIT ' . $pageNav->limitstart . ',' . $pageNav->limit
				;

		if ( strpos( $orderby, 'SUBSTRING_INDEX' ) !== false ) {
			$orderby = str_replace( 'SUBSTRING_INDEX(b.name, \' \', -1)', 'lastname', $orderby );
		}
	}

	$db->setQuery( 'SET SQL_BIG_SELECTS=1');
	$db->query();

	$db->setQuery( $query );
	$rows = $db->loadObjectList();

	if ( $db->getErrorNum() ) {
		echo $db->stderr();
		return false;
	}

	$db->setQuery( 'SET SQL_BIG_SELECTS=0');
	$db->query();

	$sel = array();
	if ( $set_group != "manual" ) {
		$sel[] = JHTML::_('select.option', 'expiration ASC',	JText::_('EXP_ASC') );
		$sel[] = JHTML::_('select.option', 'expiration DESC',	JText::_('EXP_DESC') );
	}

	$sel[] = JHTML::_('select.option', 'name ASC',			JText::_('NAME_ASC') );
	$sel[] = JHTML::_('select.option', 'name DESC',			JText::_('NAME_DESC') );
	$sel[] = JHTML::_('select.option', 'lastname ASC',		JText::_('LASTNAME_ASC') );
	$sel[] = JHTML::_('select.option', 'lastname DESC',		JText::_('LASTNAME_DESC') );
	$sel[] = JHTML::_('select.option', 'username ASC',		JText::_('LOGIN_ASC') );
	$sel[] = JHTML::_('select.option', 'username DESC',		JText::_('LOGIN_DESC') );
	$sel[] = JHTML::_('select.option', 'signup_date ASC',	JText::_('SIGNUP_ASC') );
	$sel[] = JHTML::_('select.option', 'signup_date DESC',	JText::_('SIGNUP_DESC') );

	if ( $set_group != "manual" ) {
		$sel[] = JHTML::_('select.option', 'lastpay_date ASC',	JText::_('LASTPAY_ASC') );
		$sel[] = JHTML::_('select.option', 'lastpay_date DESC',	JText::_('LASTPAY_DESC') );
		$sel[] = JHTML::_('select.option', 'plan_name ASC',		JText::_('PLAN_ASC') );
		$sel[] = JHTML::_('select.option', 'plan_name DESC',	JText::_('PLAN_DESC') );
		$sel[] = JHTML::_('select.option', 'status ASC',		JText::_('STATUS_ASC') );
		$sel[] = JHTML::_('select.option', 'status DESC',		JText::_('STATUS_DESC') );
		$sel[] = JHTML::_('select.option', 'type ASC',			JText::_('TYPE_ASC') );
		$sel[] = JHTML::_('select.option', 'type DESC',			JText::_('TYPE_DESC') );
	}

	$lists['orderNav'] = JHTML::_('select.genericlist', $sel, 'orderby_subscr', 'class="inputbox" size="1" onchange="document.adminForm.submit();"', 'value', 'text', $orderby );

	// Get list of plans for filter
	$query = 'SELECT `id`, `name`'
			. ' FROM #__acctexp_plans'
			. ' ORDER BY `ordering`'
			;
	$db->setQuery( $query );
	$db_plans = $db->loadObjectList();

	$plans[] = JHTML::_('select.option', '0', JText::_('FILTER_PLAN'), 'id', 'name' );
	if ( is_array( $db_plans ) ) {
		$plans = array_merge( $plans, $db_plans );
	}
	$lists['filterplanid']	= JHTML::_('select.genericlist', $plans, 'filter_planid', 'class="inputbox" size="1" onchange="document.adminForm.submit();"', 'id', 'name', $filter_planid );

	$plans2[] = JHTML::_('select.option', '0', JText::_('BIND_USER'), 'id', 'name' );
	if ( is_array( $db_plans ) ) {
		$plans2 = array_merge( $plans2, $db_plans );
	}
	$lists['planid']	= JHTML::_('select.genericlist', $plans2, 'assign_planid', 'class="inputbox" size="1" onchange="document.adminForm.submit();"', 'id', 'name', 0 );

	$group_selection = array();
	$group_selection[] = JHTML::_('select.option', 'excluded',	JText::_('AEC_SEL_EXCLUDED') );
	$group_selection[] = JHTML::_('select.option', 'pending',	JText::_('AEC_SEL_PENDING') );
	$group_selection[] = JHTML::_('select.option', 'active',	JText::_('AEC_SEL_ACTIVE') );
	$group_selection[] = JHTML::_('select.option', 'expired',	JText::_('AEC_SEL_EXPIRED') );
	$group_selection[] = JHTML::_('select.option', 'closed',	JText::_('AEC_SEL_CLOSED') );
	$group_selection[] = JHTML::_('select.option', 'cancelled',	JText::_('AEC_SEL_CANCELLED') );
	$group_selection[] = JHTML::_('select.option', 'hold',		JText::_('AEC_SEL_HOLD') );
	$group_selection[] = JHTML::_('select.option', 'notconfig',	JText::_('AEC_SEL_NOT_CONFIGURED') );

	$selected_groups = array();
	if ( is_array( $groups ) ) {
		foreach ($groups as $name ) {
			$selected_groups[] = JHTML::_('select.option', $name, $name );
		}
	}

	$lists['groups'] = JHTML::_('select.genericlist', $group_selection, 'groups[]', 'size="5" multiple="multiple"', 'value', 'text', $selected_groups);

	$group_selection = array();
	$group_selection[] = JHTML::_('select.option', '',			JText::_('EXPIRE_SET') );
	$group_selection[] = JHTML::_('select.option', 'now',		JText::_('EXPIRE_NOW') );
	$group_selection[] = JHTML::_('select.option', 'exclude',	JText::_('EXPIRE_EXCLUDE') );
	$group_selection[] = JHTML::_('select.option', 'lifetime',	JText::_('AEC_CMN_LIFETIME') );
	$group_selection[] = JHTML::_('select.option', 'include',	JText::_('EXPIRE_INCLUDE') );
	$group_selection[] = JHTML::_('select.option', 'close',		JText::_('EXPIRE_CLOSE') );
	$group_selection[] = JHTML::_('select.option', 'hold',		JText::_('EXPIRE_HOLD') );
	$group_selection[] = JHTML::_('select.option', 'add_1',		JText::_('EXPIRE_ADD01MONTH') );
	$group_selection[] = JHTML::_('select.option', 'add_3',		JText::_('EXPIRE_ADD03MONTH') );
	$group_selection[] = JHTML::_('select.option', 'add_12',	JText::_('EXPIRE_ADD12MONTH') );
	$group_selection[] = JHTML::_('select.option', 'set_1',		JText::_('EXPIRE_01MONTH') );
	$group_selection[] = JHTML::_('select.option', 'set_3',		JText::_('EXPIRE_03MONTH') );
	$group_selection[] = JHTML::_('select.option', 'set_12',	JText::_('EXPIRE_12MONTH') );

	$lists['set_expiration'] = JHTML::_('select.genericlist', $group_selection, 'set_expiration', 'class="inputbox" size="1" onchange="document.adminForm.submit( );"', 'value', 'text', "");

	HTML_AcctExp::listSubscriptions( $rows, $pageNav, $search, $option, $lists, $subscriptionid, $action );
}

function editSettings( $option )
{
	$db = &JFactory::getDBO();

	$user = &JFactory::getUser();

	global $aecConfig;

	// See whether we have a duplication
	if ( $aecConfig->RowDuplicationCheck() ) {
		// Clean out duplication and reload settings
		$aecConfig->CleanDuplicatedRows();
		$aecConfig = new Config_General( $db );
	}

	$lists = array();

	$currency_code_list	= AECToolbox::aecCurrencyField( true, true, true );
	$lists['currency_code_general'] = JHTML::_('select.genericlist', $currency_code_list, ( 'currency_code_general' ), 'size="10"', 'value', 'text', ( !empty( $aecConfig->cfg['currency_code_general'] ) ? $aecConfig->cfg['currency_code_general'] : '' ) );

	$available_plans	= SubscriptionPlanHandler::getActivePlanList();

	if ( !isset( $aecConfig->cfg['entry_plan'] ) ) {
		$aecConfig->cfg['entry_plan'] = 0;
	}

	$lists['entry_plan'] = JHTML::_('select.genericlist', $available_plans, 'entry_plan', 'size="' . min( 10, count( $available_plans ) + 2 ) . '"', 'value', 'text', $aecConfig->cfg['entry_plan'] );

	$gtree = aecACLhandler::getGroupTree( array( 28, 29, 30 ) );

	// Create GID related Lists
	$lists['checkout_as_gift_access'] 		= JHTML::_('select.genericlist', $gtree, 'checkout_as_gift_access', 'size="6"', 'value', 'text', $aecConfig->cfg['checkout_as_gift_access'] );

	$tab_data = array();

	$params[] = array( 'userinfobox', 32 );
	$params[] = array( 'userinfobox_sub', JText::_('CFG_GENERAL_SUB_ACCESS') );
	$params['require_subscription']			= array( 'list_yesno', 0 );
	$params['adminaccess']					= array( 'list_yesno', 0 );
	$params['manageraccess']				= array( 'list_yesno', 0 );
	$params[] = array( 'div_end', 0 );
	$params[] = array( 'userinfobox_sub', JText::_('CFG_GENERAL_SUB_SYSTEM') );
	$params['heartbeat_cycle']				= array( 'inputA', 0 );
	$params[] = array( 'div_end', 0 );
	$params[] = array( 'userinfobox_sub', JText::_('CFG_GENERAL_SUB_EMAIL') );
	$params['noemails']						= array( 'list_yesno', 0 );
	$params['nojoomlaregemails']			= array( 'list_yesno', 0 );
	$params[] = array( 'div_end', 0 );
	$params[] = array( 'userinfobox_sub', JText::_('CFG_GENERAL_SUB_DEBUG') );
	$params['curl_default']					= array( 'list_yesno', 0 );
	$params['simpleurls']					= array( 'list_yesno', 0 );
	$params['error_notification_level']		= array( 'list', 0 );
	$params['email_notification_level']		= array( 'list', 0 );
	$params[] = array( 'div_end', 0 );
	$params[] = array( '2div_end', 0 );

	$params[] = array( 'userinfobox', 33 );
	$params[] = array( 'userinfobox_sub', JText::_('CFG_GENERAL_SUB_REGFLOW') );
	$params['plans_first']					= array( 'list_yesno', 0 );
	$params['integrate_registration']		= array( 'list_yesno', 0 );
	$params['skip_confirmation']			= array( 'list_yesno', 0 );
	$params['displayccinfo']				= array( 'list_yesno', 0 );
	$params[] = array( 'div_end', 0 );
	$params[] = array( 'userinfobox_sub', JText::_('CFG_GENERAL_SUB_CONFIRMATION') );
	$params['tos']							= array( 'inputC', '' );
	$params['tos_iframe']					= array( 'list_yesno', '' );
	$params[] = array( 'div_end', 0 );
	$params[] = array( 'userinfobox_sub', JText::_('CFG_GENERAL_SUB_CHECKOUT') );
	$params['enable_coupons']				= array( 'list_yesno', 0 );
	$params['checkout_display_descriptions']	= array( 'list_yesno', '' );
	$params['checkout_as_gift']				= array( 'list_yesno', '' );
	$params['checkout_as_gift_access']		= array( 'list', '' );
	$params['confirm_as_gift']				= array( 'list_yesno', '' );
	$params[] = array( 'div_end', 0 );
	$params[] = array( '2div_end', 0 );

	$params[] = array( 'userinfobox', 32 );
	$params[] = array( 'userinfobox_sub', JText::_('CFG_GENERAL_SUB_PLANS') );
	$params['root_group']					= array( 'list', 0 );
	$params[] = array( 'div_end', 0 );
	$params[] = array( 'userinfobox_sub', 'Shopping Cart' );
	$params['enable_shoppingcart']			= array( 'list_yesno', '' );
	$params['customlink_continueshopping']	= array( 'inputC', '' );
	$params['additem_stayonpage']			= array( 'list_yesno', '' );
	$params[] = array( 'div_end', 0 );
	$params[] = array( 'userinfobox_sub', JText::_('CFG_GENERAL_SUB_PROCESSORS') );
	$params['gwlist']						= array( 'list', 0 );
	$params['standard_currency']			= array( 'list_currency', 0 );
	$params[] = array( 'div_end', 0 );
	$params[] = array( '2div_end', 0 );

	@end( $params );
	$tab_data[] = array( JText::_('CFG_TAB1_TITLE'), key( $params ), '<h2>' . JText::_('CFG_TAB1_SUBTITLE') . '</h2>' );

	$params[] = array( 'userinfobox', 48 );
	$params[] = array( 'userinfobox_sub', 'AEC' );
	$params['quicksearch_top']					= array( 'list_yesno', '' );
	$params[] = array( 'div_end', 0 );
	$params[] = array( 'userinfobox_sub', JText::_('CFG_CUSTOMIZATION_SUB_PROXY') );
	$params['use_proxy']						= array( 'list_yesno', '' );
	$params['proxy']							= array( 'inputC', '' );
	$params['proxy_port']						= array( 'inputC', '' );
	$params['proxy_username']					= array( 'inputC', '' );
	$params['proxy_password']					= array( 'inputC', '' );
	$params['gethostbyaddr']					= array( 'list_yesno', '' );
	$params[] = array( 'div_end', 0 );
	$params[] = array( 'userinfobox_sub', JText::_('CFG_CUSTOMIZATION_SUB_BUTTONS_SUB') );
	$params['renew_button_never']				= array( 'list_yesno', '' );
	$params['renew_button_nolifetimerecurring']	= array( 'list_yesno', '' );
	$params['continue_button']					= array( 'list_yesno', '' );
	$params[] = array( 'div_end', 0 );
	$params[] = array( '2div_end', 0 );

	$params[] = array( 'userinfobox', 48 );
	$params[] = array( 'userinfobox_sub', JText::_('CFG_CUSTOMIZATION_SUB_FORMAT_DATE') );
	$params['display_date_frontend']			= array( 'inputC', '%a, %d %b %Y %T %Z' );
	$params['display_date_backend']				= array( 'inputC', '%a, %d %b %Y %T %Z' );
	$params[] = array( 'div_end', 0 );
	$params[] = array( 'userinfobox_sub', JText::_('CFG_CUSTOMIZATION_SUB_FORMAT_PRICE') );
	$params['amount_currency_symbol']			= array( 'list_yesno', 0 );
	$params['amount_currency_symbolfirst']		= array( 'list_yesno', 0 );
	$params['amount_use_comma']					= array( 'list_yesno', 0 );
	$params[] = array( 'div_end', 0 );
	$params[] = array( 'userinfobox_sub', JText::_('CFG_CUSTOMIZATION_SUB_FORMAT_INUM') );
	$params['invoicenum_doformat']				= array( 'list_yesno', '' );
	$params['invoicenum_formatting']			= array( 'inputD', '' );
	$params[] = array( 'div_end', 0 );
	$params[] = array( 'userinfobox_sub', JText::_('CFG_CUSTOMIZATION_SUB_CAPTCHA') );
	$params['use_recaptcha']					= array( 'list_yesno', '' );
	$params['recaptcha_privatekey']				= array( 'inputC', '' );
	$params['recaptcha_publickey']				= array( 'inputC', '' );
	$params[] = array( 'div_end', 0 );
	$params[] = array( '2div_end', 0 );

	@end( $params );
	$tab_data[] = array( JText::_('CFG_TAB_CUSTOMIZATION_TITLE'), key( $params ), '<h2>' . JText::_('CFG_TAB_CUSTOMIZATION_SUBTITLE') . '</h2>' );

	$params[] = array( 'userinfobox_sub', JText::_('CFG_CUSTOMIZATION_INVOICE_PRINTOUT_DETAILS') );
	$params[] = array( 'accordion_start', '' );
	$params[] = array( 'accordion_itemstart', JText::_('CFG_GENERAL_INVOICE_HEADER_NAME') );
	$params['invoice_page_title']				= array( 'inputD', '' );
	$params['invoice_header']					= array( 'editor', '' );
	$params[] = array( 'div_end', '' );
	$params[] = array( 'accordion_itemstart', JText::_('CFG_GENERAL_INVOICE_AFTER_HEADER_NAME') );
	$params['invoice_after_header']				= array( 'editor', '' );
	$params[] = array( 'div_end', '' );
	$params[] = array( 'accordion_itemstart', JText::_('CFG_GENERAL_INVOICE_ADDRESS_NAME') );
	$params['invoice_address_allow_edit']		= array( 'list_yesno', '' );
	$params['invoice_address']					= array( 'inputD', '' );
	$params[] = array( 'div_end', '' );
	$params[] = array( 'accordion_itemstart', JText::_('CFG_GENERAL_INVOICE_BEFORE_CONTENT_NAME') );
	$params['invoice_before_content']			= array( 'editor', '' );
	$params[] = array( 'div_end', '' );
	$params[] = array( 'accordion_itemstart', JText::_('CFG_GENERAL_INVOICE_AFTER_CONTENT_NAME') );
	$params['invoice_after_content']			= array( 'editor', '' );
	$params[] = array( 'div_end', '' );
	$params[] = array( 'accordion_itemstart', JText::_('CFG_GENERAL_INVOICE_BEFORE_FOOTER_NAME') );
	$params['invoice_before_footer']			= array( 'editor', '' );
	$params[] = array( 'div_end', '' );
	$params[] = array( 'accordion_itemstart', JText::_('CFG_GENERAL_INVOICE_FOOTER_NAME') );
	$params['invoice_footer']					= array( 'editor', '' );
	$params[] = array( 'div_end', '' );
	$params[] = array( 'accordion_itemstart', JText::_('CFG_GENERAL_INVOICE_AFTER_FOOTER_NAME') );
	$params['invoice_after_footer']				= array( 'editor', '' );
	$params[] = array( 'div_end', '' );
	$params[] = array( 'div_end', '' );
	$params[] = array( 'div_end', 0 );

	$params = AECToolbox::rewriteEngineInfo( array(), $params );

	$params[] = array( '2div_end', 0 );

	@end( $params );
	$tab_data[] = array( JText::_('CFG_TAB_CUSTOMINVOICE_TITLE'), key( $params ), '<h2>' . JText::_('CFG_TAB_CUSTOMINVOICE_SUBTITLE') . '</h2>' );

	$params[] = array( 'userinfobox', 48 );
	$params[] = array( 'userinfobox_sub', JText::_('CFG_CUSTOMIZATION_SUB_CREDIRECT') );
	$params['customintro']						= array( 'inputC', '' );
	$params['customintro_userid']				= array( 'list_yesno', '' );
	$params['customintro_always']				= array( 'list_yesno', '' );
	$params['customthanks']						= array( 'inputC', '' );
	$params['customcancel']						= array( 'inputC', '' );
	$params['customnotallowed']					= array( 'inputC', '' );
	$params[] = array( 'div_end', 0 );
	$params[] = array( '2div_end', 0 );


	$itemidlist = array(	'cart' => array( 'view' => 'cart', 'params' => false ),
							'checkout' => array( 'view' => 'checkout', 'params' => false ),
							'confirmation' => array( 'view' => 'confirmation', 'params' => false ),
							'subscribe' => array( 'view' => 'subscribe', 'params' => false ),
							'exception' => array( 'view' => 'exception', 'params' => false ),
							'thanks' => array( 'view' => 'thanks', 'params' => false ),
							'expired' => array( 'view' => 'expired', 'params' => false ),
							'hold' => array( 'view' => 'hold', 'params' => false ),
							'notallowed' => array( 'view' => 'notallowed', 'params' => false ),
							'pending' => array( 'view' => 'pending', 'params' => false ),
							'subscriptiondetails' => array( 'view' => 'subscriptiondetails', 'params' => false ),
							'subscriptiondetails_invoices' => array( 'view' => 'subscriptiondetails', 'params' => 'sub=invoices' ),
							'subscriptiondetails_details' => array( 'view' => 'subscriptiondetails', 'params' => 'sub=details' )
							);


	$params[] = array( 'userinfobox', 48 );
	$params[] = array( 'userinfobox_sub', JText::_('CFG_CUSTOMIZATION_SUB_ITEMID') );

	foreach ( $itemidlist as $param => $xparams ) {
		$params['itemid_'.$param]				= array( 'inputB', '' );
	}

	$params[] = array( 'div_end', 0 );
	$params[] = array( '2div_end', 0 );

	$rewriteswitches							= array( 'cms', 'invoice' );
	$params = AECToolbox::rewriteEngineInfo( $rewriteswitches, $params );

	$params[] = array( 'accordion_start', '' );
	$params[] = array( 'accordion_itemstart', JText::_('CFG_GENERAL_CUSTOMTEXT_PLANS_NAME') );
	$params['customtext_plans']					= array( 'editor', '' );
	$params[] = array( 'div_end', '' );
	$params[] = array( 'accordion_itemstart', JText::_('CFG_GENERAL_CUSTOMTEXT_CONFIRM_NAME') );
	$params['custom_confirm_userdetails']		= array( 'editor', '' );
	$params['customtext_confirm_keeporiginal']	= array( 'list_yesno', '' );
	$params['customtext_confirm']				= array( 'editor', '' );
	$params[] = array( 'div_end', '' );
	$params[] = array( 'accordion_itemstart', JText::_('CFG_GENERAL_CUSTOMTEXT_CHECKOUT_NAME') );
	$params['customtext_checkout_keeporiginal']	= array( 'list_yesno', '' );
	$params['customtext_checkout']				= array( 'editor', '' );
	$params[] = array( 'div_end', '' );
	$params[] = array( 'accordion_itemstart', JText::_('CFG_GENERAL_CUSTOMTEXT_EXCEPTION_NAME') );
	$params['customtext_exception_keeporiginal']	= array( 'list_yesno', '' );
	$params['customtext_exception']				= array( 'editor', '' );
	$params[] = array( 'div_end', '' );
	$params[] = array( 'accordion_itemstart', JText::_('CFG_GENERAL_CUSTOMTEXT_NOTALLOWED_NAME') );
	$params['customtext_notallowed_keeporiginal']	= array( 'list_yesno', '' );
	$params['customtext_notallowed']			= array( 'editor', '' );
	$params[] = array( 'div_end', '' );
	$params[] = array( 'accordion_itemstart', JText::_('CFG_GENERAL_CUSTOMTEXT_PENDING_NAME') );
	$params['customtext_pending_keeporiginal']	= array( 'list_yesno', '' );
	$params['customtext_pending']				= array( 'editor', '' );
	$params[] = array( 'div_end', '' );
	$params[] = array( 'accordion_itemstart', JText::_('CFG_GENERAL_CUSTOMTEXT_HOLD_NAME') );
	$params['customtext_hold_keeporiginal']		= array( 'list_yesno', '' );
	$params['customtext_hold']					= array( 'editor', '' );
	$params[] = array( 'div_end', '' );
	$params[] = array( 'accordion_itemstart', JText::_('CFG_GENERAL_CUSTOMTEXT_EXPIRED_NAME') );
	$params['customtext_expired_keeporiginal']	= array( 'list_yesno', '' );
	$params['customtext_expired']				= array( 'editor', '' );
	$params[] = array( 'div_end', '' );
	$params[] = array( 'accordion_itemstart', JText::_('CFG_GENERAL_CUSTOMTEXT_THANKS_NAME') );
	$params['customtext_thanks_keeporiginal']	= array( 'list_yesno', '' );
	$params['customtext_thanks']				= array( 'editor', '' );
	$params[] = array( 'div_end', '' );
	$params[] = array( 'accordion_itemstart', JText::_('CFG_GENERAL_CUSTOMTEXT_CANCEL_NAME') );
	$params['customtext_cancel_keeporiginal']	= array( 'list_yesno', '' );
	$params['customtext_cancel']				= array( 'editor', '' );
	$params[] = array( 'div_end', '' );
	$params[] = array( 'div_end', '' );

	@end( $params );
	$tab_data[] = array( JText::_('CFG_TAB_CUSTOMPAGES_TITLE'), key( $params ), '<h2>' . JText::_('CFG_TAB_CUSTOMPAGES_SUBTITLE') . '</h2>' );

	$params[] = array( 'userinfobox', 32 );
	$params[] = array( 'userinfobox_sub', JText::_('CFG_GENERAL_SUB_SYSTEM') );
	$params['alertlevel2']					= array( 'inputA', 0 );
	$params['alertlevel1']					= array( 'inputA', 0 );
	$params['expiration_cushion']			= array( 'inputA', 0 );
	$params['invoice_cushion']				= array( 'inputA', 0 );
	$params['heartbeat_cycle_backend']		= array( 'inputA', 0 );
	$params['allow_frontend_heartbeat']		= array( 'list_yesno', 0 );
	$params['disable_regular_heartbeat']	= array( 'list_yesno', 0 );
	$params['custom_heartbeat_securehash']	= array( 'inputC', '' );
	$params['countries_available']			= array( 'list_country_full', 0 );
	$params['countries_top']				= array( 'list_country_full', 0 );
	$params[] = array( 'div_end', 0 );
	$params[] = array( 'userinfobox_sub', JText::_('CFG_GENERAL_SUB_DEBUG') );
	$params['bypassintegration']			= array( 'inputC', '' );

	$params['breakon_mi_error']				= array( 'list_yesno', 0 );
	$params['debugmode']					= array( 'list_yesno', 0 );
	$params['email_default_admins']			= array( 'list_yesno', 1 );
	$params['email_extra_admins']			= array( 'inputD', "" );
	$params[] = array( 'div_end', 0 );
	$params[] = array( '2div_end', 0 );

	$params[] = array( 'userinfobox', 33 );
	$params[] = array( 'userinfobox_sub', JText::_('CFG_GENERAL_SUB_REGFLOW') );
	$params['show_fixeddecision']			= array( 'list_yesno', 0 );
	$params['temp_auth_exp']				= array( 'inputC', '' );
	$params['confirmation_coupons']			= array( 'list_yesno', 0 );
	$params['intro_expired']				= array( 'list_yesno', 0 );
	$params[] = array( 'div_end', 0 );
	$params[] = array( 'userinfobox_sub', JText::_('CFG_GENERAL_SUB_CONFIRMATION') );
	$params['confirmation_changeusername']	= array( 'list_yesno', '' );
	$params['confirmation_changeusage']		= array( 'list_yesno', '' );
	$params[] = array( 'div_end', 0 );
	$params[] = array( 'userinfobox_sub', JText::_('CFG_GENERAL_SUB_CHECKOUT') );
	$params['checkoutform_jsvalidation']	= array( 'list_yesno', '' );
	$params[] = array( 'div_end', 0 );
	$params[] = array( 'userinfobox_sub', JText::_('CFG_GENERAL_SUB_SUBSCRIPTIONDETAILS') );
	$params['subscriptiondetails_menu']		= array( 'list_yesno', 1 );
	$params[] = array( 'div_end', 0 );
	$params[] = array( 'userinfobox_sub', JText::_('CFG_GENERAL_SUB_PLANS') );
	$params['root_group_rw']				= array( 'inputD', 0 );
	$params['entry_plan']					= array( 'list', 0 );
	$params['per_plan_mis']					= array( 'list_yesno', 0 );
	$params[] = array( 'div_end', 0 );
	$params[] = array( '2div_end', 0 );

	$params[] = array( 'userinfobox', 32 );
	$params[] = array( 'userinfobox_sub', JText::_('CFG_GENERAL_SUB_SECURITY') );
	$params['ssl_signup']					= array( 'list_yesno', 0 );
	$params['ssl_profile']					= array( 'list_yesno', 0 );
	$params['override_reqssl']				= array( 'list_yesno', 0 );
	$params['altsslurl']					= array( 'inputC', '' );
	$params['ssl_verifypeer']				= array( 'list_yesno', 0 );
	$params['ssl_verifyhost']				= array( 'inputC', '' );
	$params['ssl_cainfo']					= array( 'inputC', '' );
	$params['ssl_capath']					= array( 'inputC', '' );
	$params['allow_invoice_unpublished_item']				= array( 'list_yesno', 0 );
	$params[] = array( 'div_end', 0 );
	$params[] = array( 'userinfobox_sub', JText::_('CFG_GENERAL_SUB_UNINSTALL') );
	$params['delete_tables']				= array( 'list_yesno', 0 );
	$params['delete_tables_sure']			= array( 'list_yesno', 0 );
	$params[] = array( 'div_end', 0 );
	$params[] = array( '2div_end', 0 );

	@end( $params );
	$tab_data[] = array( JText::_('CFG_TAB_EXPERT_TITLE'), key( $params ), '<h2>' . JText::_('CFG_TAB_EXPERT_SUBTITLE') . '</h2>' );

	$error_reporting_notices[] = JHTML::_('select.option', 512, JText::_('AEC_NOTICE_NUMBER_512') );
	$error_reporting_notices[] = JHTML::_('select.option', 128, JText::_('AEC_NOTICE_NUMBER_128') );
	$error_reporting_notices[] = JHTML::_('select.option', 32, JText::_('AEC_NOTICE_NUMBER_32') );
	$error_reporting_notices[] = JHTML::_('select.option', 8, JText::_('AEC_NOTICE_NUMBER_8') );
	$error_reporting_notices[] = JHTML::_('select.option', 2, JText::_('AEC_NOTICE_NUMBER_2') );
	$lists['error_notification_level']			= JHTML::_('select.genericlist', $error_reporting_notices, 'error_notification_level', 'size="5"', 'value', 'text', $aecConfig->cfg['error_notification_level'] );
	$lists['email_notification_level']			= JHTML::_('select.genericlist', $error_reporting_notices, 'email_notification_level', 'size="5"', 'value', 'text', $aecConfig->cfg['email_notification_level'] );

	$pph					= new PaymentProcessorHandler();
	$gwlist					= $pph->getProcessorList();

	$gw_list_enabled		= array();
	$gw_list_enabled_html	= array();
	$gw_list_enabled_html[] = JHTML::_('select.option', 'none', JText::_('AEC_CMN_NONE_SELECTED') );

	// Display Processor descriptions?
	if ( !empty( $aecConfig->cfg['gwlist'] ) ) {
		$desc_list = $aecConfig->cfg['gwlist'];
	} else {
		$desc_list = array();
	}

	$gwlist_selected = array();

	asort($gwlist);

	$ppsettings = array();

	foreach ( $gwlist as $gwname ) {
		$pp = new PaymentProcessor();
		if ( $pp->loadName( $gwname ) ) {
			$pp->getInfo();

			if ( $pp->processor->active ) {
				// Add to Active List
				$gw_list_enabled[]->value = $gwname;

				// Add to selected Description List if existing in db entry
				if ( !empty( $desc_list ) ) {
					if ( in_array( $gwname, $desc_list ) ) {
						$gwlist_selected[]->value = $gwname;
					}
				}

				// Add to Description List
				$gw_list_enabled_html[] = JHTML::_('select.option', $gwname, $pp->info['longname'] );

			}
		}
	}

	$lists['gwlist']			= JHTML::_('select.genericlist', $gw_list_enabled_html, 'gwlist[]', 'size="' . max(min(count($gw_list_enabled), 12), 3) . '" multiple="multiple"', 'value', 'text', $gwlist_selected);

	$grouplist = ItemGroupHandler::getTree();

	$glist = array();

	foreach ( $grouplist as $id => $glisti ) {
		$glist[] = JHTML::_('select.option', $glisti[0], $glisti[1] );
	}

	$lists['root_group'] 		= JHTML::_('select.genericlist', $glist, 'root_group', 'size="' . min(6,count($glist)+1) . '"', 'value', 'text', $aecConfig->cfg['root_group'] );

	$editors = array();
	foreach ( $tab_data as $tab ) {
		foreach ( $tab as $st_content ) {
			if ( strcmp( $st_content[0], 'editor' ) === 0 ) {
				$editors[] = $st_content[4];
			}
		}
	}

	foreach ( $itemidlist as $idk => $idkp ) {
		if ( empty( $aecConfig->cfg['itemid_' . $idk] ) ) {
			$query = 'SELECT `id`'
					. ' FROM #__menu'
					. ' WHERE LOWER( `link` ) = \'index.php?option=com_acctexp&view=' . $idkp['view'] . '\''
					. ' OR LOWER( `link` ) LIKE \'%' . 'layout='. $idkp['view'] . '%\''
					;
			$db->setQuery( $query );

			$mid = 0;
			if ( empty( $idkp['params'] ) ) {
				$mid = $db->loadResult();
			} else {
				$mids = $db->loadResultArray();

				if ( !empty( $mids ) ) {
					$query = 'SELECT `id`'
							. ' FROM #__menu'
							. ' WHERE `id` IN (' . implode( ',', $mids ) . ')'
							. ' AND `params` LIKE \'%' . $idkp['params'] . '%\''
							;
					$db->setQuery( $query );

					$mid = $db->loadResult();
				}
			}

			if ( $mid ) {
				$aecConfig->cfg['itemid_' . $idk] = $mid;
			}
		}
	}

	$settings = new aecSettings ( 'cfg', 'general' );
	$settingsparams = array_merge( $aecConfig->cfg, $ppsettings );
	$settings->fullSettingsArray( $params, $settingsparams, $lists ) ;

	// Call HTML Class
	$aecHTML = new aecHTML( $settings->settings, $settings->lists );
	if ( !empty( $customparamsarray ) ) {
		$aecHTML->customparams = $customparamsarray;
	}

	HTML_AcctExp::Settings( $option, $aecHTML, $tab_data, $editors );
}

/**
* Cancels an configure operation
*/
function cancelSettings( $option )
{
	aecRedirect( 'index.php?option=' . $option . '&task=showCentral', JText::_('AEC_CONFIG_CANCELLED') );
}


function saveSettings( $option, $return=0 )
{
	$db		= &JFactory::getDBO();
	$user	= &JFactory::getUser();

	global $aecConfig;

	$app = JFactory::getApplication();

	unset( $_POST['id'] );
	unset( $_POST['task'] );
	unset( $_POST['option'] );

	$general_settings = array();
	foreach ( $_POST as $name => $value ) {
		$general_settings[$name] = $value;
	}

	$diff = $aecConfig->diffParams($general_settings, 'settings');
	$difference = '';

	if ( is_array( $diff ) ) {
		$newdiff = array();
		foreach ( $diff as $value => $change ) {
			$newdiff[] = $value . '(' . implode( ' -> ', $change ) . ')';
		}
		$difference = implode( ',', $newdiff );
	} else {
		$difference = 'none';
	}

	$aecConfig->cfg = $general_settings;
	$aecConfig->saveSettings();

	$ip = AECToolbox::aecIP();

	$short	= JText::_('AEC_LOG_SH_SETT_SAVED');
	$event	= JText::_('AEC_LOG_LO_SETT_SAVED') . ' ' . $difference;
	$tags	= 'settings,system';
	$params = array(	'userid' => $user->id,
						'ip' => $ip['ip'],
						'isp' => $ip['isp'] );

	$eventlog = new eventLog( $db );
	$eventlog->issue( $short, $tags, $event, 2, $params );

	if ( !empty( $aecConfig->cfg['entry_plan'] ) ) {
		$plan = new SubscriptionPlan( $db );
		$plan->load( $aecConfig->cfg['entry_plan'] );

		$terms = $plan->getTerms();

		if ( !$terms->checkFree() ) {
			$short	= "Settings Warning";
			$event	= "You have selected a non-free plan as Entry Plan."
						. " Please keep in mind that this means that users"
						. " will be getting it for free when they log in"
						. " without having any membership";
			$tags	= 'settings,system';
			$params = array(	'userid' => $user->id,
								'ip' => $ip['ip'],
								'isp' => $ip['isp'] );

			$eventlog = new eventLog( $db );
			$eventlog->issue( $short, $tags, $event, 32, $params );
		}
	}

	if ( $return ) {
		aecRedirect( 'index.php?option=' . $option . '&task=showSettings', JText::_('AEC_CONFIG_SAVED') );
	} else {
		aecRedirect( 'index.php?option=' . $option . '&task=showCentral', JText::_('AEC_CONFIG_SAVED') );
	}
}

function listProcessors( $option )
{
 	$db = &JFactory::getDBO();

	$app = JFactory::getApplication();

 	$limit = $app->getUserStateFromRequest( "viewlistlimit", 'limit', $app->getCfg( 'list_limit' ) );
	$limitstart = $app->getUserStateFromRequest( "viewconf{$option}limitstart", 'limitstart', 0 );

 	// get the total number of records
 	$query = 'SELECT count(*)'
		 	. ' FROM #__acctexp_config_processors'
		 	;
 	$db->setQuery( $query );
 	$total = $db->loadResult();

 	if ( $limit > $total ) {
 		$limitstart = 0;
 	}

	jimport('joomla.html.pagination');
	$pageNav = new JPagination( $total, $limitstart, $limit );

 	// get the subset (based on limits) of records
 	$query = 'SELECT name'
		 	. ' FROM #__acctexp_config_processors'
		 	. ' GROUP BY `id`'
		 	//. ' ORDER BY `ordering`'
		 	. ' LIMIT ' . $pageNav->limitstart . ',' . $pageNav->limit
		 	;
	$db->setQuery( $query );
	$names = $db->loadResultArray();

	$rows = array();
	foreach ( $names as $name ) {
		$pp = new PaymentProcessor( $db );
		$pp->loadName( $name );

		if ( $pp->fullInit() ) {
			$rows[] = $pp;
		}
	}

 	HTML_AcctExp::listProcessors( $rows, $pageNav, $option );
 }

function editProcessor( $id, $option )
{
	$db = &JFactory::getDBO();

	$user = &JFactory::getUser();

	$lang = JFactory::getLanguage();

	if ( $id ) {
		$pp = new PaymentProcessor();

		if ( !$pp->loadId( $id ) ) {
			return false;
		}

		// Init Info and Settings
		$pp->fullInit();

		// Get Backend Settings
		$settings_array		= $pp->getBackendSettings();
		$original_settings	= $pp->processor->settings();

		if ( isset( $settings_array['lists'] ) ) {
			foreach ( $settings_array['lists'] as $lname => $lvalue ) {
				$list_name = $pp->processor_name . '_' . $lname;

				$lists[$list_name] = str_replace( 'name="' . $lname . '"', 'name="' . $list_name . '"', $lvalue );
			}

			unset( $settings_array['lists'] );
		}

		$available_plans = SubscriptionPlanHandler::getActivePlanList();
		$total_plans = count( $available_plans );

		// Iterate through settings form assigning the db settings
		foreach ( $settings_array as $name => $values ) {
			$setting_name = $pp->processor_name . '_' . $name;

			switch( $settings_array[$name][0] ) {
				case 'list_currency':
					// Get currency list
					if ( is_array( $pp->info['currencies'] ) ) {
						$currency_array	= $pp->info['currencies'];
					} else {
						$currency_array	= explode( ',', $pp->info['currencies'] );
					}

					// Transform currencies into OptionArray
					$currency_code_list = array();
					foreach ( $currency_array as $currency ) {
						if ( $lang->hasKey( 'CURRENCY_' . $currency )) {
							$currency_code_list[] = JHTML::_('select.option', $currency, JText::_( 'CURRENCY_' . $currency ) );
						}
					}

					// Create list
					$lists[$setting_name] = JHTML::_('select.genericlist', $currency_code_list, $setting_name, 'size="10"', 'value', 'text', $pp->settings[$name] );
					$settings_array[$name][0] = 'list';
					break;
				case 'list_language':
					// Get language list
					if ( is_array( $pp->info['languages'] ) ) {
						$language_array	= $pp->info['languages'];
					} else {
						$language_array	= explode( ',', $pp->info['languages'] );
					}

					// Transform languages into OptionArray
					$language_code_list = array();
					foreach ( $language_array as $language ) {
						$language_code_list[] = JHTML::_('select.option', $language, JText::_( 'AEC_LANG_' . $language ) );
					}
					// Create list
					$lists[$setting_name] = JHTML::_('select.genericlist', $language_code_list, $setting_name, 'size="10"', 'value', 'text', $pp->settings[$name] );
					$settings_array[$name][0] = 'list';
					break;
				case 'list_plan':
					// Create list
					$lists[$setting_name] = JHTML::_('select.genericlist', $available_plans, $setting_name, 'size="10"', 'value', 'text', $pp->settings[$name] );
					$settings_array[$name][0] = 'list';
					break;
				default:
					break;
			}

			if ( !isset( $settings_array[$name][1] ) ) {
				$nname = 'CFG_' . strtoupper( $pp->processor_name ) . '_' . strtoupper($name) . '_NAME';
				$gname = 'CFG_PROCESSOR_' . strtoupper($name) . '_NAME';

				if ( $lang->hasKey( $nname ) ) {
					$settings_array[$name][1] = JText::_( $nname );
				} elseif ( $lang->hasKey( $gname ) ) {
					$settings_array[$name][1] = JText::_( $gname );
				} else {
					$settings_array[$name][1] = JText::_( $nname );
				}

				$nname = 'CFG_' . strtoupper( $pp->processor_name ) . '_' . strtoupper($name) . '_DESC';
				$gname = 'CFG_PROCESSOR_' . strtoupper($name) . '_DESC';

				if ( $lang->hasKey( $nname ) ) {
					$settings_array[$name][2] = JText::_( $nname );
				} elseif ( $lang->hasKey( $gname ) ) {
					$settings_array[$name][2] = JText::_( $gname );
				} else {
					$settings_array[$name][2] = JText::_( $nname );
				}
				
			}

			// It might be that the processor has got some new properties, so we need to double check here
			if ( isset( $pp->settings[$name] ) ) {
				$content = $pp->settings[$name];
			} elseif ( isset( $original_settings[$name] ) ) {
				$content = $original_settings[$name];
			} else {
				$content = null;
			}

			// Set the settings value
			$settings_array[$setting_name] = array_merge( (array) $settings_array[$name], array( $content ) );

			// unload the original value
			unset( $settings_array[$name] );
		}

		$longname = $pp->processor_name . '_info_longname';
		$description = $pp->processor_name . '_info_description';

		$settingsparams = $pp->settings;

		$params = array();
		$params[$pp->processor_name.'_active'] = array( 'list_yesno', JText::_('PP_GENERAL_ACTIVE_NAME'), JText::_('PP_GENERAL_ACTIVE_DESC'), $pp->processor->active);

		if ( is_array( $settings_array ) && !empty( $settings_array ) ) {
			$params = array_merge( $params, $settings_array );
		}

		$params[$longname] = array( 'inputC', JText::_('CFG_PROCESSOR_NAME_NAME'), JText::_('CFG_PROCESSOR_NAME_DESC'), $pp->info['longname'], $longname);
		$params[$description] = array( 'editor', JText::_('CFG_PROCESSOR_DESC_NAME'), JText::_('CFG_PROCESSOR_DESC_DESC'), $pp->info['description'], $description);
	} else {
		// Create Processor Selection Screen
		$pph					= new PaymentProcessorHandler();
		$pplist					= $pph->getProcessorList();
		$pp_installed_list		= $pph->getInstalledObjectList( false, true );

		$pp_list_html			= array();

		asort($pplist);

		foreach ( $pplist as $ppname ) {
			if ( in_array( $ppname, $pp_installed_list ) ) {
				continue;
			}

			$readppname = ucwords( str_replace( '_', ' ', strtolower( $ppname ) ) );

			// Load Payment Processor
			$pp = new PaymentProcessor();
			if ( $pp->loadName( $ppname ) ) {
				$pp->getInfo();

				// Add to general PP List
				$pp_list_html[] = JHTML::_('select.option', $ppname, $readppname );
			}
		}

		$lists['processor']	= JHTML::_('select.genericlist', $pp_list_html, 'processor', 'size="' . max(min(count($pplist), 24), 2) . '"', 'value', 'text' );

		$params['processor'] = array( 'list' );
		$settingsparams = array();

		$pp = null;
	}

	$settings = new aecSettings ( 'pp', 'general' );
	$settings->fullSettingsArray( $params, $settingsparams, $lists ) ;

	// Call HTML Class
	$aecHTML = new aecHTML( $settings->settings, $settings->lists );
	if ( !empty( $customparamsarray ) ) {
		$aecHTML->customparams = $customparamsarray;
	}

	$aecHTML->pp = $pp;

	HTML_AcctExp::editProcessor( $option, $aecHTML );
}

/**
* Cancels an configure operation
*/
function cancelProcessor( $option )
{
	aecRedirect( 'index.php?option=' . $option . '&task=showProcessors', JText::_('AEC_CONFIG_CANCELLED') );
}

function changeProcessor( $cid=null, $state=0, $type, $option )
{
	$db = &JFactory::getDBO();

	if ( count( $cid ) < 1 ) {
		echo "<script> alert('" . JText::_('AEC_ALERT_SELECT_FIRST') . "'); window.history.go(-1);</script>\n";
		exit;
	}

	$total	= count( $cid );
	$cids	= implode( ',', $cid );

	$query = 'UPDATE #__acctexp_config_processors'
			. ' SET `' . $type . '` = \'' . $state . '\''
			. ' WHERE `id` IN (' . $cids . ')'
			;
	$db->setQuery( $query );

	if ( !$db->query() ) {
		echo "<script> alert('".$db->getErrorMsg()."'); window.history.go(-1); </script>\n";
		exit();
	}

	if ( $state == '1' ) {
		$msg = ( ( strcmp( $type, 'active' ) === 0 ) ? JText::_('AEC_CMN_PUBLISHED') : JText::_('AEC_CMN_MADE_VISIBLE') );
	} elseif ( $state == '0' ) {
		$msg = ( ( strcmp( $type, 'active' ) === 0 ) ? JText::_('AEC_CMN_NOT_PUBLISHED') : JText::_('AEC_CMN_MADE_INVISIBLE') );
	}

	$msg = sprintf( JText::_('AEC_MSG_ITEMS_SUCESSFULLY'), $total ) . ' ' . $msg;

	aecRedirect( 'index.php?option=' . $option . '&task=showProcessors', $msg );
}

function saveProcessor( $option, $return=0 )
{
	$db = &JFactory::getDBO();

	$user = &JFactory::getUser();

	$pp = new PaymentProcessor();

	if ( !empty( $_POST['id'] ) ) {
		$pp->loadId( $_POST['id'] );

		if ( empty( $pp->id ) ) {
			cancel();
		}

		$procname = $pp->processor_name;
	} elseif ( isset( $_POST['processor'] ) ) {
		$pp->loadName( $_POST['processor'] );

		$procname = $_POST['processor'];
	}

	$pp->fullInit();

	$pp->storeload();

	$active			= $procname . '_active';
	$longname		= $procname . '_info_longname';
	$description	= $procname . '_info_description';

	if ( isset( $_POST[$longname] ) ) {
		$pp->info['longname'] = $_POST[$longname];
		unset( $_POST[$longname] );
	}

	if ( isset( $_POST[$description] ) ) {
		$pp->info['description'] = $_POST[$description];
		unset( $_POST[$description] );
	}

	if ( isset( $_POST[$active] ) ) {
		$pp->processor->active = $_POST[$active];
		unset( $_POST[$active] );
	}

	$settings = $pp->getBackendSettings();

	if ( is_int( $pp->is_recurring() ) ) {
		$settings['recurring'] = 2;
	}

	foreach ( $settings as $name => $value ) {
		if ( $name == 'lists' ) {
			continue;
		}

		$postname = $procname  . '_' . $name;

		if ( isset( $_POST[$postname] ) ) {
			$val = $_POST[$postname];

			if ( empty( $val ) ) {
				switch( $name ) {
					case 'currency':
						$val = 'USD';
						break;
					default:
						break;
				}
			}

			$pp->settings[$name] = $_POST[$postname];
			unset( $_POST[$postname] );
		}
	}

	$pp->storeload();

	if ( $return ) {
		aecRedirect( 'index.php?option=' . $option . '&task=editProcessor&id=' . $pp->id, JText::_('AEC_CONFIG_SAVED') );
	} else {
		aecRedirect( 'index.php?option=' . $option . '&task=showProcessors', JText::_('AEC_CONFIG_SAVED') );
	}
}

function listSubscriptionPlans( $option )
{
 	$db = &JFactory::getDBO();

	$app = JFactory::getApplication();

 	$limit			= $app->getUserStateFromRequest( "viewlistlimit", 'limit', $app->getCfg( 'list_limit' ) );
	$limitstart		= $app->getUserStateFromRequest( "viewconf{$option}limitstart", 'limitstart', 0 );
	$filter_group	= $app->getUserStateFromRequest( "filter_group", 'filter_group', array() );

	if ( !empty( $filter_group ) ) {
		$subselect = ItemGroupHandler::getChildren( $filter_group, 'item' );
	} else {
		$subselect = array();
	}

 	// get the total number of records
 	$query = 'SELECT count(*)'
		 	. ' FROM #__acctexp_plans'
		 	. ( empty( $subselect ) ? '' : ' WHERE id IN (' . implode( ',', $subselect ) . ')' )
		 	;
 	$db->setQuery( $query );
 	$total = $db->loadResult();

 	if ( $limit > $total ) {
 		$limitstart = 0;
 	}

	jimport('joomla.html.pagination');
	$pageNav = new JPagination( $total, $limitstart, $limit );

 	// get the subset (based on limits) of records
	$rows = SubscriptionPlanHandler::getFullPlanList( $pageNav->limitstart, $pageNav->limit, $subselect );

	$gcolors = array();

	foreach ( $rows as $n => $row ) {
		$query = 'SELECT count(*)'
				. 'FROM #__users AS a'
				. ' LEFT JOIN #__acctexp_subscr AS b ON a.id = b.userid'
				. ' WHERE b.plan = ' . $row->id
				. ' AND (b.status = \'Active\' OR b.status = \'Trial\')'
				;
		$db->setQuery( $query	);

	 	$rows[$n]->usercount = $db->loadResult();
	 	if ( $db->getErrorNum() ) {
	 		echo $db->stderr();
	 		return false;
	 	}

	 	$query = 'SELECT count(*)'
				. ' FROM #__users AS a'
				. ' LEFT JOIN #__acctexp_subscr AS b ON a.id = b.userid'
				. ' WHERE b.plan = ' . $row->id
				. ' AND (b.status = \'Expired\')'
				;
		$db->setQuery( $query	);

	 	$rows[$n]->expiredcount = $db->loadResult();
	 	if ( $db->getErrorNum() ) {
	 		echo $db->stderr();
	 		return false;
	 	}

	 	$query = 'SELECT group_id'
				. ' FROM #__acctexp_itemxgroup'
				. ' WHERE type = \'item\''
				. ' AND item_id = \'' . $rows[$n]->id . '\''
				;
		$db->setQuery( $query	);
		$g = (int) $db->loadResult();

		$group = empty( $g ) ? 0 : $g;

		if ( !isset( $gcolors[$group] ) ) {
			$gcolors[$group] = array();
			$gcolors[$group]['color'] = ItemGroupHandler::groupColor( $group );
			$gcolors[$group]['icon'] = ItemGroupHandler::groupIcon( $group ) . '.png';
		}

		$rows[$n]->group = aecHTML::Icon( $gcolors[$group]['icon'], $group ) . '<strong>' . $group . '</strong>';
		$rows[$n]->color = $gcolors[$group]['color'];
	}


	$grouplist = ItemGroupHandler::getTree();

	$glist		= array();
	$sel_groups	= array();

	$glist[] = JHTML::_('select.option', 0, '- - - - - -' );

	if ( empty( $filter_group ) ) {
		$sel_groups[] = JHTML::_('select.option', 0, '- - - - - -' );
	}

	foreach ( $grouplist as $id => $glisti ) {
		$glist[] = JHTML::_('select.option', $glisti[0], $glisti[1] );
		if ( !empty( $filter_group ) ) {
			if ( in_array( $glisti[0], $filter_group ) ) {
				$sel_groups[] = JHTML::_('select.option', $glisti[0], $glisti[1] );
			}
		}
	}

	$lists['filter_group'] = JHTML::_('select.genericlist', $glist, 'filter_group[]', 'size="' . min(8,count($glist)+1) . '" multiple="multiple"', 'value', 'text', $sel_groups );

 	HTML_AcctExp::listSubscriptionPlans( $rows, $lists, $pageNav, $option );
 }

function editSubscriptionPlan( $id, $option )
{
	global $aecConfig;

	$db = &JFactory::getDBO();

	$user = &JFactory::getUser();

	$lang = JFactory::getLanguage();

	$lists = array();
	$params_values = array();
	$restrictions_values = array();
	$customparams_values = array();

	$customparamsarray = new stdClass();

	$row = new SubscriptionPlan( $db );
	$row->load( $id );

	$restrictionHelper = new aecRestrictionHelper();

	if ( !$row->id ) {
		$row->ordering	= 9999;
		$hasrecusers	= false;

		$params_values['active']	= 1;
		$params_values['visible']	= 0;
		$params_values['processors'] = 0;

		$restrictions_values['gid_enabled']	= 1;
		if ( defined( 'JPATH_MANIFESTS' ) ) {
			$restrictions_values['gid']			= 2;
		} else {
			$restrictions_values['gid']			= 18;
		}
	} else {
		$params_values = $row->params;
		$restrictions_values = $row->restrictions;

		// Clean up custom params
		if ( !empty( $row->customparams ) ) {
			foreach ( $row->customparams as $n => $v ) {
				if ( isset( $params_values[$n] ) || isset( $restrictions_values[$n] ) ) {
					unset( $row->customparams[$n] );
				}
			}
		}

		$customparams_values = $row->custom_params;

		// We need to convert the values that are set as object properties
		$params_values['active']				= $row->active;
		$params_values['visible']				= $row->visible;
		$params_values['email_desc']			= $row->getProperty( 'email_desc' );
		$params_values['name']					= $row->getProperty( 'name' );
		$params_values['desc']					= $row->getProperty( 'desc' );
		$params_values['micro_integrations']	= $row->micro_integrations;
		$params_values['processors']			= $row->params['processors'];

		// Checking if there is already a user, which disables certain actions
		$query  = 'SELECT count(*)'
				. ' FROM #__users AS a'
				. ' LEFT JOIN #__acctexp_subscr AS b ON a.id = b.userid'
				. ' WHERE b.plan = ' . $row->id
				. ' AND (b.status = \'Active\' OR b.status = \'Trial\')'
				. ' AND b.recurring =\'1\''
				;
		$db->setQuery( $query );
		$hasrecusers = ( $db->loadResult() > 0 ) ? true : false;
	}

	$stdformat = '{aecjson}{"cmd":"condition","vars":[{"cmd":"data","vars":"payment.freetrial"},'
				.'{"cmd":"concat","vars":[{"cmd":"jtext","vars":"_CONFIRM_FREETRIAL"},"&nbsp;",{"cmd":"data","vars":"payment.method_name"}]},'
				.'{"cmd":"concat","vars":[{"cmd":"data","vars":"payment.amount"},{"cmd":"data","vars":"payment.currency_symbol"},"&nbsp;",{"cmd":"data","vars":"payment.method_name"}]}'
				.']}{/aecjson}'
				;

	// params and their type values
	$params['active']					= array( 'list_yesno', 1 );
	$params['visible']					= array( 'list_yesno', 1 );

	$params['name']						= array( 'inputC', '' );
	$params['desc']						= array( 'editor', '' );
	$params['customamountformat']		= array( 'inputD', $stdformat );
	$params['customthanks']				= array( 'inputC', '' );
	$params['customtext_thanks_keeporiginal']	= array( 'list_yesno', 1 );
	$params['customtext_thanks']		= array( 'editor', '' );
	$params['email_desc']				= array( 'inputD', '' );
	$params['micro_integrations_inherited']		= array( 'list', '' );
	$params['micro_integrations']		= array( 'list', '' );
	$params['micro_integrations_plan']	= array( 'list', '' );

	$params['params_remap']				= array( 'subarea_change', 'groups' );

	$groups = ItemGroupHandler::parentGroups( $row->id, 'item' );

	if ( !empty( $groups ) ) {
		$gs = array();
		foreach ( $groups as $groupid ) {
			$params['group_delete_'.$groupid] = array( 'checkbox', '', '', '' );

			$group = new ItemGroup( $db );
			$group->load( $groupid );

			$g = array();
			$g['name']	= $group->getProperty('name');
			$g['color']	= $group->params['color'];
			$g['icon']	= $group->params['icon'].'.png';

			$g['group']	= aecHTML::Icon( $g['icon'], $groupid ) . '<strong>' . $groupid . '</strong>';

			$gs[$groupid] = $g;
		}


		$customparamsarray->groups = $gs;
	} else {
		$customparamsarray->groups = null;
	}

	$grouplist = ItemGroupHandler::getTree();

	$glist = array();

	$glist[] = JHTML::_('select.option', 0, '- - - - - -' );
	$groupids = array();
	foreach ( $grouplist as $id => $glisti ) {
		$glist[] = JHTML::_('select.option', $glisti[0], $glisti[1] );

		$groupids[$glisti[0]] = ItemGroupHandler::groupColor( $glisti[0] );
	}

	$lists['add_group'] 			= JHTML::_('select.genericlist', $glist, 'add_group', 'size="1"', 'value', 'text', ( ( $row->id ) ? 0 : 1 ) );

	foreach ( $groupids as $groupid => $groupcolor ) {
		$lists['add_group'] = str_replace( 'value="'.$groupid.'"', 'value="'.$groupid.'"style="background-color:#'.$groupcolor.';"', $lists['add_group'] );
	}

	$params['add_group']			= array( 'list', '', '', ( ( $row->id ) ? 0 : 1 ) );

	$params['params_remap']			= array( 'subarea_change', 'params' );

	$params['override_activation']	= array( 'list_yesno', 0 );
	$params['override_regmail']		= array( 'list_yesno', 0 );

	$params['full_free']			= array( 'list_yesno', '' );
	$params['full_amount']			= array( 'inputB', '' );
	$params['full_period']			= array( 'inputB', '' );
	$params['full_periodunit']		= array( 'list', 'D' );
	$params['trial_free']			= array( 'list_yesno', '' );
	$params['trial_amount']			= array( 'inputB', '' );
	$params['trial_period']			= array( 'inputB', '' );
	$params['trial_periodunit']		= array( 'list', 'D' );

	$params['gid_enabled']			= array( 'list_yesno', 1 );
	$params['gid']					= array( 'list', ( defined( 'JPATH_MANIFESTS' ) ? 2 : 18 ) );
	$params['lifetime']				= array( 'list_yesno', 0 );
	$params['processors']			= array( 'list', '' );
	$params['standard_parent']		= array( 'list', '' );
	$params['fallback']				= array( 'list', '' );
	$params['fallback_req_parent']	= array( 'list_yesno', 0 );
	$params['make_active']			= array( 'list_yesno', 1 );
	$params['make_primary']			= array( 'list_yesno', 1 );
	$params['update_existing']		= array( 'list_yesno', 1 );

	$params['similarplans']			= array( 'list', '' );
	$params['equalplans']			= array( 'list', '' );

	$params['notauth_redirect']		= array( 'inputC', '' );
	$params['fixed_redirect']		= array( 'inputC', '' );
	$params['hide_duration_checkout']	= array( 'list_yesno', 0 );
	$params['addtocart_redirect']	= array( 'inputC', '' );
	$params['cart_behavior']		= array( 'list', 0 );

	$params['restr_remap']			= array( 'subarea_change', 'restrictions' );

	$params = array_merge( $params, $restrictionHelper->getParams() );

	$rewriteswitches				= array( 'cms', 'user' );
	$params['rewriteInfo']			= array( 'fieldset', '', AECToolbox::rewriteEngineInfo( $rewriteswitches ) );

	// make the select list for first trial period units
	$perunit[] = JHTML::_('select.option', 'D', JText::_('PAYPLAN_PERUNIT1') );
	$perunit[] = JHTML::_('select.option', 'W', JText::_('PAYPLAN_PERUNIT2') );
	$perunit[] = JHTML::_('select.option', 'M', JText::_('PAYPLAN_PERUNIT3') );
	$perunit[] = JHTML::_('select.option', 'Y', JText::_('PAYPLAN_PERUNIT4') );

	$lists['trial_periodunit'] = JHTML::_('select.genericlist', $perunit, 'trial_periodunit', 'size="4"', 'value', 'text', arrayValueDefault($params_values, 'trial_periodunit', "D") );
	$lists['full_periodunit'] = JHTML::_('select.genericlist', $perunit, 'full_periodunit', 'size="4"', 'value', 'text', arrayValueDefault($params_values, 'full_periodunit', "D") );

	$params['processors_remap'] = array("subarea_change", "plan_params");

	$pps = PaymentProcessorHandler::getInstalledObjectList( 1 );

	if ( empty( $params_values['processors'] ) ) {
		$plan_procs = array();
	} else {
		$plan_procs = $params_values['processors'];
	}

	$firstarray = array();
	$secndarray = array();
	foreach ( $pps as $ppo ) {
		if ( in_array( $ppo->id, $plan_procs ) && !empty( $customparams_values[$ppo->id . '_aec_overwrite_settings'] ) ) {
			$firstarray[] = $ppo;
		} else {
			$secndarray[] = $ppo;
		}
	}

	$pps = array_merge( $firstarray, $secndarray );

	$selected_gw = array();
	$custompar = array();
	foreach ( $pps as $ppobj ) {
		if ( !$ppobj->active ) {
			continue;
		}

		$pp = null;
		$pp = new PaymentProcessor();

		if ( !$pp->loadName( $ppobj->name ) ) {
			continue;
		}

		$pp->init();
		$pp->getInfo();

		$custompar[$pp->id] = array();
		$custompar[$pp->id]['handle'] = $ppobj->name;
		$custompar[$pp->id]['name'] = $pp->info['longname'];
		$custompar[$pp->id]['params'] = array();

		$params['processor_' . $pp->id] = array( 'checkbox', JText::_('PAYPLAN_PROCESSORS_ACTIVATE_NAME'), JText::_('PAYPLAN_PROCESSORS_ACTIVATE_DESC')  );
		$custompar[$pp->id]['params'][] = 'processor_' . $pp->id;

		$params[$pp->id . '_aec_overwrite_settings'] = array( 'checkbox', JText::_('PAYPLAN_PROCESSORS_OVERWRITE_SETTINGS_NAME'), JText::_('PAYPLAN_PROCESSORS_OVERWRITE_SETTINGS_DESC') );
		$custompar[$pp->id]['params'][] = $pp->id . '_aec_overwrite_settings';

		$customparams = $pp->getCustomPlanParams();

		if ( is_array( $customparams ) ) {
			foreach ( $customparams as $customparam => $cpcontent ) {
				// Write the params field
				if ( $lang->hasKey( strtoupper( "CFG_processor_plan_params_" . $customparam . "_name" ) ) ) {
					$cp_name = JText::_( strtoupper( "CFG_processor_plan_params_" . $customparam . "_name" ) );
					$cp_desc = JText::_( strtoupper( "CFG_processor_plan_params_" . $customparam . "_desc" ) );
				} else {
					$cp_name = JText::_( strtoupper( "CFG_" . $pp->processor_name . "_plan_params_" . $customparam . "_name" ) );
					$cp_desc = JText::_( strtoupper( "CFG_" . $pp->processor_name . "_plan_params_" . $customparam . "_desc" ) );
				}

				$shortname = $pp->id . "_" . $customparam;
				$params[$shortname] = array_merge( $cpcontent, array( $cp_name, $cp_desc ) );
				$custompar[$pp->id]['params'][] = $shortname;
			}
		}

		if ( empty( $plan_procs ) ) {
			continue;
		}

		if ( !in_array( $pp->id, $plan_procs ) ) {
			continue;
		}

		$params_values['processor_' . $pp->id] = 1;

		if ( isset( $customparams_values[$pp->id . '_aec_overwrite_settings'] ) ) {
			if ( !$customparams_values[$pp->id . '_aec_overwrite_settings'] ) {
				continue;
			}
		} else {
			continue;
		}

		$settings_array = $pp->getBackendSettings();

		if ( isset( $settings_array['lists'] ) ) {
			foreach ( $settings_array['lists'] as $listname => $listcontent ) {
				$lists[$pp->id . '_' . $listname] = $listcontent;
			}

			unset( $settings_array['lists'] );
		}

		// Iterate through settings form to...
		foreach ( $settings_array as $name => $values ) {
			$setting_name = $pp->id . '_' . $name;

			if ( isset( $customparams_values[$setting_name] ) ) {
				$value = $customparams_values[$setting_name];
			} elseif ( isset( $pp->settings[$name] ) ) {
				$value = $pp->settings[$name];
			} else {
				$value = '';
			}

			// ...assign new list fields
			switch( $settings_array[$name][0] ) {
				case 'list_yesno':
					$arr = array(
						JHTML::_('select.option', 0, JText::_( 'no' ) ),
						JHTML::_('select.option', 1, JText::_( 'yes' ) ),
					);

					$lists[$setting_name] = JHTML::_('select.genericlist', $arr, $setting_name, '', 'value', 'text', (int) $value );

					$settings_array[$name][0] = 'list';
					break;

				case 'list_currency':
					// Get currency list
					$currency_array	= explode( ',', $pp->info['currencies'] );

					// Transform currencies into OptionArray
					$currency_code_list = array();
					foreach ( $currency_array as $currency ) {
						if ( $lang->hasKey( 'CURRENCY_' . $currency )) {
							$currency_code_list[] = JHTML::_('select.option', $currency, JText::_( 'CURRENCY_' . $currency ) );
						}
					}

					// Create list
					$lists[$setting_name] = JHTML::_('select.genericlist', $currency_code_list, $setting_name, 'size="10"', 'value', 'text', $value );
					$settings_array[$name][0] = 'list';
					break;

				case 'list_language':
					// Get language list
					if ( !is_array( $pp->info['languages'] ) ) {
						$language_array	= explode( ',', $pp->info['languages'] );
					} else {
						$language_array	= $pp->info['languages'];
					}

					// Transform languages into OptionArray
					$language_code_list = array();
					foreach ( $language_array as $language ) {
						$language_code_list[] = JHTML::_('select.option', $language, ( $lang->hasKey( 'AEC_LANG_' . $language  ) ? JText::_( 'AEC_LANG_' . $language ) : $language ) );
					}
					// Create list
					$lists[$setting_name] = JHTML::_('select.genericlist', $language_code_list, $setting_name, 'size="10"', 'value', 'text', $value );
					$settings_array[$name][0] = 'list';
					break;

				case 'list_plan':
					unset( $settings_array[$name] );
					break;

				default:
					break;
			}

			// ...put in missing language fields
			if ( !isset( $settings_array[$name][1] ) ) {
				$settings_array[$name][1] = JText::_( 'CFG_' . strtoupper( $ppobj->name ) . '_' . strtoupper($name) . '_NAME' );
				$settings_array[$name][2] = JText::_( 'CFG_' . strtoupper( $ppobj->name ) . '_' . strtoupper($name) . '_DESC' );
			}

			$params[$pp->id . '_' . $name] = $settings_array[$name];
			$custompar[$pp->id]['params'][] = $pp->id . '_' . $name;
		}
	}

	$customparamsarray->pp = $custompar;

	// get available active plans
	$available_plans = array();
	$available_plans[] = JHTML::_('select.option', '0', JText::_('PAYPLAN_NOPLAN') );

	$query = 'SELECT `id` AS value, `name` AS text'
			. ' FROM #__acctexp_plans'
			. ' WHERE `active` = 1'
			. ' AND `id` != \'' . $row->id . '\'';
			;
	$db->setQuery( $query );
	$payment_plans = $db->loadObjectList();

 	if ( is_array( $payment_plans ) ) {
 		$active_plans	= array_merge( $available_plans, $payment_plans );
 	}
	$total_plans	= min( max( (count( $active_plans ) + 1 ), 4 ), 20 );

	$lists['fallback'] = JHTML::_('select.genericlist', $active_plans, 'fallback', 'size="' . $total_plans . '"', 'value', 'text', arrayValueDefault($params_values, 'fallback', 0));
	$lists['standard_parent'] = JHTML::_('select.genericlist', $active_plans, 'standard_parent', 'size="' . $total_plans . '"', 'value', 'text', arrayValueDefault($params_values, 'standard_parent', 0));

	// get similar plans
	if ( !empty( $params_values['similarplans'] ) ) {
		$query = 'SELECT `id` AS value, `name` As text'
				. ' FROM #__acctexp_plans'
				. ' WHERE `id` IN (' . implode( ',', $params_values['similarplans'] ) .')'
				;
		$db->setQuery( $query );

	 	$sel_similar_plans = $db->loadObjectList();
	} else {
		$sel_similar_plans = 0;
	}

	$lists['similarplans'] = JHTML::_('select.genericlist', $payment_plans, 'similarplans[]', 'size="' . $total_plans . '" multiple="multiple"', 'value', 'text', $sel_similar_plans);

	// get equal plans
	if ( !empty( $params_values['equalplans'] ) ) {
		$query = 'SELECT `id` AS value, `name` AS text'
				. ' FROM #__acctexp_plans'
				. ' WHERE `id` IN (' . implode( ',', $params_values['equalplans'] ) .')'
				;
		$db->setQuery( $query );

	 	$sel_equal_plans = $db->loadObjectList();
	} else {
		$sel_equal_plans = 0;
	}

	$lists['equalplans'] = JHTML::_('select.genericlist', $payment_plans, 'equalplans[]', 'size="' . $total_plans . '" multiple="multiple"', 'value', 'text', $sel_equal_plans);

	$lists = array_merge( $lists, $restrictionHelper->getLists( $params_values, $restrictions_values ) );

	// get available micro integrations
	$query = 'SELECT `id` AS value, CONCAT(`name`, " - ", `desc`) AS text'
			. ' FROM #__acctexp_microintegrations'
			. ' WHERE `active` = 1'
		 	. ' AND `hidden` = \'0\''
			. ' ORDER BY ordering'
			;
	$db->setQuery( $query );
	$mi_list = $db->loadObjectList();

	if ( !empty( $row->micro_integrations ) ) {
		$query = 'SELECT `id` AS value, CONCAT(`name`, " - ", `desc`) AS text'
				. ' FROM #__acctexp_microintegrations'
				. ' WHERE `id` IN (' . implode( ',', $row->micro_integrations ) . ')'
		 		. ' AND `hidden` = \'0\''
				;
	 	$db->setQuery( $query );
		$selected_mi = $db->loadObjectList();
	} else {
		$selected_mi = array();
	}

	$lists['micro_integrations'] = JHTML::_('select.genericlist', $mi_list, 'micro_integrations[]', 'size="' . min((count( $mi_list ) + 1), 25) . '" multiple="multiple"', 'value', 'text', $selected_mi);

	$inherited = $row->getMicroIntegrationsSeparate();

	$inherited_list = array();

	if ( !empty( $inherited ) ) {
		foreach ( $mi_list as $miobj ) {
			if ( in_array( $miobj->value, $inherited['inherited'] ) ) {
				$inherited_list[] = $miobj;
			}
		}
	}

	$lists['micro_integrations_inherited'] = JHTML::_('select.genericlist', $inherited_list, 'micro_integrations_inherited[]', 'size="' . min((count( $inherited_list ) + 1), 25) . '" disabled="disabled"', 'value', 'text', array());

	$mi_handler = new microIntegrationHandler();
	$mi_list = $mi_handler->getIntegrationList();

	$mi_htmllist = array();
	$mi_htmllist[]	= JHTML::_('select.option', '', JText::_('AEC_CMN_NONE_SELECTED') );

	foreach ( $mi_list as $name ) {
		$mi = new microIntegration( $db );
		$mi->class_name = $name;
		if ( $mi->callIntegration() ){
			$len = 30 - AECToolbox::visualstrlen( trim( $mi->name ) );
			$fullname = str_replace( '#', '&nbsp;', str_pad( $mi->name, $len, '#' ) ) . ' - ' . substr($mi->desc, 0, 120);
			$mi_htmllist[] = JHTML::_('select.option', $name, $fullname );
		}
	}

	if ( !empty( $row->micro_integrations ) && is_array( $row->micro_integrations ) ) {
		$query = 'SELECT `id`'
				. ' FROM #__acctexp_microintegrations'
				. ' WHERE `id` IN (' . implode( ',', $row->micro_integrations ) . ')'
		 		. ' AND `hidden` = \'1\''
				;
	 	$db->setQuery( $query );
		$hidden_mi = $db->loadObjectList();
	} else {
		$hidden_mi = array();
	}

	// make the select list for first trial period units
	$cartmode[] = JHTML::_('select.option', '0', JText::_('PAYPLAN_CARTMODE_INHERIT') );
	$cartmode[] = JHTML::_('select.option', '1', JText::_('PAYPLAN_CARTMODE_FORCE_CART') );
	$cartmode[] = JHTML::_('select.option', '2', JText::_('PAYPLAN_CARTMODE_FORCE_DIRECT') );

	$lists['cart_behavior'] = JHTML::_('select.genericlist', $cartmode, 'cart_behavior', 'size="1"', 'value', 'text', arrayValueDefault($params_values, 'cart_behavior', "0") );

	$customparamsarray->hasperplanmi = false;

	if ( !empty( $aecConfig->cfg['per_plan_mis'] ) || !empty( $hidden_mi ) ) {
		$customparamsarray->hasperplanmi = true;

		$lists['micro_integrations_plan'] = JHTML::_('select.genericlist', $mi_htmllist, 'micro_integrations_plan[]', 'size="' . min( ( count( $mi_list ) + 1 ), 25 ) . '" multiple="multiple"', 'value', 'text', array() );

		$custompar = array();

		$hidden_mi_list = array();
		if ( !empty( $hidden_mi ) ) {
			foreach ( $hidden_mi as $miobj ) {
				$hidden_mi_list[] = $miobj->id;
			}
		}

		$params['micro_integrations_hidden']		= array( 'hidden', '' );
		$params_values['micro_integrations_hidden']		= $hidden_mi_list;

		if ( !empty( $hidden_mi ) ) {
			foreach ( $hidden_mi as $miobj ) {
				$mi = new microIntegration( $db );

				if ( !$mi->load( $miobj->id ) ) {
					continue;
				}

				if ( !$mi->callIntegration( 1 ) ) {
					continue;
				}

				$custompar[$mi->id] = array();
				$custompar[$mi->id]['name'] = $mi->name;
				$custompar[$mi->id]['params'] = array();

				$prefix = 'MI_' . $mi->id . '_';

				$params[] = array( 'area_change', 'MI' );
				$params[] = array( 'subarea_change', 'E' );
				$params[] = array( 'add_prefix', $prefix );
				$params[] = array( 'userinfobox_sub', JText::_('MI_E_TITLE') );

				$generalsettings = $mi->getGeneralSettings();

				foreach ( $generalsettings as $name => $value ) {
					$params[$prefix . $name] = $value;
					$custompar[$mi->id]['params'][] = $prefix . $name;

					if ( isset( $mi->$name ) ) {
						$params_values[$prefix.$name] = $mi->$name;
					} else {
						$params_values[$prefix.$name] = '';
					}
				}

				$params[]	= array( 'div_end', 0 );

				$misettings = $mi->getSettings();

				if ( isset( $misettings['lists'] ) ) {
					foreach ( $misettings['lists'] as $listname => $listcontent ) {
						$lists[$prefix . $listname] = str_replace( 'name="', 'name="'.$prefix, $listcontent );
					}

					unset( $misettings['lists'] );
				}

				$params[] = array( 'area_change', 'MI' );
				$params[] = array( 'subarea_change', $mi->class_name );
				$params[] = array( 'add_prefix', $prefix );
				$params[] = array( 'userinfobox_sub', JText::_('MI_E_SETTINGS') );

				foreach ( $misettings as $name => $value ) {
					$params[$prefix . $name] = $value;
					$custompar[$mi->id]['params'][] = $prefix . $name;
				}

				$params[]	= array( 'div_end', 0 );
			}
		}

		if ( !empty( $custompar ) ) {
			$customparamsarray->mi = $custompar;
		}
	}

	$settings = new aecSettings ( 'payplan', 'general' );

	if ( is_array( $customparams_values ) ) {
		$settingsparams = array_merge( $params_values, $customparams_values, $restrictions_values );
	} else {
		$settingsparams = array_merge( $params_values, $restrictions_values );
	}

	$settings->fullSettingsArray( $params, $settingsparams, $lists ) ;

	// Call HTML Class
	$aecHTML = new aecHTML( $settings->settings, $settings->lists );

	if ( !empty( $customparamsarray ) ) {
		$aecHTML->customparams = $customparamsarray;
	}

	HTML_AcctExp::editSubscriptionPlan( $option, $aecHTML, $row, $hasrecusers );
}

function saveSubscriptionPlan( $option, $apply=0 )
{
	$db = &JFactory::getDBO();

	$row = new SubscriptionPlan( $db );
	$row->load( $_POST['id'] );

	$post = AECToolbox::cleanPOST( $_POST, false );

	$row->savePOSTsettings( $post );

	$row->storeload();

	if ( $_POST['id'] ) {
		$id = $_POST['id'];
	} else {
		$id = $row->getMax();
	}

	if ( !empty( $row->params['lifetime'] ) && !empty( $row->params['full_period'] ) ) {
		$short	= "Plan Warning";
		$event	= "You have selected a regular period for a plan that"
					. " already has the 'lifetime' (i.e. 'non expiring') flag set."
					. " The period you have set will be overridden by"
					. " that setting.";
		$tags	= 'settings,plan';
		$params = array();

		$eventlog = new eventLog( $db );
		$eventlog->issue( $short, $tags, $event, 32, $params );
	}

	$terms = $row->getTerms();

	if ( !$terms->checkFree() && empty( $row->params['processors'] ) ) {
		$short	= "Plan Warning";
		$event	= "You have set a plan to be non-free, yet did not select a payment processor."
					. " Without a processor assigned, the plan will not show up on the frontend.";
		$tags	= 'settings,plan';
		$params = array();

		$eventlog = new eventLog( $db );
		$eventlog->issue( $short, $tags, $event, 32, $params );
	}

	if ( !empty( $row->params['lifetime'] ) && !empty( $row->params['processors'] ) ) {
		$fcount	= 0;
		$found	= 0;

		foreach ( $row->params['processors'] as $procid ) {
			$fcount++;

			if ( isset( $row->custom_params[$procid.'_recurring'] ) ) {
				if ( ( 0 < $row->custom_params[$procid.'_recurring'] ) && ( $row->custom_params[$procid.'_recurring'] < 2 ) ) {
					$found++;
				} elseif ( $row->custom_params[$procid.'_recurring'] == 2 ) {
					$fcount++;
				}
			} else {
				$pp = new PaymentProcessor( $db );
				if ( ( 0 < $pp->is_recurring() ) && ( $pp->is_recurring() < 2 ) ) {
					$found++;
				} elseif ( $pp->is_recurring() == 2 ) {
					$fcount++;
				}
			}
		}

		if ( $found ) {
			if ( ( $found < $fcount ) && ( $fcount > 1 ) ) {
				$event	= "You have selected one or more processors that only support recurring payments"
						. ", yet the plan is set to a lifetime period."
						. " This is not possible and the processors will not be displayed as options.";
			} else {
				$event	= "You have selected a processor that only supports recurring payments"
						. ", yet the plan is set to a lifetime period."
						. " This is not possible and the plan will not be displayed.";
			}

			$short	= "Plan Warning";
			$tags	= 'settings,plan';
			$params = array();

			$eventlog = new eventLog( $db );
			$eventlog->issue( $short, $tags, $event, 32, $params );
		}
	}

	if ( $apply ) {
		aecRedirect( 'index.php?option=' . $option . '&task=editSubscriptionPlan&id=' . $id, JText::_('AEC_MSG_SUCESSFULLY_SAVED') );
	} else {
		aecRedirect( 'index.php?option=' . $option . '&task=showSubscriptionPlans' );
	}
}

function removeSubscriptionPlan( $id, $option )
{
	$db = &JFactory::getDBO();

	$ids = implode( ',', $id );

	$query = 'SELECT count(*)'
			. ' FROM #__acctexp_plans'
			. ' WHERE `id` IN (' . $ids . ')'
			;
	$db->setQuery( $query );
	$total = $db->loadResult();

	if ( $total == 0 ) {
		echo "<script> alert('" . html_entity_decode( JText::_('AEC_MSG_NO_ITEMS_TO_DELETE') ) . "'); window.history.go(-1);</script>\n";
		exit;
	}

	// See if we have registered users on this plan.
	// If we have it, the plan(s) cannot be removed
	$query = 'SELECT count(*)'
			. ' FROM #__users AS a'
			. ' LEFT JOIN #__acctexp_subscr AS b ON a.id = b.userid'
			. ' WHERE b.plan = ' . $row->id
			. ' AND (b.status = \'Active\' OR b.status = \'Trial\')'
			;
	$db->setQuery( $query );
	$subscribers = $db->loadResult();

	if ( $subscribers > 0 ) {
		$msg = JText::_('AEC_MSG_NO_DEL_W_ACTIVE_SUBSCRIBER');
	} else {
		// Delete plans
		$query = 'DELETE FROM #__acctexp_plans'
				. ' WHERE `id` IN (' . $ids . ')'
				;
		$db->setQuery( $query );
		if ( !$db->query() ) {
			echo "<script> alert('".$db->getErrorMsg()."'); window.history.go(-1); </script>\n";
			exit();
		}

		ItemGroupHandler::removeChildren( $id, false, 'item' );

		$msg = $total . ' ' . JText::_('AEC_MSG_ITEMS_DELETED');
	}
	aecRedirect( 'index.php?option=' . $option . '&task=showSubscriptionPlans', $msg );
}

function cancelSubscriptionPlan( $option )
{
	aecRedirect( 'index.php?option=' . $option . '&task=showSubscriptionPlans', JText::_('AEC_CMN_EDIT_CANCELLED') );
}

function changeSubscriptionPlan( $cid=null, $state=0, $type, $option )
{
	$db = &JFactory::getDBO();

	if ( count( $cid ) < 1 ) {
		echo "<script> alert('" . JText::_('AEC_ALERT_SELECT_FIRST') . "'); window.history.go(-1);</script>\n";
		exit;
	}

	$total	= count( $cid );
	$cids	= implode( ',', $cid );

	$query = 'UPDATE #__acctexp_plans'
			. ' SET `' . $type . '` = \'' . $state . '\''
			. ' WHERE `id` IN (' . $cids . ')'
			;
	$db->setQuery( $query );

	if ( !$db->query() ) {
		echo "<script> alert('".$db->getErrorMsg()."'); window.history.go(-1); </script>\n";
		exit();
	}

	if ( $state == '1' ) {
		$msg = ( ( strcmp( $type, 'active' ) === 0 ) ? JText::_('AEC_CMN_PUBLISHED') : JText::_('AEC_CMN_MADE_VISIBLE') );
	} elseif ( $state == '0' ) {
		$msg = ( ( strcmp( $type, 'active' ) === 0 ) ? JText::_('AEC_CMN_NOT_PUBLISHED') : JText::_('AEC_CMN_MADE_INVISIBLE') );
	}

	$msg = sprintf( JText::_('AEC_MSG_ITEMS_SUCESSFULLY'), $total ) . ' ' . $msg;

	aecRedirect( 'index.php?option=' . $option . '&task=showSubscriptionPlans', $msg );
}

function listItemGroups( $option )
{
 	$db = &JFactory::getDBO();

	$app = JFactory::getApplication();

 	$limit		= $app->getUserStateFromRequest( "viewlistlimit", 'limit', $app->getCfg( 'list_limit' ) );
	$limitstart = $app->getUserStateFromRequest( "viewconf{$option}limitstart", 'limitstart', 0 );

 	// get the total number of records
 	$query = 'SELECT count(*)'
		 	. ' FROM #__acctexp_itemgroups'
		 	;
 	$db->setQuery( $query );
 	$total = $db->loadResult();
 	echo $db->getErrorMsg();

 	if ( $limit > $total ) {
 		$limitstart = 0;
 	}

	jimport('joomla.html.pagination');
	$pageNav = new JPagination( $total, $limitstart, $limit );

 	// get the subset (based on limits) of records
 	$query = 'SELECT *'
		 	. ' FROM #__acctexp_itemgroups'
		 	. ' GROUP BY `id`'
		 	. ' ORDER BY `ordering`'
		 	. ' LIMIT ' . $pageNav->limitstart . ',' . $pageNav->limit
		 	;
	$db->setQuery( $query );

 	$rows = $db->loadObjectList();
 	if ( $db->getErrorNum() ) {
 		echo $db->stderr();
 		return false;
 	}

	$gcolors = array();

	foreach ( $rows as $n => $row ) {
		$query = 'SELECT count(*)'
				. 'FROM #__users AS a'
				. ' LEFT JOIN #__acctexp_subscr AS b ON a.id = b.userid'
				. ' WHERE b.plan = ' . $row->id
				. ' AND (b.status = \'Active\' OR b.status = \'Trial\')'
				;
		$db->setQuery( $query	);

	 	$rows[$n]->usercount = $db->loadResult();
	 	if ( $db->getErrorNum() ) {
	 		echo $db->stderr();
	 		return false;
	 	}

	 	$query = 'SELECT count(*)'
				. ' FROM #__users AS a'
				. ' LEFT JOIN #__acctexp_subscr AS b ON a.id = b.userid'
				. ' WHERE b.plan = ' . $row->id
				. ' AND (b.status = \'Expired\')'
				;
		$db->setQuery( $query	);

	 	$rows[$n]->expiredcount = $db->loadResult();
	 	if ( $db->getErrorNum() ) {
	 		echo $db->stderr();
	 		return false;
	 	}

		$group = $rows[$n]->id;

		if ( !isset( $gcolors[$group] ) ) {
			$gcolors[$group] = array();
			$gcolors[$group]['color'] = ItemGroupHandler::groupColor( $group );
			$gcolors[$group]['icon'] = ItemGroupHandler::groupIcon( $group ) . '.png';
		}

		$rows[$n]->group = aecHTML::Icon( $gcolors[$group]['icon'], $group );
		$rows[$n]->color = $gcolors[$group]['color'];
	}

 	HTML_AcctExp::listItemGroups( $rows, $pageNav, $option );
 }

function editItemGroup( $id, $option )
{
	$db = &JFactory::getDBO();

	$lists = array();
	$params_values = array();
	$restrictions_values = array();
	$customparams_values = array();

	$row = new ItemGroup( $db );
	$row->load( $id );

	$restrictionHelper = new aecRestrictionHelper();

	if ( !$row->id ) {
		$row->ordering	= 9999;

		$params_values['active']	= 1;
		$params_values['visible']	= 0;

		$restrictions_values['gid_enabled']	= 1;
		$restrictions_values['gid']			= 18;
	} else {
		$params_values = $row->params;
		$restrictions_values = $row->restrictions;
		$customparams_values = $row->custom_params;

		// We need to convert the values that are set as object properties
		$params_values['active']				= $row->active;
		$params_values['visible']				= $row->visible;
		$params_values['name']					= $row->getProperty( 'name' );
		$params_values['desc']					= $row->getProperty( 'desc' );
	}

	// params and their type values
	$params['active']					= array( 'list_yesno', 1 );
	$params['visible']					= array( 'list_yesno', 0 );

	$params['name']						= array( 'inputC', '' );
	$params['desc']						= array( 'editor', '' );

	$params['color']					= array( 'list', '' );
	$params['icon']						= array( 'list', '' );

	$params['reveal_child_items']		= array( 'list_yesno', 0 );
	$params['symlink']					= array( 'inputC', '' );
	$params['symlink_userid']			= array( 'list_yesno', 0 );

	$params['notauth_redirect']			= array( 'inputD', '' );

	$params['micro_integrations']		= array( 'list', '' );

	$params['params_remap']				= array( 'subarea_change', 'groups' );

	$groups = ItemGroupHandler::parentGroups( $row->id, 'group' );

	if ( !empty( $groups ) ) {
		$gs = array();
		foreach ( $groups as $groupid ) {
			$params['group_delete_'.$groupid] = array( 'checkbox', '', '', '' );

			$group = new ItemGroup( $db );
			$group->load( $groupid );

			$g = array();
			$g['name']	= $group->getProperty('name');
			$g['color']	= $group->params['color'];
			$g['icon']	= $group->params['icon'].'.png';

			$g['group']	= aecHTML::Icon( $g['icon'], $groupid ) . '<strong>' . $groupid . '</strong>';

			$gs[$groupid] = $g;
		}


		$customparamsarray->groups = $gs;
	} else {
		$customparamsarray->groups = null;
	}

	$grouplist = ItemGroupHandler::getTree();

	$glist = array();

	$glist[] = JHTML::_('select.option', 0, '- - - - - -' );
	$groupids = array();
	foreach ( $grouplist as $id => $glisti ) {
		$glist[] = JHTML::_('select.option', $glisti[0], $glisti[1] );

		$groupids[$glisti[0]] = ItemGroupHandler::groupColor( $glisti[0] );
	}

	$lists['add_group'] 			= JHTML::_('select.genericlist', $glist, 'add_group', 'size="1"', 'value', 'text', ( ( $row->id ) ? 0 : 1 ) );

	foreach ( $groupids as $groupid => $groupcolor ) {
		$lists['add_group'] = str_replace( 'value="'.$groupid.'"', 'value="'.$groupid.'"style="background-color:#'.$groupcolor.';"', $lists['add_group'] );
	}

	$params['add_group']	= array( 'list', '', '', ( ( $row->id ) ? 0 : 1 ) );

	$params['restr_remap']	= array( 'subarea_change', 'restrictions' );

	$params = array_merge( $params, $restrictionHelper->getParams() );

	$rewriteswitches		= array( 'cms', 'user' );
	$params['rewriteInfo']	= array( 'fieldset', '', AECToolbox::rewriteEngineInfo( $rewriteswitches ) );


	// light blue, another blue, brown, green, another green, reddish gray, yellowish, purpleish, red
	$colors = array( 'BBDDFF', '5F8BC4', 'A2BE72', 'DDFF99', 'D07C30', 'C43C42', 'AA89BB', 'B7B7B7', '808080' );

	$colorlist = array();
	foreach ( $colors as $color ) {
		$obj = new stdClass;
		$obj->value = $color;
		$obj->text = '- - ' . $color . ' - -';
		$obj->id = 'aec_colorlist_'.$color;

		$colorlist[] = $obj;
	}

	$lists['color'] = JHTML::_('select.genericlist', $colorlist, 'color', 'size="1"', 'value', 'text', arrayValueDefault($params_values, 'color', 'BBDDFF'));

	foreach ( $colors as $color ) {
		$lists['color'] = str_replace( 'value="'.$color.'"', 'value="'.$color.'"style="background-color:#'.$color.';"', $lists['color'] );
	}

	$icons = array( 'blue', 'green', 'orange', 'pink', 'purple', 'red', 'yellow' );

	$iconlist = array();
	foreach ( $icons as $iconname ) {
		$obj = new stdClass;
		$obj->value = 'flag_'.$iconname;
		$obj->text = $iconname.' '.'flag';
		$obj->id = 'aec_iconlist_flag_'.$iconname;

		$iconlist[] = $obj;
	}

	$lists['icon'] = JHTML::_('select.genericlist', $iconlist, 'icon', 'size="1"', 'value', 'text', arrayValueDefault($params_values, 'icon', 'blue'));

	// get available micro integrations
	$query = 'SELECT `id` AS value, CONCAT(`name`, " - ", `desc`) AS text'
			. ' FROM #__acctexp_microintegrations'
			. ' WHERE `active` = 1'
		 	. ' AND `hidden` = \'0\''
			. ' ORDER BY ordering'
			;
	$db->setQuery( $query );
	$mi_list = $db->loadObjectList();

	if ( !empty( $row->params['micro_integrations'] ) ) {
		$query = 'SELECT `id` AS value, CONCAT(`name`, " - ", `desc`) AS text'
				. ' FROM #__acctexp_microintegrations'
				. ' WHERE `id` IN (' . implode( ',', $row->params['micro_integrations'] ) . ')'
		 		. ' AND `hidden` = \'0\''
				;
	 	$db->setQuery( $query );
		$selected_mi = $db->loadObjectList();
	} else {
		$selected_mi = array();
	}

	$lists['micro_integrations'] = JHTML::_('select.genericlist', $mi_list, 'micro_integrations[]', 'size="' . min((count( $mi_list ) + 1), 25) . '" multiple="multiple"', 'value', 'text', $selected_mi);

	$settings = new aecSettings ( 'itemgroup', 'general' );
	if ( is_array( $customparams_values ) ) {
		$settingsparams = array_merge( $params_values, $customparams_values, $restrictions_values );
	} elseif( is_array( $restrictions_values ) ){
		$settingsparams = array_merge( $params_values, $restrictions_values );
	}
	else {
		$settingsparams = $params_values;
	}

	$lists = array_merge( $lists, $restrictionHelper->getLists( $params_values, $restrictions_values ) );

	$settings->fullSettingsArray( $params, $settingsparams, $lists ) ;

	// Call HTML Class
	$aecHTML = new aecHTML( $settings->settings, $settings->lists );
	if ( !empty( $customparamsarray ) ) {
		$aecHTML->customparams = $customparamsarray;
	}

	HTML_AcctExp::editItemGroup( $option, $aecHTML, $row );
}

function saveItemGroup( $option, $apply=0 )
{
	$db = &JFactory::getDBO();

	$row = new ItemGroup( $db );
	$row->load( $_POST['id'] );

	$post = AECToolbox::cleanPOST( $_POST, false );

	$row->savePOSTsettings( $post );

	if ( !$row->check() ) {
		echo "<script> alert('".$row->getError()."'); window.history.go(-2); </script>\n";
		exit();
	}
	if ( !$row->store() ) {
		echo "<script> alert('".$row->getError()."'); window.history.go(-2); </script>\n";
		exit();
	}

	$row->reorder();

	if ( $_POST['id'] ) {
		$id = $_POST['id'];
	} else {
		$id = $row->getMax();
	}

	if ( $apply ) {
		aecRedirect( 'index.php?option=' . $option . '&task=editItemGroup&id=' . $id, JText::_('AEC_MSG_SUCESSFULLY_SAVED') );
	} else {
		aecRedirect( 'index.php?option=' . $option . '&task=showItemGroups' );
	}
}

function removeItemGroup( $id, $option )
{
	$db = &JFactory::getDBO();

	$ids = implode( ',', $id );

	$query = 'SELECT count(*)'
			. ' FROM #__acctexp_itemgroups'
			. ' WHERE `id` IN (' . $ids . ')'
			;
	$db->setQuery( $query );
	$total = $db->loadResult();

	if ( $total == 0 ) {
		echo "<script> alert('" . html_entity_decode( JText::_('AEC_MSG_NO_ITEMS_TO_DELETE') ) . "'); window.history.go(-1);</script>\n";
		exit;
	}

	$total = 0;

	foreach ( $id as $i ) {
		$ig = new ItemGroup( $db );
		$ig->load( $i );

		if ( $ig->delete() !== false ) {
			ItemGroupHandler::removeChildren( $i, false, 'group' );

			$total++;
		}
	}

	if ( $total == 0 ) {
		echo "<script> alert('" . html_entity_decode( JText::_('AEC_MSG_NO_ITEMS_TO_DELETE') ) . "'); window.history.go(-1);</script>\n";
		exit;
	} else {
		$msg = $total . ' ' . JText::_('AEC_MSG_ITEMS_DELETED');

		aecRedirect( 'index.php?option=' . $option . '&task=showItemGroups', $msg );
	}
}

function cancelItemGroup( $option )
{
	aecRedirect( 'index.php?option=' . $option . '&task=showItemGroups', JText::_('AEC_CMN_EDIT_CANCELLED') );
}

function changeItemGroup( $cid=null, $state=0, $type, $option )
{
	$db = &JFactory::getDBO();

	if ( count( $cid ) < 1 ) {
		echo "<script> alert('" . JText::_('AEC_ALERT_SELECT_FIRST') . "'); window.history.go(-1);</script>\n";
		exit;
	}

	$total	= count( $cid );
	$cids	= implode( ',', $cid );

	$query = 'UPDATE #__acctexp_itemgroups'
			. ' SET `' . $type . '` = \'' . $state . '\''
			. ' WHERE `id` IN (' . $cids . ')'
			;
	$db->setQuery( $query );

	if ( !$db->query() ) {
		echo "<script> alert('".$db->getErrorMsg()."'); window.history.go(-1); </script>\n";
		exit();
	}

	if ( $state == '1' ) {
		$msg = ( ( strcmp( $type, 'active' ) === 0 ) ? JText::_('AEC_CMN_PUBLISHED') : JText::_('AEC_CMN_MADE_VISIBLE') );
	} elseif ( $state == '0' ) {
		$msg = ( ( strcmp( $type, 'active' ) === 0 ) ? JText::_('AEC_CMN_NOT_PUBLISHED') : JText::_('AEC_CMN_MADE_INVISIBLE') );
	}

	$msg = sprintf( JText::_('AEC_MSG_ITEMS_SUCESSFULLY'), $total ) . ' ' . $msg;

	aecRedirect( 'index.php?option=' . $option . '&task=showItemGroups', $msg );
}

function listMicroIntegrations( $option )
{
	$db = &JFactory::getDBO();

	$app = JFactory::getApplication();

	$limit		= $app->getUserStateFromRequest( "viewlistlimit", 'limit', $app->getCfg( 'list_limit' ) );
	$limitstart	= $app->getUserStateFromRequest( "viewconf{$option}limitstart", 'limitstart', 0 );

	$orderby		= $app->getUserStateFromRequest( "orderby_mi{$option}", 'orderby_mi', 'ordering ASC' );
	$search			= $app->getUserStateFromRequest( "search{$option}", 'search', '' );
	$search			= $db->getEscaped( trim( strtolower( $search ) ) );

	$filter_planid	= intval( $app->getUserStateFromRequest( "filter_planid{$option}", 'filter_planid', 0 ) );

	$ordering = false;

	if ( strpos( $orderby, 'ordering' ) !== false ) {
		$ordering = true;
	}

	// get the total number of records
	$query = 'SELECT count(*)'
		 	. ' FROM #__acctexp_microintegrations'
		 	. ' WHERE `hidden` = \'0\''
		 	;
	$db->setQuery( $query );
	$total = $db->loadResult();
	echo $db->getErrorMsg();

	if ( $limit > $total ) {
		$limitstart = 0;
	}

	jimport('joomla.html.pagination');
	$pageNav = new JPagination( $total, $limitstart, $limit );

	$where = array();
	$where[] = '`hidden` = \'0\'';

	if ( isset( $search ) && $search!= '' ) {
		$where[] = "(name LIKE '%$search%' OR class_name LIKE '%$search%')";
	}

	if ( isset( $filter_planid ) && $filter_planid > 0 ) {
		$mis = microIntegrationHandler::getMIsbyPlan( $filter_planid );

		if ( !empty( $mis ) ) {
			$where[] = "(id IN (" . implode( ',', $mis ) . "))";
		} else {
			$filter_planid = "";
		}
	}

	// get the subset (based on limits) of required records
	$query = 'SELECT * FROM #__acctexp_microintegrations';

	$query .= (count( $where ) ? ' WHERE ' . implode( ' AND ', $where ) : '' );

	$query .= ' ORDER BY ' . $orderby;
	$query .= ' LIMIT ' . $pageNav->limitstart . ',' . $pageNav->limit;

	$db->setQuery( $query );

	$rows = $db->loadObjectList();
	if ( $db->getErrorNum() ) {
		echo $db->stderr();
		return false;
	}

	$sel = array();
	$sel[] = JHTML::_('select.option', 'ordering ASC',		JText::_('ORDERING_ASC') );
	$sel[] = JHTML::_('select.option', 'ordering DESC',		JText::_('ORDERING_DESC') );
	$sel[] = JHTML::_('select.option', 'id ASC',			JText::_('ID_ASC') );
	$sel[] = JHTML::_('select.option', 'id DESC',			JText::_('ID_DESC') );
	$sel[] = JHTML::_('select.option', 'name ASC',			JText::_('NAME_ASC') );
	$sel[] = JHTML::_('select.option', 'name DESC',			JText::_('NAME_DESC') );
	$sel[] = JHTML::_('select.option', 'class_name ASC',	JText::_('CLASSNAME_ASC') );
	$sel[] = JHTML::_('select.option', 'class_name DESC',	JText::_('CLASSNAME_DESC') );

	$lists['orderNav'] = JHTML::_('select.genericlist', $sel, 'orderby_mi', 'class="inputbox" size="1" onchange="document.adminForm.submit();"', 'value', 'text', $orderby );

	// Get list of plans for filter
	$query = 'SELECT `id`, `name`'
			. ' FROM #__acctexp_plans'
			. ' ORDER BY `ordering`'
			;
	$db->setQuery( $query );
	$db_plans = $db->loadObjectList();

	$plans[] = JHTML::_('select.option', '0', JText::_('FILTER_PLAN'), 'id', 'name' );
	if ( is_array( $db_plans ) ) {
		$plans = array_merge( $plans, $db_plans );
	}
	$lists['filterplanid']	= JHTML::_('select.genericlist', $plans, 'filter_planid', 'class="inputbox" size="1" onchange="document.adminForm.submit();"', 'id', 'name', $filter_planid );

	HTML_AcctExp::listMicroIntegrations( $rows, $pageNav, $option, $lists, $search, $ordering );
}

function editMicroIntegration ( $id, $option )
{
	$db = &JFactory::getDBO();

	$user = &JFactory::getUser();

	$lists	= array();
	$mi		= new microIntegration( $db );
	$mi->load( $id );

	$aecHTML = null;

	$mi_gsettings = $mi->getGeneralSettings();

	if ( !$mi->id ) {
		// Create MI Selection List
		$mi_handler = new microIntegrationHandler();
		$mi_list = $mi_handler->getIntegrationList();

		$mi_htmllist	= array();
		if ( count( $mi_list ) > 0 ) {
			foreach ( $mi_list as $name ) {
				$mi_item = new microIntegration( $db );
				$mi_item->class_name = $name;
				if ( $mi_item->callIntegration() ) {
					if ( strpos( $name, "mi_aec" ) === 0 ) {
						$nname = "[AEC] " . $mi_item->name;
					} else {
						$nname = $mi_item->name;
					}

					$len = 60 - AECToolbox::visualstrlen( trim( $nname ) );
					if ( defined( 'JPATH_MANIFESTS' ) ) {
						// 1.6 blows up when you put in &nbps;s, so we change them later
						$fullname = str_pad( $nname, $len, '#' ) . ' - ' . substr( $mi_item->desc, 0, 80 ) . ( strlen( $mi_item->desc ) > 80 ? '...' : '');
					} else {
						$fullname = str_replace( '#', '&nbsp;', str_pad( $nname, $len, '#' ) ) . ' - ' . substr( $mi_item->desc, 0, 80 ) . ( strlen( $mi_item->desc ) > 80 ? '...' : '');
					}

					$mi_htmllist[] = JHTML::_('select.option', $name, $fullname );
				}
			}

			$lists['class_name'] = JHTML::_('select.genericlist', $mi_htmllist, 'class_name', 'size="' . min( ( count( $mi_list ) + 1 ), 25 ) . '"', 'value', 'text', '' );

			if ( defined( 'JPATH_MANIFESTS' ) ) {
				$lists['class_name'] = str_replace( '#', '&nbsp;', $lists['class_name'] );
			}
		} else {
			$lists['class_name'] = '';
		}
	}

	if ( $mi->id ) {
		// Call MI (override active check) and Settings
		if ( $mi->callIntegration( true ) ) {
			$set = array();
			foreach ( $mi_gsettings as $n => $v ) {
				if ( !isset( $mi->$n ) ) {
					if (  isset( $mi->settings[$n] ) ) {
						$set[$n] = $mi->settings[$n];
					} else {
						$set[$n] = null;
					}
				} else {
					$set[$n] = $mi->$n;
				}
			}

			$mi_gsettings[$mi->id.'remap']	= array( 'area_change', 'MI' );
			$mi_gsettings[$mi->id.'remaps']	= array( 'subarea_change', $mi->class_name );

			$mi_settings = $mi->getSettings();

			// Get lists supplied by the MI
			if ( !empty( $mi_settings['lists'] ) ) {
				$lists = array_merge( $lists, $mi_settings['lists'] );
				unset( $mi_settings['lists'] );
			}

			$settings = new aecSettings( 'MI', 'E' );
			$settings->fullSettingsArray( array_merge( $mi_gsettings, $mi_settings ), $set, $lists );

			// Call HTML Class
			$aecHTML = new aecHTML( $settings->settings, $settings->lists );

			$aecHTML->hasSettings = false;

			$aecHTML->customparams = array();
			foreach ( $mi_settings as $n => $v ) {
				$aecHTML->customparams[] = $n;
			}

			$aecHTML->hasSettings = true;
		} else {
			$short	= 'microIntegration loading failure';
			$event	= 'When trying to load microIntegration: ' . $mi->id . ', callIntegration failed';
			$tags	= 'microintegration,loading,error';
			$params = array();

			$eventlog = new eventLog( $db );
			$eventlog->issue( $short, $tags, $event, 128, $params );
		}
	} else {
		$settings = new aecSettings( 'MI', 'E' );
		$settings->fullSettingsArray( $mi_gsettings, array(), $lists );

		// Call HTML Class
		$aecHTML = new aecHTML( $settings->settings, $settings->lists );

		$aecHTML->hasSettings = false;
	}

	HTML_AcctExp::editMicroIntegration( $option, $mi, $lists, $aecHTML );
}

function saveMicroIntegration( $option, $apply=0 )
{
	$db = &JFactory::getDBO();

	unset( $_POST['option'] );
	unset( $_POST['task'] );

	$id = $_POST['id'] ? $_POST['id'] : 0;

	$mi = new microIntegration( $db );
	$mi->load( $id );

	if ( !empty( $_POST['class_name'] ) ) {
		$load = $mi->callDry( $_POST['class_name'] );
	} else {
		$load = $mi->callIntegration( 1 );
	}

	if ( $load ) {
		$mi->savePostParams( $_POST );

		$mi->storeload();
	} else {
		$short	= 'microIntegration storing failure';
		if ( !empty( $_POST['class_name'] ) ) {
			$event	= 'When trying to store microIntegration: ' . $_POST['class_name'] . ', callIntegration failed';
		} else {
			$event	= 'When trying to store microIntegration: ' . $mi->id . ', callIntegration failed';
		}
		$tags	= 'microintegration,loading,error';
		$params = array();

		$eventlog = new eventLog( $db );
		$eventlog->issue( $short, $tags, $event, 128, $params );
	}

	$mi->reorder();

	if ( $id ) {
		if ( $apply ) {
			aecRedirect( 'index.php?option=' . $option . '&task=editMicroIntegration&id=' . $id, JText::_('AEC_MSG_SUCESSFULLY_SAVED') );
		} else {
			aecRedirect( 'index.php?option=' . $option . '&task=showMicroIntegrations', JText::_('AEC_MSG_SUCESSFULLY_SAVED') );
		}
	} else {
		aecRedirect( 'index.php?option=' . $option . '&task=editMicroIntegration&id=' . $mi->id , JText::_('AEC_MSG_SUCESSFULLY_SAVED') );
	}

}

function removeMicroIntegration( $id, $option )
{
	$db = &JFactory::getDBO();

	$ids = implode( ',', $id );

	$query = 'SELECT count(*)'
			. ' FROM #__acctexp_microintegrations'
			. ' WHERE `id` IN (' . $ids . ')'
			;
	$db->setQuery( $query );
	$total = $db->loadResult();

	if ( $total==0 ) {
		echo "<script> alert('" . html_entity_decode( JText::_('AEC_MSG_NO_ITEMS_TO_DELETE') ) . "'); window.history.go(-1);</script>\n";
		exit;
	}

	// Call On-Deletion function
	foreach ( $id as $k ) {
		$mi = new microIntegration($db);
		$mi->load($k);
		if ( $mi->callIntegration() ) {
			$mi->delete();
		}
	}

	// Micro Integrations from table
	$query = 'DELETE FROM #__acctexp_microintegrations'
			. ' WHERE `id` IN (' . $ids . ')'
			;
	$db->setQuery( $query	);

	if ( !$db->query() ) {
		echo "<script> alert('".$db->getErrorMsg()."'); window.history.go(-1); </script>\n";
		exit();
	}

	$msg = $total . ' ' . JText::_('AEC_MSG_ITEMS_DELETED');

	aecRedirect( 'index.php?option=' . $option . '&task=showMicroIntegrations', $msg );
}

function cancelMicroIntegration( $option )
{
	aecRedirect( 'index.php?option=' . $option . '&task=showMicroIntegrations', JText::_('AEC_CMN_EDIT_CANCELLED') );
}

// Changes the state of one or more content pages
// @param array An array of unique plan id numbers
// @param integer 0 if unpublishing, 1 if publishing
//

function changeMicroIntegration( $cid=null, $state=0, $option )
{
	$db = &JFactory::getDBO();

	if ( count( $cid ) < 1 ) {
		$action = $state == 1 ? JText::_('AEC_CMN_TOPUBLISH'): JText::_('AEC_CMN_TOUNPUBLISH');
		echo "<script> alert('" . sprintf( html_entity_decode( JText::_('AEC_ALERT_SELECT_FIRST_TO') ), $action ) . "'); window.history.go(-1);</script>\n";
		exit;
	}

	$total = count( $cid );
	$cids = implode( ',', $cid );

	$query = 'UPDATE #__acctexp_microintegrations'
			. ' SET `active` = \'' . $state . '\''
			. ' WHERE `id` IN (' . $cids . ')'
			;
	$db->setQuery( $query );
	if ( !$db->query() ) {
		echo "<script> alert('".$db->getErrorMsg()."'); window.history.go(-1); </script>\n";
		exit();
	}

	if ( $state == '1' ) {
		$msg = $total . ' ' . JText::_('AEC_MSG_ITEMS_SUCC_PUBLISHED');
	} elseif ( $state == '0' ) {
		$msg = $total . ' ' . JText::_('AEC_MSG_ITEMS_SUCC_UNPUBLISHED');
	}

	aecRedirect( 'index.php?option=' . $option . '&task=showMicroIntegrations', $msg );
}

function listCoupons( $option, $type )
{
 	$db = &JFactory::getDBO();

	$app = JFactory::getApplication();

 	$limit		= $app->getUserStateFromRequest( "viewlistlimit", 'limit', $app->getCfg( 'list_limit' ) );
	$limitstart = $app->getUserStateFromRequest( "viewconf{$option}limitstart", 'limitstart', 0 );

	$total = 0;

	if ( !$type ) {
	 	$table = '#__acctexp_coupons';
	} else {
	 	$table = '#__acctexp_coupons_static';
	}

	$query = 'SELECT count(*)'
			. ' FROM ' . $table
			;
 	$db->setQuery( $query );
 	$total = $db->loadResult();

 	if ( $limit > $total ) {
 		$limitstart = 0;
 	}

	jimport('joomla.html.pagination');
	$pageNav = new JPagination( $total, $limitstart, $limit );

 	// get the subset (based on limits) of required records
 	$query = 'SELECT *'
		 	. ' FROM ' . $table
		 	. ' GROUP BY `id`'
		 	. ' ORDER BY `ordering`'
		 	. ' LIMIT ' . $pageNav->limitstart . ',' . $pageNav->limit
		 	;
 	$db->setQuery( $query	);

 	$rows = $db->loadObjectList();
 	if ( $db->getErrorNum() ) {
 		echo $db->stderr();
 		return false;
 	}

	HTML_AcctExp::listCoupons( $rows, $pageNav, $option, $type );
 }

function editCoupon( $id, $option, $new, $type )
{
	$db = &JFactory::getDBO();

	$user = &JFactory::getUser();

	$app = JFactory::getApplication();

	$lists					= array();
	$params_values			= array();
	$restrictions_values	= array();

	$cph = new couponHandler();

	if ( !$new ) {
		$cph->coupon = new Coupon( $db, $type );
		$cph->coupon->load( $id );

		$params_values			= $cph->coupon->params;
		$discount_values		= $cph->coupon->discount;
		$restrictions_values	= $cph->coupon->restrictions;
	} else {
		$cph->coupon = new Coupon( $db, 1 );
		$cph->coupon->createNew();

		$discount_values		= array();
		$restrictions_values	= array();
	}

	// We need to convert the values that are set as object properties
	$params_values['active']				= $cph->coupon->active;
	$params_values['type']					= $type;
	$params_values['name']					= $cph->coupon->name;
	$params_values['desc']					= $cph->coupon->desc;
	$params_values['coupon_code']			= $cph->coupon->coupon_code;
	$params_values['usecount']				= $cph->coupon->usecount;
	$params_values['micro_integrations']	= $cph->coupon->micro_integrations;

	// params and their type values
	$params['active']						= array( 'list_yesno',		1 );
	$params['type']							= array( 'list_yesno',		1 );
	$params['name']							= array( 'inputC',			'' );
	$params['desc']							= array( 'inputE',			'' );
	$params['coupon_code']					= array( 'inputC',			'' );
	$params['micro_integrations']			= array( 'list',			'' );

	$params['params_remap']					= array( 'subarea_change',	'params' );

	$params['amount_use']					= array( 'list_yesno',		'' );
	$params['amount']						= array( 'inputB',			'' );
	$params['amount_percent_use']			= array( 'list_yesno',		'' );
	$params['amount_percent']				= array( 'inputB',			'' );
	$params['percent_first']				= array( 'list_yesno',		'' );
	$params['useon_trial']					= array( 'list_yesno',		'' );
	$params['useon_full']					= array( 'list_yesno',		'1' );
	$params['useon_full_all']				= array( 'list_yesno',		'' );

	$params['has_start_date']				= array( 'list_yesno',		1 );
	$params['start_date']					= array( 'list_date',		date( 'Y-m-d', ( time() + ( $app->getCfg( 'offset' ) * 3600 ) )) );
	$params['has_expiration']				= array( 'list_yesno',		0);
	$params['expiration']					= array( 'list_date',		date( 'Y-m-d', ( time() + ( $app->getCfg( 'offset' ) * 3600 ) ) ) );
	$params['has_max_reuse']				= array( 'list_yesno',		1 );
	$params['max_reuse']					= array( 'inputB',			1 );
	$params['has_max_peruser_reuse']		= array( 'list_yesno',		1 );
	$params['max_peruser_reuse']			= array( 'inputB',			1 );
	$params['usecount']						= array( 'inputB',			0 );

	$params['usage_plans_enabled']			= array( 'list_yesno',		0 );
	$params['usage_plans']					= array( 'list',			0 );

	$params['usage_cart_full']				= array( 'list_yesno',		0 );
	$params['cart_multiple_items']			= array( 'list_yesno',		0 );
	$params['cart_multiple_items_amount']	= array( 'inputB',			'' );

	$params['restr_remap']					= array( 'subarea_change',	'restrictions' );

	$params['depend_on_subscr_id']			= array( 'list_yesno',		0 );
	$params['subscr_id_dependency']			= array( 'inputB',			'' );
	$params['allow_trial_depend_subscr']	= array( 'list_yesno',		0 );

	$params['restrict_combination']			= array( 'list_yesno',		0 );
	$params['bad_combinations']				= array( 'list',			'' );

	$params['allow_combination']			= array( 'list_yesno',		0 );
	$params['good_combinations']			= array( 'list',			'' );

	$params['restrict_combination_cart']	= array( 'list_yesno',		0 );
	$params['bad_combinations_cart']		= array( 'list',			'' );

	$params['allow_combination_cart']		= array( 'list_yesno',		0 );
	$params['good_combinations_cart']		= array( 'list',			'' );

	$restrictionHelper = new aecRestrictionHelper();
	$params = array_merge( $params, $restrictionHelper->getParams() );

	// get available plans
	$available_plans = array();
	$available_plans[]			= JHTML::_('select.option', '0', JText::_('PAYPLAN_NOPLAN') );

	$query = 'SELECT `id` as value, `name` as text'
			. ' FROM #__acctexp_plans'
			;
	$db->setQuery( $query );
	$plans = $db->loadObjectList();

 	if ( is_array( $plans ) ) {
 		$all_plans					= array_merge( $available_plans, $plans );
 	} else {
 		$all_plans					= $available_plans;
 	}
	$total_all_plans			= min( max( ( count( $all_plans ) + 1 ), 4 ), 20 );

	// get usages
	if ( !empty( $restrictions_values['usage_plans'] ) ) {
		$query = 'SELECT `id` AS value, `name` as text'
				. ' FROM #__acctexp_plans'
				. ' WHERE `id` IN (' . implode( ',', $restrictions_values['usage_plans'] ) . ')'
				;
		$db->setQuery( $query );

	 	$sel_usage_plans = $db->loadObjectList();
	} else {
		$sel_usage_plans = 0;
	}

	$lists['usage_plans']		= JHTML::_('select.genericlist', $all_plans, 'usage_plans[]', 'size="' . $total_all_plans . '" multiple="multiple"',
									'value', 'text', $sel_usage_plans);


	// get available micro integrations
	$available_mi = array();

	$query = 'SELECT `id` AS value, CONCAT(`name`, " - ", `desc`) AS text'
			. ' FROM #__acctexp_microintegrations'
			. ' WHERE `active` = 1'
			. ' ORDER BY `ordering`'
			;
	$db->setQuery( $query );
	$mi_list = $db->loadObjectList();

	$mis = array();
	if ( !empty( $mi_list ) && !empty( $params_values['micro_integrations'] ) ) {
		foreach ( $mi_list as $mi_item ) {
			if ( in_array( $mi_item->value, $params_values['micro_integrations'] ) ) {
				$mis[] = $mi_item->value;
			}
		}
	}

 	if ( !empty( $mis ) ) {
	 	$query = 'SELECT `id` AS value, CONCAT(`name`, " - ", `desc`) AS text'
			 	. ' FROM #__acctexp_microintegrations'
			 	. ( !empty( $mis ) ? ' WHERE `id` IN (' . implode( ',', $mis ) . ')' : '' )
			 	;
	 	$db->setQuery( $query );
		$selected_mi = $db->loadObjectList();
 	} else {
 		$selected_mi = array();
 	}

	$lists['micro_integrations'] = JHTML::_('select.genericlist', $mi_list, 'micro_integrations[]', 'size="' . min((count( $mi_list ) + 1), 25) . '" multiple="multiple"', 'value', 'text', $selected_mi );

	$query = 'SELECT `coupon_code` as value, `coupon_code` as text'
			. ' FROM #__acctexp_coupons'
			. ' WHERE `coupon_code` != \'' . $cph->coupon->coupon_code . '\''
			;
	$db->setQuery( $query );
	$coupons = $db->loadObjectList();

	$query = 'SELECT `coupon_code` as value, `coupon_code` as text'
			. ' FROM #__acctexp_coupons_static'
			. ' WHERE `coupon_code` != \'' . $cph->coupon->coupon_code . '\''
			;
	$db->setQuery( $query );
	$coupons = array_merge( $db->loadObjectList(), $coupons );

	$cpl = array( 'bad_combinations', 'good_combinations', 'bad_combinations_cart', 'good_combinations_cart' );

	foreach ( $cpl as $cpn ) {
		$cur = array();

		if ( !empty( $restrictions_values[$cpn] ) ) {
			$query = 'SELECT `coupon_code` as value, `coupon_code` as text'
					. ' FROM #__acctexp_coupons'
					. ' WHERE `coupon_code` IN (\'' . implode( '\',\'', $restrictions_values[$cpn] ) . '\')'
					;
			$db->setQuery( $query );
			$cur = $db->loadObjectList();

			$query = 'SELECT `coupon_code` as value, `coupon_code` as text'
					. ' FROM #__acctexp_coupons_static'
					. ' WHERE `coupon_code` IN (\'' . implode( '\',\'', $restrictions_values[$cpn] ) . '\')'
					;
			$db->setQuery( $query );
			$nc = $db->loadObjectList();

			if ( !empty( $nc ) ) {
				$cur = array_merge( $nc, $cur );
			}
		}

		$lists[$cpn] = JHTML::_('select.genericlist', $coupons, $cpn.'[]', 'size="' . min((count( $coupons ) + 1), 25) . '" multiple="multiple"', 'value', 'text', $cur);
	}

	$lists = array_merge( $lists, $restrictionHelper->getLists( $params_values, $restrictions_values ) );

	$settings = new aecSettings( 'coupon', 'general' );

	$settingsparams = array_merge( $params_values, $discount_values, $restrictions_values );

	$settings->fullSettingsArray( $params, $settingsparams, $lists );

	// Call HTML Class
	$aecHTML = new aecHTML( $settings->settings, $settings->lists );

	HTML_AcctExp::editCoupon( $option, $aecHTML, $cph->coupon, $type );
}

function saveCoupon( $option, $type, $apply=0 )
{
	$db = &JFactory::getDBO();

	$new = 0;
	$type = $_POST['type'];

	if ( $_POST['coupon_code'] != '' ) {

		$cph = new couponHandler();

		if ( !empty( $_POST['id'] ) ) {
			$cph->coupon = new Coupon( $db, $type );
			$cph->coupon->load( $_POST['id'] );
			$cph->type = $type;
			if ( empty( $cph->coupon->id ) ) {
				$cph->coupon = new Coupon( $db, !$type );
				$cph->coupon->load( $_POST['id'] );
				$cph->type = !$type;
			}
			if ( $cph->coupon->id ) {
				$cph->status = 1;
			}
		} else {
			$cph->load( $_POST['coupon_code'] );
		}

		if ( !$cph->status ) {
			$cph->coupon = new coupon( $db, $type );
			$cph->coupon->createNew( $_POST['coupon_code'] );
			$cph->status = true;
			$new = 1;
		}

		if ( $cph->status ) {
			if ( !$new ) {
				if ( $cph->type != $_POST['type'] ) {
					$cph->switchType();
				}
			}

			unset( $_POST['type'] );
			unset( $_POST['id'] );
			$post = AECToolbox::cleanPOST( $_POST, false );

			$cph->coupon->savePOSTsettings( $post );

			$cph->coupon->storeload();
		} else {
			$short	= 'coupon store failure';
			$event	= 'When trying to store coupon';
			$tags	= 'coupon,loading,error';
			$params = array();

			$eventlog = new eventLog( $db );
			$eventlog->issue( $short, $tags, $event, 128, $params );
		}

		$cph->coupon->reorder();

		if ( $cph->coupon->id ) {
			$id = $cph->coupon->id;
		} else {
			$id = $cph->coupon->getMax();
		}

		if ( $apply ) {
			aecRedirect( 'index.php?option=' . $option . '&task=editCoupon' . ( $type ? 'Static' : '' ) . '&id=' . $id, JText::_('AEC_MSG_SUCESSFULLY_SAVED') );
		} else {
			aecRedirect( 'index.php?option=' . $option . '&task=showCoupons' . ( $type ? 'Static' : '' ), JText::_('AEC_MSG_SUCESSFULLY_SAVED') );
		}
	} else {
		aecRedirect( 'index.php?option=' . $option . '&task=showCoupons' . ( $type ? 'Static' : '' ), JText::_('AEC_MSG_NO_COUPON_CODE') );
	}

}

function removeCoupon( $id, $option, $returnTask, $type )
{
	$db = &JFactory::getDBO();

	$ids = implode( ',', $id );

	// Delete Coupons from table
	$query = 'DELETE FROM #__acctexp_coupons'
			. ( $type ? '_static' : '' )
			. ' WHERE `id` IN (' . $ids . ')'
			;
	$db->setQuery( $query	);

	if ( !$db->query() ) {
		echo "<script> alert('".$db->getErrorMsg()."'); window.history.go(-1); </script>\n";
		exit();
	}

	$msg = JText::_('AEC_MSG_ITEMS_DELETED');

	aecRedirect( 'index.php?option=' . $option . '&task=showCoupons' . ( $type ? 'Static' : '' ), $msg );
}

function cancelCoupon( $option, $type )
{
	aecRedirect( 'index.php?option=' . $option . '&task=showCoupons' . ($type ? 'Static' : '' ), JText::_('AEC_CMN_EDIT_CANCELLED') );
}

function changeCoupon( $cid=null, $state=0, $option, $type )
{
	$db = &JFactory::getDBO();

	if ( count( $cid ) < 1 ) {
		$action = $state == 1 ? JText::_('AEC_CMN_TOPUBLISH') : JText::_('AEC_CMN_TOUNPUBLISH');
		echo "<script> alert('" . sprintf( html_entity_decode( JText::_('AEC_ALERT_SELECT_FIRST_TO') ) ), $action . "'); window.history.go(-1);</script>\n";
		exit;
	}

	$total	= count( $cid );
	$cids	= implode( ',', $cid );

	$query = 'UPDATE #__acctexp_coupons' . ( $type ? '_static' : '' )
			. ' SET `active` = \'' . $state . '\''
			. ' WHERE `id` IN (' . $cids . ')'
			;
	$db->setQuery( $query	);
	$db->query();

	if ( $state ) {
		$msg = $total . ' ' . JText::_('AEC_MSG_ITEMS_SUCC_PUBLISHED');
	} else {
		$msg = $total . ' ' . JText::_('AEC_MSG_ITEMS_SUCC_UNPUBLISHED');
	}

	aecRedirect( 'index.php?option=' . $option . '&task=showCoupons' . ( $type ? 'Static' : '' ), $msg );
}

function editCSS( $option ) {
	$file = JPATH_SITE . '/media/' . $option . '/css/site.css';

	if ( $fp = fopen( $file, 'r' ) ) {
		$content = fread( $fp, filesize( $file ) );
		$content = htmlspecialchars( $content );
		General_css::editCSSSource( $content, $option );
	} else {
		aecRedirect( 'index.php?option='. $option .'&task=editCSS', sprintf( JText::_('AEC_MSG_OP_FAILED'), $file ) );
	}
}

function saveCSS( $option )
{
	$filecontent = aecGetParam( 'filecontent' );

	if ( !$filecontent ) {
		aecRedirect( 'index.php?option='. $option .'&task=editCSS', JText::_('AEC_MSG_OP_FAILED_EMPTY') );
	}

	$file			= JPATH_SITE .'/media/' . $option . '/css/site.css';
	$enable_write	= aecGetParam( 'enable_write', 0 );
	$oldperms		= fileperms( $file );

	if ( $enable_write ) {
		@chmod( $file, $oldperms | 0222 );
	}

	clearstatcache();
	if ( is_writable( $file ) == false ) {
		aecRedirect( 'index.php?option='. $option .'&task=editCSS', JText::_('AEC_MSG_OP_FAILED_NOT_WRITEABLE') );
	}

	if ( $fp = fopen ($file, 'wb') ) {
		fputs( $fp, stripslashes( $filecontent ) );
		fclose( $fp );
		if ( $enable_write ) {
			@chmod( $file, $oldperms );
		} elseif ( aecGetParam( 'disable_write', 0 ) ) {
			@chmod( $file, $oldperms & 0777555 );
		}
		aecRedirect( 'index.php?option='. $option .'&task=editCSS', JText::_('AEC_CMN_FILE_SAVED') );
	} elseif ( $enable_write ) {
		@chmod($file, $oldperms);
		aecRedirect( 'index.php?option='. $option .'&task=editCSS', JText::_('AEC_MSG_OP_FAILED_NO_WRITE') );
	}

}

function cancelCSS ( $option )
{
	aecRedirect( 'index.php?option='. $option );
}

function invoices( $option )
{
	$db = &JFactory::getDBO();

	$app = JFactory::getApplication();

	$limit 		= intval( $app->getUserStateFromRequest( "viewlistlimit", 'limit', $app->getCfg( 'list_limit' ) ) );
	$limitstart = intval( $app->getUserStateFromRequest( "view{$option}limitstart", 'limitstart', 0 ) );
	$search 	= $app->getUserStateFromRequest( "search{$option}_invoices", 'search', '' );

	if ( $search ) {
		$unformatted = $db->getEscaped( trim( strtolower( $search ) ) );

		$where = 'LOWER(`invoice_number`) LIKE \'%' . $unformatted . '%\''
				. ' OR LOWER(`secondary_ident`) LIKE \'%' . $unformatted . '%\''
				. ' OR `id` LIKE \'%' . $unformatted . '%\''
				. ' OR LOWER(`invoice_number_format`) LIKE \'%' . $unformatted . '%\''
				;
	}

	// get the total number of records
	$query = 'SELECT count(*)'
			. ' FROM #__acctexp_invoices'
			;
	$db->setQuery( $query );
	$total = $db->loadResult();
	echo $db->getErrorMsg();

	jimport('joomla.html.pagination');
	$pageNav = new JPagination( $total, $limitstart, $limit );

	// Lets grab the data and fill it in.
	$query = 'SELECT *'
			. ' FROM #__acctexp_invoices'
			. ( !empty( $where ) ? ( ' WHERE ' . $where . ' ' ) : '' )
			. ' ORDER BY `created_date` DESC'
			. ' LIMIT ' . $pageNav->limitstart . ',' . $pageNav->limit;
			;
	$db->setQuery( $query );
	$rows = $db->loadObjectList();

	if ( $db->getErrorNum() ) {
		echo $db->stderr();
		return false;
	}

	$cclist = array();
	foreach ( $rows as $id => $row ) {
		$in_formatted = Invoice::formatInvoiceNumber( $row );

		if ( $in_formatted != $row->invoice_number ) {
			$rows[$id]->invoice_number = $row->invoice_number . "\n" . '(' . $in_formatted . ')';
		}

		if ( !empty( $row->coupons ) ) {
			$coupons = unserialize( base64_decode( $row->coupons ) );
		} else {
			$coupons = null;
		}

		if ( !empty( $coupons ) ) {
			$rows[$id]->coupons = "";

			$couponslist = array();
			foreach ( $coupons as $coupon_code ) {
				if ( !isset( $cclist[$coupon_code] ) ) {
					$cclist[$coupon_code] = couponHandler::idFromCode( $coupon_code );
				}

				if ( !empty( $cclist[$coupon_code]['id'] ) ) {
					$couponslist[] = '<a href="index.php?option=com_acctexp&amp;task=' . ( $cclist[$coupon_code]['type'] ? 'editcouponstatic' : 'editcoupon' ) . '&amp;id=' . $cclist[$coupon_code]['id'] . '">' . $coupon_code . '</a>';
				}
			}

			$rows[$id]->coupons = implode( ", ", $couponslist );
		} else {
			$rows[$id]->coupons = null;
		}

		$query = 'SELECT username'
				. ' FROM #__users'
				. ' WHERE `id` = \'' . $row->userid . '\''
				;
		$db->setQuery( $query );
		$username = $db->loadResult();

		$rows[$id]->username = '<a href="index.php?option=com_acctexp&amp;task=edit&userid=' . $row->userid . '">';

		if ( !empty( $username ) ) {
			$rows[$id]->username .= $username . '</a>';
		} else {
			$rows[$id]->username .= $row->userid;
		}

		$rows[$id]->username .= '</a>';
	}

	HTML_AcctExp::viewinvoices( $option, $rows, $search, $pageNav );
}

function clearInvoice( $option, $invoice_number, $applyplan, $task )
{
	$db = &JFactory::getDBO();

	$invoiceid = AECfetchfromDB::InvoiceIDfromNumber( $invoice_number, 0, true );

	if ( $invoiceid ) {
		$db = &JFactory::getDBO();

		$objInvoice = new Invoice( $db );
		$objInvoice->load( $invoiceid );

		if ( $applyplan ) {
			$objInvoice->pay();
		} else {
			$objInvoice->setTransactionDate();
		}

		if ( strcmp( $task, 'edit' ) == 0) {
			$userid = '&userid=' . $objInvoice->userid;
		} else {
			$userid = '';
		}
	}

	aecRedirect( 'index.php?option=' . $option . '&task=' . $task . $userid, JText::_('AEC_MSG_INVOICE_CLEARED') );
}

function cancelInvoice( $option, $invoice_number, $task )
{
	$db = &JFactory::getDBO();

	$invoiceid = AECfetchfromDB::InvoiceIDfromNumber( $invoice_number, 0, true );

	if ( $invoiceid ) {
		$objInvoice = new Invoice( $db );
		$objInvoice->load( $invoiceid );
		$uid = $objInvoice->userid;

		$objInvoice->delete();

		if ( strcmp( $task, 'edit' ) == 0 ) {
			$userid = '&userid=' . $uid;
		} else {
			$userid = '';
		}
	}

	aecRedirect( 'index.php?option=' . $option . '&task=' . $task . $userid, JText::_('REMOVED') );
}

function AdminInvoicePrintout( $option, $invoice_number )
{
	$db = &JFactory::getDBO();

	$invoice = new Invoice( $db );
	$invoice->loadInvoiceNumber( $invoice_number );

	$iFactory = new InvoiceFactory( $invoice->userid, null, null, null, null, null, false, true );
	$iFactory->invoiceprint( 'com_acctexp', $invoice->invoice_number );
}

function history( $option )
{
	$db = &JFactory::getDBO();

	$app = JFactory::getApplication();

	$limit 		= intval( $app->getUserStateFromRequest( "viewlistlimit", 'limit', $app->getCfg( 'list_limit' ) ) );
	$limitstart = intval( $app->getUserStateFromRequest( "view{$option}limitstart", 'limitstart', 0 ) );
	$search 	= $app->getUserStateFromRequest( "search{$option}_log_history", 'search', '' );

	$where = array();
	if ( $search ) {
		$where[] = 'LOWER(`user_name`) LIKE \'%' . $db->getEscaped( trim( strtolower( $search ) ) ) . '%\'';
	}

	// get the total number of records
	$query = 'SELECT count(*)'
			. '  FROM #__acctexp_log_history'
			;
	$db->setQuery( $query );
	$total = $db->loadResult();
	echo $db->getErrorMsg();

	jimport('joomla.html.pagination');
	$pageNav = new JPagination( $total, $limitstart, $limit );

	// Lets grab the data and fill it in.
	$query = 'SELECT *'
			. ' FROM #__acctexp_log_history'
			. ( count( $where ) ? ' WHERE ' . implode( ' AND ', $where ) : '' )
			. ' GROUP BY `transaction_date`'
			. ' ORDER BY `transaction_date` DESC'
			. ' LIMIT ' . $pageNav->limitstart . ',' . $pageNav->limit
			;
	$db->setQuery( $query );
	$rows = $db->loadObjectList();

	if ( $db->getErrorNum() ) {
		echo $db->stderr();
		return false;
	}

	HTML_AcctExp::viewhistory( $option, $rows, $search, $pageNav );
}

function eventlog( $option )
{
	$db = &JFactory::getDBO();

	$app = JFactory::getApplication();

	$limit 		= intval( $app->getUserStateFromRequest( "viewlistlimit", 'limit', $app->getCfg( 'list_limit' ) ) );
	$limitstart = intval( $app->getUserStateFromRequest( "view{$option}limitstart", 'limitstart', 0 ) );
	$search 	= $app->getUserStateFromRequest( "search{$option}_invoices", 'search', '' );

	$where = array();
	if ( $search ) {
		$where[] = 'LOWER(`event`) LIKE \'%' . $db->getEscaped( trim( strtolower( $search ) ) ) . '%\'';
	}

	$tags = ( !empty( $_REQUEST['tags'] ) ? $_REQUEST['tags'] : null );

	if ( is_array( $tags ) ) {
		foreach ( $tags as $tag ) {
			$where[] = 'LOWER(`tags`) LIKE \'%' . trim( strtolower( $tag ) ) . '%\'';
		}
	}

	// get the total number of records
	$query = 'SELECT count(*)'
			. ' FROM #__acctexp_eventlog'
			;
	$db->setQuery( $query );
	$total = $db->loadResult();
	echo $db->getErrorMsg();

	jimport('joomla.html.pagination');
	$pageNav = new JPagination( $total, $limitstart, $limit );

	// Lets grab the data and fill it in.
	$query = 'SELECT id'
			. ' FROM #__acctexp_eventlog'
			. ( count( $where ) ? ' WHERE ' . implode( ' AND ', $where ) : '' )
			. ' ORDER BY `id` DESC'
			. ' LIMIT ' . $pageNav->limitstart . ',' . $pageNav->limit
			;
	$db->setQuery( $query );
	$rows = $db->loadResultArray();

	if ( $db->getErrorNum() ) {
		echo $db->stderr();
		return false;
	}

	$events = array();
	foreach ( $rows as $id ) {
		$row = new EventLog( $db );
		$row->load( $id );

		$events[$id]->id		= $row->id;
		$events[$id]->datetime	= $row->datetime;
		$events[$id]->short		= $row->short;
		$events[$id]->tags		= implode( ', ', explode( ',', $row->tags ) );
		$events[$id]->event		= $row->event;
		$events[$id]->level		= $row->level;
		$events[$id]->notify	= $row->notify;

		$params = array();
		if ( !empty( $row->params ) && is_array( $row->params ) ) {
			foreach ( $row->params as $key => $value ) {
				switch ( $key ) {
					case 'userid':
						$content = '<a href="index.php?option=com_acctexp&amp;task=edit&userid=' . $value . '">' . $value . '</a>';
						break;
					case 'invoice_number':
						$content = '<a href="index.php?option=com_acctexp&amp;task=quicklookup&search=' . $value . '">' . $value . '</a>';
						break;
					default:
						$content = $value;
						break;
				}
				$params[] = $key . '(' . $content . ')';
			}
		}
		$events[$id]->params = implode( ', ', $params );

		if ( strpos( $row->event, '<?xml' ) !== false ) {
			$events[$id]->event = "<p><strong>XML cell - decoded as:</strong></p><pre>".htmlentities($row->event)."</pre>";
		} else {
			$format = @json_decode( $row->event );

			if ( is_array( $format ) || is_object( $format ) ) {
				$events[$id]->event = "<p><strong>JSON cell - decoded as:</strong></p><pre>".print_r($format,true)."</pre>";
			} else {
				$events[$id]->event = htmlentities( stripslashes( $events[$id]->event ) );
			}
		}
	}

	HTML_AcctExp::eventlog( $option, $events, $search, $pageNav );
}

function quicklookup( $option )
{
	$db = &JFactory::getDBO();

	$searcc	= trim( aecGetParam( 'search', 0 ) );
	$search = $db->getEscaped( strtolower( $searcc ) );

	$userid = 0;
	$k = 0;

	if ( strpos( $search, 'supercommand:' ) !== false ) {
		$supercommand = new aecSuperCommand();

		if ( $supercommand->parseString( $search ) ) {
			if ( strpos( $search, '!' ) === 0 ) {
				$armed = true;
			} else {
				$armed = false;
			}

			$return = $supercommand->query( $armed );

			if ( $return > 1 ) {
				$multiple = true;
			} else {
				$multiple = false;
			}

			if ( ( $return != false ) && !$armed ) {
				$r['search'] = "!" . $search;

				$r['return'] = '<div style="font-size:110%;border: 2px solid #da5;padding:16px;">This supercommand would affect ' . $return . " user" . ($multiple ? "s":"") . ". Click the search button again to carry out the query.</div>";
			} elseif ( $return != false ) {
				$r['search'] = "";
				$r['return'] = '<div style="font-size:110%;border: 2px solid #da5;padding:16px;">If you\'re so clever, you tell us what <strong>colour</strong> it should be!? (Everything went fine. Really! It affected ' . $return . " user" . ($multiple ? "s":"") . ")</div>";
			} else {
				$r['search'] = "";
				$r['return'] = '<div style="font-size:110%;border: 2px solid #da5;padding:16px;">Something went wrong. No users found.</div>';
			}

			return $r;
		}

		return "I think you ought to know I'm feeling very depressed. (Something was wrong with your query.)";
	}

	// Try username and name
	$queries[$k] = 'FROM #__users'
				. ' WHERE LOWER( `username` ) LIKE \'%' . $search . '%\' OR LOWER( `name` ) LIKE \'%' . $search . '%\''
				;
	$qfields[$k] = 'id';
	$k++;

	// If its not that, how about the user email?
	$queries[$k] = 'FROM #__users'
				. ' WHERE LOWER( `email` ) = \'' . $search . '\''
				;
	$qfields[$k] = 'id';
	$k++;

	// Try to find this as a userid
	$queries[$k] = 'FROM #__users'
				. ' WHERE `id` = \'' . $search . '\''
				;
	$qfields[$k] = 'id';
	$k++;

	// Or maybe its an invoice number?
	$queries[$k] = 'FROM #__acctexp_invoices'
				. ' WHERE LOWER( `invoice_number` ) = \'' . $search . '\''
				. ' OR LOWER( `secondary_ident` ) = \'' . $search . '\''
				;
	$qfields[$k] = 'userid';
	$k++;

	foreach ( $queries as $qid => $base_query ) {
		$query = 'SELECT count(*) ' . $base_query;
		$db->setQuery( $query );
		$existing = $db->loadResult();

		if ( $existing ) {
			$query = 'SELECT `' . $qfields[$qid] . '` ' . $base_query;
			$db->setQuery( $query );

			if ( $existing > 1 ) {
				$users = $db->loadResultArray();

				$return = array();
				foreach ( $users as $user ) {
					$JTableUser = new JTableUser( $db );
					$JTableUser->load( $user );
					$userlink = '<a href="';
					$userlink .= JURI::base() . 'index.php?option=com_acctexp&amp;task=edit&amp;userid=' . $JTableUser->id;
					$userlink .= '">';
					$userlink .= $JTableUser->name . ' (' . $JTableUser->username . ')';
					$userlink .= '</a>';

					$return[] = $userlink;
				}

				return implode( ', ', $return );
			} else {
				return $db->loadResult();
			}
		}
	}

	if ( strpos( $search, 'jsonserialencode' ) === 0 ) {
		$s = trim( substr( $searcc, 16 ) );
		if ( !empty( $s ) ) {
			$return = base64_encode( serialize( jsoonHandler::decode( $s ) ) );
			return '<div style="text-align:left;">' . $return . '</div>';
		}
	}

	if ( strpos( $search, 'serialdecodejson' ) === 0 ) {
		$s = trim( substr( $searcc, 16 ) );
		if ( !empty( $s ) ) {
			$return = jsoonHandler::encode( unserialize( base64_decode( $s ) ) );
			return '<div style="text-align:left;">' . $return . '</div>';
		}
	}

	if ( strpos( $search, 'serialdecode' ) === 0 ) {
		$s = trim( substr( $searcc, 12 ) );
		if ( !empty( $s ) ) {
			$return = unserialize( base64_decode( $s ) );
			return '<div style="text-align:left;">' . obsafe_print_r( $return, true, true ) . '</div>';
		}
	}

	if ( strpos( $search, 'unserialize' ) === 0 ) {
		$s = trim( substr( $searcc, 11 ) );
		if ( !empty( $s ) ) {
			$return = unserialize( $s );
			return '<div style="text-align:left;">' . obsafe_print_r( $return, true, true ) . '</div>';
		}
	}

	$maybe = array( '?', '??', '???', '????', 'what to do', 'need strategy', 'help', 'help me', 'huh?', 'AAAAH!' );

	if ( in_array( $search, $maybe ) ) {
		include_once( JPATH_SITE . '/components/com_acctexp/lib/eucalib/eucalib.add.php' );

		return '<div class="usernote" style="width:200px; padding-top: 40px; padding-bottom: 40px; float: right;">'
				. ${'edition_0' . ( rand( 1, 4 ) )}['quote_' . str_pad( rand( 1, ( count( ${'edition_0' . ( rand( 1, 4 ) )} ) + 1 ) ), 2, '0' )]
				. '</div>';
	}

	if ( strpos( $search, 'logthis:' ) === 0 ) {
		$eventlog = new eventLog( $db );
		$eventlog->issue( 'debug', 'debug', 'debug entry: '.str_replace( 'logthis:', '', $search ), 128 );
	}

	return false;
}

function obsafe_print_r($var, $return = false, $html = false, $level = 0) {
    $spaces = "";
    $space = $html ? "&nbsp;" : " ";
    $newline = $html ? "<br />\n" : "\n";
    for ($i = 1; $i <= 6; $i++) {
        $spaces .= $space;
    }
    $tabs = $spaces;
    for ($i = 1; $i <= $level; $i++) {
        $tabs .= $spaces;
    }
    if (is_array($var)) {
        $title = "Array";
    } elseif (is_object($var)) {
        $title = get_class($var)." Object";
    }
    $output = $title . $newline . $newline;
    if ( !empty( $var ) ) {
	    foreach($var as $key => $value) {
	        if (is_array($value) || is_object($value)) {
	            $level++;
	            $value = obsafe_print_r($value, true, $html, $level);
	            $level--;
	        }
	        $output .= $tabs . "[" . $key . "] => " . $value . $newline;
	    }
    }
    if ($return) return $output;
      else echo $output;
}

function hackcorefile( $option, $filename, $check_hack, $undohack, $checkonly=false )
{
	$db = &JFactory::getDBO();

	$app = JFactory::getApplication();

	$aec_hack_start				= "// AEC HACK %s START" . "\n";
	$aec_hack_end				= "// AEC HACK %s END" . "\n";

	$aec_condition_start		= 'if (file_exists( JPATH_ROOT.DS."components".DS."com_acctexp".DS."acctexp.class.php" )) {' . "\n";

	$aec_condition_end			= '}' . "\n";

	$aec_include_class			= 'include_once(JPATH_SITE . "/components/com_acctexp/acctexp.class.php");' . "\n";

	$aec_verification_check		= "AECToolBox::VerifyUsername( %s );" . "\n";
	$aec_userchange_clause		= '$mih = new microIntegrationHandler();' . "\n" . '$mih->userchange($row, $_POST, \'%s\');' . "\n";
	$aec_userchange_clauseCB12	= '$mih = new microIntegrationHandler();' . "\n" . '$mih->userchange($userComplete, $_POST, \'%s\');' . "\n";
	$aec_userchange_clause15	= '$mih = new microIntegrationHandler();' . "\n" . '$mih->userchange($userid, $post, \'%s\');' . "\n";
	$aec_userregchange_clause15	= '$mih = new microIntegrationHandler();' . "\n" . '$mih->userchange($user, $post, \'%s\');' . "\n";

	$aec_global_call			= "\n";

	$aec_redirect_notallowed	= 'aecRedirect( $mosConfig_live_site . "/index.php?option=com_acctexp&task=NotAllowed" );' . "\n";
	$aec_redirect_notallowed15	= '$app = JFactory::getApplication();' . "\n" . '$app->redirect( "index.php?option=com_acctexp&task=NotAllowed" );' . "\n";

	$aec_redirect_subscribe		= 'aecRedirect( JURI::root() . \'index.php?option=com_acctexp&task=subscribe\' );' . "\n";

	$aec_normal_hack = $aec_hack_start
					. $aec_global_call
					. $aec_condition_start
					. $aec_redirect_notallowed
					. $aec_condition_end
					. $aec_hack_end;

	$aec_jhack1 = $aec_hack_start
					. 'function mosNotAuth($override=false) {' . "\n"
					. $aec_global_call
					. $aec_condition_start
					. 'if (!$override) {' . "\n"
					. $aec_redirect_notallowed
					. $aec_condition_end
					. $aec_condition_end
					. $aec_hack_end;

	$aec_jhack2 = $aec_hack_start
					. $aec_global_call
					. $aec_condition_start
					. $aec_redirect_notallowed
					. $aec_condition_end
					. $aec_hack_end;

	$aec_jhack3 = $aec_hack_start
					. $aec_global_call
					. $aec_condition_start
					. $aec_include_class
					. sprintf( $aec_verification_check, '$credentials[\'username\']' )
					. $aec_condition_end
					. $aec_hack_end;

	$aec_cbmhack =	$aec_hack_start
					. "mosNotAuth(true);" . "\n"
					. $aec_hack_end;

	$aec_uchangehack =	$aec_hack_start
						. $aec_global_call
						. $aec_condition_start
						. $aec_include_class
						. $aec_userchange_clause
						. $aec_condition_end
						. $aec_hack_end;

	$aec_uchangehackCB12 = str_replace( '$row', '$userComplete', $aec_uchangehack );
	$aec_uchangehackCB12x = str_replace( '$row', '$this', $aec_uchangehack );

	$aec_uchangehackCB12 =	$aec_hack_start
						. $aec_global_call
						. $aec_condition_start
						. $aec_include_class
						. $aec_userchange_clauseCB12
						. $aec_condition_end
						. $aec_hack_end;

	$aec_uchangehack15 =	$aec_hack_start
						. $aec_global_call
						. $aec_condition_start
						. $aec_include_class
						. $aec_userregchange_clause15
						. $aec_condition_end
						. $aec_hack_end;

	$aec_uchangereghack15 =	$aec_hack_start
						. $aec_global_call
						. $aec_condition_start
						. $aec_include_class
						. $aec_userchange_clause15
						. $aec_condition_end
						. $aec_hack_end;

	$aec_rhackbefore =	$aec_hack_start
						. $aec_global_call
						. $aec_condition_start
						. 'if (!isset($_POST[\'planid\'])) {' . "\n"
						. $aec_include_class
						. 'aecRedirect(JURI::root() . "index.php?option=com_acctexp&amp;task=subscribe");' . "\n"
						. $aec_condition_end
						. $aec_condition_end
						. $aec_hack_end;

	$aec_rhackbefore_fix = str_replace("planid", "usage", $aec_rhackbefore);

	$aec_rhackbefore2 =	$aec_hack_start
						. $aec_global_call . '$app = JFactory::getApplication();' . "\n"
						. $aec_condition_start
						. 'if (!isset($_POST[\'usage\'])) {' . "\n"
						. $aec_include_class
						. 'aecRedirect(JURI::root() . "index.php?option=com_acctexp&amp;task=subscribe");' . "\n"
						. $aec_condition_end
						. $aec_condition_end
						. $aec_hack_end;

	$aec_optionhack =	$aec_hack_start
						. $aec_global_call
						. $aec_condition_start
						. '$option = "com_acctexp";' . "\n"
						. $aec_condition_end
						. $aec_hack_end;

	$aec_regvarshack =	'<?php' . "\n"
						. $aec_hack_start
						. $aec_global_call
						. $aec_condition_start
						. '?>' . "\n"
						. '<input type="hidden" name="planid" value="<?php echo $_POST[\'planid\'];?>" />' . "\n"
						. '<input type="hidden" name="processor" value="<?php echo $_POST[\'processor\'];?>" />' . "\n"
						. '<?php' . "\n"
						. 'if ( isset( $_POST[\'recurring\'] ) ) {'
						. '?>' . "\n"
						. '<input type="hidden" name="recurring" value="<?php echo $_POST[\'recurring\'];?>" />' . "\n"
						. '<?php' . "\n"
						. '}' . "\n"
						. $aec_condition_end
						. $aec_hack_end
						. '?>' . "\n";

	$aec_regvarshack_fix = str_replace( 'planid', 'usage', $aec_regvarshack);

	$aec_regvarshack_fixcb = $aec_hack_start
						. $aec_global_call
						. $aec_condition_start
						. 'if ( isset( $_POST[\'usage\'] ) ) {' . "\n"
						. '$regFormTag .= \'<input type="hidden" name="usage" value="\' . $_POST[\'usage\'] . \'" />\';' . "\n"
						. '}' . "\n"
						. 'if ( isset( $_POST[\'processor\'] ) ) {' . "\n"
						. '$regFormTag .= \'<input type="hidden" name="processor" value="\' . $_POST[\'processor\'] . \'" />\';' . "\n"
						. '}' . "\n"
						. 'if ( isset( $_POST[\'recurring\'] ) ) {' . "\n"
						. '$regFormTag .= \'<input type="hidden" name="recurring" value="\' . $_POST[\'recurring\'] . \'" />\';' . "\n"
						. '}' . "\n"
						. $aec_condition_end
						. $aec_hack_end
						;

	$aec_regredirect = $aec_hack_start
					. $aec_global_call
					. $aec_condition_start
					. $aec_redirect_subscribe
					. $aec_condition_end
					. $aec_hack_end;

	$juser_blind = $aec_hack_start
					. 'case \'blind\':'. "\n"
					. 'break;'. "\n"
					. $aec_hack_end;

	$aec_j15hack1 =  $aec_hack_start
					. 'if ( $error->message == JText::_("ALERTNOTAUTH") ) {'
					. $aec_condition_start
					. $aec_redirect_notallowed15
					. $aec_condition_end
					. $aec_condition_end
					. $aec_hack_end;

	$n = 'errorphp';
	$hacks[$n]['name']			=	'error.php ' . JText::_('AEC_HACK_HACK') . ' #1';
	$hacks[$n]['desc']			=	JText::_('AEC_HACKS_NOTAUTH');
	$hacks[$n]['type']			=	'file';
	$hacks[$n]['filename']		=	JPATH_SITE . '/libraries/joomla/error/error.php';
	$hacks[$n]['read']			=	'// Initialize variables';
	$hacks[$n]['insert']		=	sprintf( $aec_j15hack1, $n, $n ) . "\n" . $hacks[$n]['read'];
	$hacks[$n]['legacy']		=	1;

	$n = 'joomlaphp4';
	$hacks[$n]['name']			=	'authentication.php';
	$hacks[$n]['desc']			=	JText::_('AEC_HACKS_LEGACY_PLUGIN');
	$hacks[$n]['uncondition']	=	'joomlaphp';
	$hacks[$n]['type']			=	'file';
	$hacks[$n]['filename']		=	JPATH_SITE . '/libraries/joomla/user/authentication.php';
	$hacks[$n]['read'] 			=	'if(empty($response->username)) {';
	$hacks[$n]['insert']		=	sprintf($aec_jhack3, $n, $n) . "\n" . $hacks[$n]['read'];
	$hacks[$n]['legacy']		=	1;

	if ( GeneralInfoRequester::detect_component( 'UHP2' ) ) {
		$n = 'uhp2menuentry';
		$hacks[$n]['name']			=	JText::_('AEC_HACKS_UHP2');
		$hacks[$n]['desc']			=	JText::_('AEC_HACKS_UHP2_DESC');
		$hacks[$n]['uncondition']	=	'uhp2managephp';
		$hacks[$n]['type']			=	'file';
		$hacks[$n]['filename']		=	JPATH_SITE . '/modules/mod_uhp2_manage.php';
		$hacks[$n]['read']			=	'<?php echo "$settings"; ?></a>';
		$hacks[$n]['insert']		=	sprintf( $hacks[$n]['read'] . "\n</li>\n<?php " . $aec_hack_start . '?>'
		. '<li class="latest<?php echo $moduleclass_sfx; ?>">'
		. '<a href="index.php?option=com_acctexp&task=subscriptionDetails" class="latest<?php echo $moduleclass_sfx; ?>">'
		. JText::_('AEC_SPEC_MENU_ENTRY') . '</a>'."\n<?php ".$aec_hack_end."?>", $n, $n );
	}

	if ( GeneralInfoRequester::detect_component( 'CB1.2' ) ) {
		$n = 'comprofilerphp2';
		$hacks[$n]['name']			=	'comprofiler.php ' . JText::_('AEC_HACK_HACK') . ' #2';
		$hacks[$n]['desc']			=	JText::_('AEC_HACKS_CB2');
		$hacks[$n]['type']			=	'file';
		$hacks[$n]['filename']		=	JPATH_SITE . '/components/com_comprofiler/comprofiler.php';
		$hacks[$n]['read']			=	'function registerForm( $option, $emailpass, $regErrorMSG = null ) {';
		$hacks[$n]['insert']		=	$hacks[$n]['read'] . "\n" . sprintf($aec_optionhack, $n, $n);
		$hacks[$n]['legacy']		=	1;

		$n = 'comprofilerphp6';
		$hacks[$n]['name']			=	'comprofiler.php ' . JText::_('AEC_HACK_HACK') . ' #6';
		$hacks[$n]['desc']			=	JText::_('AEC_HACKS_CB6');
		$hacks[$n]['type']			=	'file';
		$hacks[$n]['filename']		=	JPATH_SITE . '/components/com_comprofiler/comprofiler.php';
		$hacks[$n]['read']			=	'HTML_comprofiler::registerForm( $option, $emailpass, $userComplete, $regErrorMSG );';
		$hacks[$n]['insert']		=	sprintf($aec_rhackbefore_fix, $n, $n) . "\n" . $hacks[$n]['read'];
		$hacks[$n]['legacy']		=	1;

		$n = 'comprofilerhtml2';
		$hacks[$n]['name']			=	'comprofiler.html.php ' . JText::_('AEC_HACK_HACK') . ' #2';
		$hacks[$n]['desc']			=	JText::_('AEC_HACKS_CB_HTML2');
		$hacks[$n]['type']			=	'file';
		$hacks[$n]['filename']		=	JPATH_SITE . '/components/com_comprofiler/comprofiler.html.php';
		$hacks[$n]['read']			=	'echo HTML_comprofiler::_cbTemplateRender( $user, \'RegisterForm\'';
		$hacks[$n]['insert']		=	sprintf($aec_regvarshack_fixcb, $n, $n) . "\n" . $hacks[$n]['read'];
		$hacks[$n]['desc']			=	JText::_('AEC_HACKS_LEGACY');
		$hacks[$n]['legacy']		=	1;

	} elseif ( GeneralInfoRequester::detect_component( 'CB' ) ) {
		$n = 'comprofilerphp2';
		$hacks[$n]['name']			=	'comprofiler.php ' . JText::_('AEC_HACK_HACK') . ' #2';
		$hacks[$n]['desc']			=	JText::_('AEC_HACKS_CB2');
		$hacks[$n]['type']			=	'file';
		$hacks[$n]['filename']		=	JPATH_SITE . '/components/com_comprofiler/comprofiler.php';
		$hacks[$n]['read']			=	'if ($regErrorMSG===null) {';
		$hacks[$n]['insert']		=	sprintf($aec_optionhack, $n, $n) . "\n" . $hacks[$n]['read'];

		$n = 'comprofilerphp6';
		$hacks[$n]['name']			=	'comprofiler.php ' . JText::_('AEC_HACK_HACK') . ' #6';
		$hacks[$n]['desc']			=	JText::_('AEC_HACKS_CB6');
		$hacks[$n]['type']			=	'file';
		$hacks[$n]['condition']		=	'comprofilerphp2';
		$hacks[$n]['uncondition']	=	'comprofilerphp3';
		$hacks[$n]['filename']		=	JPATH_SITE . '/components/com_comprofiler/comprofiler.php';
		$hacks[$n]['read']			=	'HTML_comprofiler::registerForm';
		$hacks[$n]['insert']		=	sprintf($aec_rhackbefore_fix, $n, $n) . "\n" . $hacks[$n]['read'];

		$n = 'comprofilerhtml2';
		$hacks[$n]['name']			=	'comprofiler.html.php ' . JText::_('AEC_HACK_HACK') . ' #2';
		$hacks[$n]['desc']			=	JText::_('AEC_HACKS_CB_HTML2');
		$hacks[$n]['type']			=	'file';
		$hacks[$n]['uncondition']	=	'comprofilerhtml';
		$hacks[$n]['filename']		=	JPATH_SITE . '/components/com_comprofiler/comprofiler.html.php';
		$hacks[$n]['read']			=	'<input type="hidden" name="task" value="saveregisters" />';
		$hacks[$n]['insert']		=	$hacks[$n]['read'] . "\n" . sprintf($aec_regvarshack_fix, $n, $n);

	} elseif ( GeneralInfoRequester::detect_component( 'CBE' ) ) {
		$n = 'comprofilerphp2';
		$hacks[$n]['name']			=	'comprofiler.php ' . JText::_('AEC_HACK_HACK') . ' #2';
		$hacks[$n]['desc']			=	JText::_('AEC_HACKS_CB2');
		$hacks[$n]['type']			=	'file';
		$hacks[$n]['filename']		=	JPATH_SITE . '/components/com_comprofiler/comprofiler.php';
		$hacks[$n]['read']			=	'$rowFieldValues=array();';
		$hacks[$n]['insert']		=	sprintf($aec_optionhack, $n, $n) . "\n" . $hacks[$n]['read'];

		$n = 'comprofilerphp6';
		$hacks[$n]['name']			=	'comprofiler.php ' . JText::_('AEC_HACK_HACK') . ' #6';
		$hacks[$n]['desc']			=	JText::_('AEC_HACKS_CB6');
		$hacks[$n]['type']			=	'file';
		$hacks[$n]['condition']		=	'comprofilerphp2';
		$hacks[$n]['filename']		=	JPATH_SITE . '/components/com_comprofiler/comprofiler.php';
		$hacks[$n]['read']			=	'HTML_comprofiler::registerForm';
		$hacks[$n]['insert']		=	sprintf($aec_rhackbefore2, $n, $n) . "\n" . $hacks[$n]['read'];

		$n = 'comprofilerhtml2';
		$hacks[$n]['name']			=	'comprofiler.html.php ' . JText::_('AEC_HACK_HACK') . ' #2';
		$hacks[$n]['desc']			=	JText::_('AEC_HACKS_CB_HTML2');
		$hacks[$n]['type']			=	'file';
		$hacks[$n]['uncondition']	=	'comprofilerhtml';
		$hacks[$n]['filename']		=	JPATH_SITE . '/components/com_comprofiler/comprofiler.html.php';
		$hacks[$n]['read']			=	'<input type="hidden" name="task" value="saveRegistration" />';
		$hacks[$n]['insert']		=	$hacks[$n]['read'] . "\n" . sprintf($aec_regvarshack_fix, $n, $n);
	} elseif ( GeneralInfoRequester::detect_component( 'JUSER' ) ) {
		$n = 'juserhtml1';
		$hacks[$n]['name']			=	'juser.html.php ' . JText::_('AEC_HACK_HACK') . ' #1';
		$hacks[$n]['desc']			=	JText::_('AEC_HACKS_JUSER_HTML1');
		$hacks[$n]['type']			=	'file';
		$hacks[$n]['filename']		=	JPATH_SITE . '/components/com_juser/juser.html.php';
		$hacks[$n]['read']			=	'<input type="hidden" name="option" value="com_juser" />';
		$hacks[$n]['insert']		=	sprintf($aec_regvarshack_fix, $n, $n) . "\n" . '<input type="hidden" name="option" value="com_acctexp" />';

		$n = 'juserphp1';
		$hacks[$n]['name']			=	'juser.php ' . JText::_('AEC_HACK_HACK') . ' #1';
		$hacks[$n]['desc']			=	JText::_('AEC_HACKS_JUSER_PHP1');
		$hacks[$n]['type']			=	'file';
		$hacks[$n]['filename']		=	JPATH_SITE . '/components/com_juser/juser.php';
		$hacks[$n]['read']			=	'HTML_JUser::userEdit( $row, $option, $params, $ext_row, \'saveUserRegistration\' );';
		$hacks[$n]['insert']		=	sprintf($aec_rhackbefore_fix, $n, $n) . "\n" . $hacks[$n]['read'];

		$n = 'juserphp2';
		$hacks[$n]['name']			=	'juser.php ' . JText::_('AEC_HACK_HACK') . ' #2';
		$hacks[$n]['desc']			=	JText::_('AEC_HACKS_JUSER_PHP2');
		$hacks[$n]['type']			=	'file';
		$hacks[$n]['filename']		=	JPATH_SITE . '/components/com_juser/juser.php';
		$hacks[$n]['read']			=	'default:';
		$hacks[$n]['insert']		=	sprintf($juser_blind, $n, $n) . "\n" . $hacks[$n]['read'];
	} else {

		$n = 'registrationhtml2';
		$hacks[$n]['name']			=	'registration.html.php ' . JText::_('AEC_HACK_HACK') . ' #2';
		$hacks[$n]['desc']			=	JText::_('AEC_HACKS_LEGACY');
		$hacks[$n]['type']			=	'file';
		$hacks[$n]['uncondition']	=	'registrationhtml';
		$hacks[$n]['condition']		=	'registrationphp2';
		$hacks[$n]['filename']		=	JPATH_SITE . '/components/com_user/views/register/tmpl/default.php';
		$hacks[$n]['read']			=	'<input type="hidden" name="task" value="register_save" />';
		$hacks[$n]['insert']		=	$hacks[$n]['read'] . "\n" . sprintf($aec_regvarshack_fix, $n, $n);
		$hacks[$n]['legacy']		=	1;

		$n = 'registrationphp6';
		$hacks[$n]['name']			=	'user.php';
		$hacks[$n]['desc']			=	JText::_('AEC_HACKS_REG5');
		$hacks[$n]['type']			=	'file';
		$hacks[$n]['uncondition']	=	'registrationphp5';
		$hacks[$n]['filename']		=	JPATH_SITE . '/components/com_user/controller.php';
		$hacks[$n]['read']			=	'JRequest::setVar(\'view\', \'register\');';
		$hacks[$n]['insert']		=	$hacks[$n]['read'] . "\n" . sprintf($aec_regredirect, $n, $n);
		$hacks[$n]['legacy']		=	1;
	}

	if ( GeneralInfoRequester::detect_component( 'anyCB' ) ) {
		if ( GeneralInfoRequester::detect_component( 'CB1.2' ) ) {
			$n = 'comprofilerphp7';
			$hacks[$n]['name']			=	'comprofiler.php ' . JText::_('AEC_HACK_HACK') . ' #7';
			$hacks[$n]['desc']			=	JText::_('AEC_HACKS_MI1');
			$hacks[$n]['type']			=	'file';
			$hacks[$n]['filename']		=	JPATH_SITE . '/components/com_comprofiler/comprofiler.php';
			$hacks[$n]['read']			=	'$_PLUGINS->trigger( \'onAfterUserRegistrationMailsSent\',';
			$hacks[$n]['insert']		=	sprintf( $aec_uchangehackCB12, $n, 'registration', $n ) . "\n" . $hacks[$n]['read'];
			$hacks[$n]['legacy']		=	1;

			$n = 'comprofilerphp8';
			$hacks[$n]['name']			=	'comprofiler.php ' . JText::_('AEC_HACK_HACK') . ' #8';
			$hacks[$n]['desc']			=	JText::_('AEC_HACKS_MI1');
			$hacks[$n]['type']			=	'file';
			$hacks[$n]['filename']		=	JPATH_SITE . '/administrator/components/com_comprofiler/library/cb/cb.tables.php';
			$hacks[$n]['read']			=	'$_PLUGINS->trigger( \'onAfterUserUpdate\', array( &$this, &$this, true ) );';
			$hacks[$n]['insert']		=	$hacks[$n]['read'] . "\n" . sprintf( $aec_uchangehackCB12x, $n, 'user', $n );
			$hacks[$n]['legacy']		=	1;
		} else {
			$n = 'comprofilerphp4';
			$hacks[$n]['name']			=	'comprofiler.php ' . JText::_('AEC_HACK_HACK') . ' #4';
			$hacks[$n]['desc']			=	JText::_('AEC_HACKS_MI1');
			$hacks[$n]['type']			=	'file';
			$hacks[$n]['filename']		=	JPATH_SITE . "/components/com_comprofiler/comprofiler.php";
			$hacks[$n]['read']			=	'$_PLUGINS->trigger( \'onAfterUserRegistrationMailsSent\',';
			$hacks[$n]['insert']		=	sprintf($aec_uchangehack, $n, "user", $n) . "\n" . $hacks[$n]['read'];
			$hacks[$n]['legacy']		=	1;

			$n = 'comprofilerphp5';
			$hacks[$n]['name']			=	'comprofiler.php ' . JText::_('AEC_HACK_HACK') . ' #5';
			$hacks[$n]['desc']			=	JText::_('AEC_HACKS_MI2');
			$hacks[$n]['type']			=	'file';
			$hacks[$n]['filename']		=	JPATH_SITE . "/components/com_comprofiler/comprofiler.php";
			$hacks[$n]['read']			=	'$_PLUGINS->trigger( \'onAfterUserUpdate\', array($row, $rowExtras, true));';
			$hacks[$n]['insert']		=	$hacks[$n]['read'] . "\n" . sprintf($aec_uchangehack, $n, "registration",$n);
			$hacks[$n]['legacy']		=	1;

			$n = 'comprofilerphp7';
			$hacks[$n]['name']			=	'comprofiler.php ' . JText::_('AEC_HACK_HACK') . ' #7';
			$hacks[$n]['desc']			=	JText::_('AEC_HACKS_MI1');
			$hacks[$n]['type']			=	'file';
			$hacks[$n]['uncondition']	=	'comprofilerphp4';
			$hacks[$n]['filename']		=	JPATH_SITE . '/components/com_comprofiler/comprofiler.php';
			$hacks[$n]['read']			=	'$_PLUGINS->trigger( \'onAfterUserRegistrationMailsSent\',';
			$hacks[$n]['insert']		=	sprintf( $aec_uchangehack, $n, 'registration', $n ) . "\n" . $hacks[$n]['read'];

			$n = 'comprofilerphp8';
			$hacks[$n]['name']			=	'comprofiler.php ' . JText::_('AEC_HACK_HACK') . ' #8';
			$hacks[$n]['desc']			=	JText::_('AEC_HACKS_MI1');
			$hacks[$n]['type']			=	'file';
			$hacks[$n]['uncondition']	=	'comprofilerphp5';
			$hacks[$n]['filename']		=	JPATH_SITE . '/components/com_comprofiler/comprofiler.php';
			$hacks[$n]['read']			=	'$_PLUGINS->trigger( \'onAfterUserUpdate\', array($row, $rowExtras, true));';
			$hacks[$n]['insert']		=	$hacks[$n]['read'] . "\n" . sprintf( $aec_uchangehack, $n, 'user', $n );
		}
	} else {
		$n = 'userphp';
		$hacks[$n]['name']			=	'user.php';
		$hacks[$n]['desc']			=	JText::_('AEC_HACKS_LEGACY');
		$hacks[$n]['type']			=	'file';
		$hacks[$n]['filename']		=	JPATH_SITE . '/components/com_user/controller.php';
		$hacks[$n]['read']			=	'if ($model->store($post)) {';
		$hacks[$n]['insert']		=	sprintf( $aec_uchangehack15, $n, "user", $n ) . "\n" . $hacks[$n]['read'];
		$hacks[$n]['legacy']		=	1;

		$n = 'registrationphp1';
		$hacks[$n]['name']			=	'registration.php ' . JText::_('AEC_HACK_HACK') . ' #1';
		$hacks[$n]['desc']			=	JText::_('AEC_HACKS_LEGACY');
		$hacks[$n]['type']			=	'file';
		$hacks[$n]['filename']		=	JPATH_SITE . '/components/com_user/controller.php';
		$hacks[$n]['read']			=	'UserController::_sendMail($user, $password);';
		$hacks[$n]['insert']		=	$hacks[$n]['read'] . "\n" . sprintf( $aec_uchangereghack15, $n, "registration", $n );
		$hacks[$n]['legacy']		=	1;
	}

	$n = 'adminuserphp';
	$hacks[$n]['name']			=	'admin.user.php';
	$hacks[$n]['desc']			=	JText::_('AEC_HACKS_LEGACY');
	$hacks[$n]['type']			=	'file';
	$hacks[$n]['filename']		=	JPATH_SITE . '/administrator/components/com_users/controller.php';
	$hacks[$n]['read']			=	'if (!$user->save())';
	$hacks[$n]['insert']		=	sprintf( $aec_uchangehack15, $n, 'adminuser', $n ) . "\n" . $hacks[$n]['read'];
	$hacks[$n]['legacy']	=	1;

	if ( GeneralInfoRequester::detect_component( 'CBM' ) ) {
		if ( !GeneralInfoRequester::detect_component( 'CB1.2' ) ) {
			$n = 'comprofilermoderator';
			$hacks[$n]['name']			=	'comprofilermoderator.php';
			$hacks[$n]['desc']			=	JText::_('AEC_HACKS_CBM');
			$hacks[$n]['type']			=	'file';
			$hacks[$n]['filename']		=	JPATH_SITE . '/modules/mod_comprofilermoderator.php';
			$hacks[$n]['read']			=	'mosNotAuth();';
			$hacks[$n]['insert']		=	sprintf( $aec_cbmhack, $n, $n );
		}
	}

	$mih = new microIntegrationHandler();
	$new_hacks = $mih->getHacks();

	if ( is_array( $new_hacks ) ) {
		$hacks = array_merge( $hacks, $new_hacks );
	}

	// Receive the status for the hacks
	foreach ( $hacks as $name => $hack ) {

		$hacks[$name]['status'] = 0;

		if ( !empty( $hack['filename'] ) ) {
			if ( !file_exists( $hack['filename'] ) ) {
				continue;
			}
		}

		if ( $hack['type'] ) {
			switch( $hack['type'] ) {
				case 'file':
					if ( $hack['filename'] != 'UNKNOWN' ) {
						$originalFileHandle = fopen( $hack['filename'], 'r' );
						$oldData			= fread( $originalFileHandle, filesize($hack['filename'] ) );
						fclose( $originalFileHandle );

						if ( strpos( $oldData, 'AEC HACK START' ) || strpos( $oldData, 'AEC CHANGE START' )) {
							$hacks[$name]['status'] = 'legacy';
						} else {
							if ( ( strpos( $oldData, 'AEC HACK ' . $name . ' START' ) > 0 ) || ( strpos( $oldData, 'AEC CHANGE ' . $name . ' START' ) > 0 )) {
								$hacks[$name]['status'] = 1;
							}
						}

						if ( function_exists( 'posix_getpwuid' ) ) {
							$hacks[$name]['fileinfo'] = posix_getpwuid( fileowner( $hack['filename'] ) );
						}
					}
					break;

				case 'menuentry':
					$count = 0;
					$query = 'SELECT COUNT(*)'
							. ' FROM #__menu'
							. ' WHERE `link` = \'' . JURI::root()  . '/index.php?option=com_acctexp&task=subscriptionDetails\''
							;
					$db->setQuery( $query );
					$count = $db->loadResult();

					if ( $count ) {
						$hacks[$name]['status'] = 1;
					}
					break;
			}
		}
	}

	if ( $checkonly ) {
		return $hacks[$filename]['status'];
	}

	// Commit the hacks
	if ( !$check_hack ) {

		switch( $hacks[$filename]['type'] ) {
			case 'file':
				// mic: fix if CMS is not Joomla or Mambo
				if ( $hack['filename'] != 'UNKNOWN' ) {
					$originalFileHandle = fopen( $hacks[$filename]['filename'], 'r' ) or die ("Cannot open $originalFile<br>");
					// Transfer File into variable $oldData
					$oldData = fread( $originalFileHandle, filesize( $hacks[$filename]['filename'] ) );
					fclose( $originalFileHandle );

					if ( !$undohack ) { // hack
						$newData			= str_replace( $hacks[$filename]['read'], $hacks[$filename]['insert'], $oldData );

							//make a backup
							if ( !backupFile( $hacks[$filename]['filename'], $hacks[$filename]['filename'] . '.aec-backup' ) ) {
							// Echo error message
							}

					} else { // undo hack
						if ( strcmp( $hacks[$filename]['status'], 'legacy' ) === 0 ) {
							$newData = preg_replace( '/\/\/.AEC.(HACK|CHANGE).START\\n.*\/\/.AEC.(HACK|CHANGE).END\\n/s', $hacks[$filename]['read'], $oldData );
						} else {
							if ( strpos( $oldData, $hacks[$filename]['insert'] ) ) {
								if ( isset( $hacks[$filename]['oldread'] ) && isset( $hacks[$filename]['oldinsert'] ) ) {
									$newData = str_replace( $hacks[$filename]['oldinsert'], $hacks[$filename]['oldread'], $oldData );
								}

								$newData = str_replace( $hacks[$filename]['insert'], $hacks[$filename]['read'], $oldData );
							} else {
								$newData = preg_replace( '/\/\/.AEC.(HACK|CHANGE).' . $filename . '.START\\n.*\/\/.AEC.(HACK|CHANGE).' . $filename . '.END\\n/s', $hacks[$filename]['read'], $oldData );
							}
						}
					}

						$oldperms = fileperms( $hacks[$filename]['filename'] );
						chmod( $hacks[$filename]['filename'], $oldperms | 0222 );

						if ( $fp = fopen( $hacks[$filename]['filename'], 'wb' ) ) {
								fwrite( $fp, $newData, strlen( $newData ) );
								fclose( $fp );
								chmod( $hacks[$filename]['filename'], $oldperms );
						}
				}
				break;
		}
	}

	return $hacks;
}

function backupFile( $file, $file_new )
{
		if ( !copy( $file, $file_new ) ) {
				return false;
		}
		return true;
}

function readout( $option )
{
	$db = &JFactory::getDBO();

	$optionlist = array(
							'show_settings' => 0,
							'show_extsettings' => 0,
							'show_processors' => 0,
							'show_plans' => 1,
							'show_mi_relations' => 1,
							'show_mis' => 1,
							'truncation_length' => 42,
							'noformat_newlines' => 0,
							'use_ordering' => 0,
							'column_headers' => 20,
							'export_csv' => 0,
							'store_settings' => 1
						);

	if ( isset( $_POST['display'] ) ) {
		if ( !empty( $_POST['export_csv'] ) ) {
			$method = "csv";
		} else {
			$method = "html";
		}

		$r = array();
		$readout = new aecReadout( $optionlist, $method );

		foreach ( $optionlist as $opt => $odefault ) {
			if ( !isset( $_POST[$opt] ) ) {
				continue;
			}

			switch ( $opt ) {
				case 'show_settings':
					$s = $readout->readSettings();
					break;
				case 'show_processors':
					$s = $readout->readProcessors();
					break;
				case 'show_plans':
					$s = $readout->readPlans();
					break;
				case 'show_mi_relations':
					$s = $readout->readPlanMIrel();
					break;
				case 'show_mis':
					$s = $readout->readMIs();
					break;
				case 'store_settings':
					$user = &JFactory::getUser();

					$settings = array();
					foreach ( $optionlist as $opt => $optdefault ) {
						if ( !empty( $_POST[$opt] ) ) {
							$settings[$opt] = $_POST[$opt];
						} else {
							$settings[$opt] = 0;
						}
					}

					$metaUser = new metaUser( $user->id );
					$metaUser->meta->addCustomParams( array( 'aecadmin_readout' => $settings ) );
					$metaUser->meta->storeload();
					continue 2;
					break;
				default:
					continue 2;
					break;
			}

			if ( isset( $s['def'] ) ) {
				$r[] = $s;
			} elseif ( is_array( $s ) ) {
				foreach ( $s as $i => $x ) {
					$r[] = $x;
				}
			}
		}

		if ( !empty( $_POST['export_csv'] ) ) {
			HTML_AcctExp::readoutCSV( $option, $r );
		} else {
			HTML_AcctExp::readout( $option, $r );
		}
	} else {
		$user = &JFactory::getUser();

		$metaUser = new metaUser( $user->id );
		if ( isset( $metaUser->meta->custom_params['aecadmin_readout'] ) ) {
			$prefs = $metaUser->meta->custom_params['aecadmin_readout'];
		} else {
			$prefs = array();
		}

		foreach ( $optionlist as $opt => $optdefault ) {
			if ( isset( $prefs[$opt] ) ) {
				$optval = $prefs[$opt];
			} else {
				$optval = $optdefault;
			}

			if ( ( $optdefault == 1 ) || ( $optdefault == 0 ) ) {
				$params[$opt] = array( 'checkbox', $optval );
			} else {
				$params[$opt] = array( 'inputB', $optval );
			}
		}

		$settings = new aecSettings ( 'readout', 'general' );

		$settings->fullSettingsArray( $params, $prefs, array() ) ;

		// Call HTML Class
		$aecHTML = new aecHTML( $settings->settings, $settings->lists );

		HTML_AcctExp::readoutSetup( $option, $aecHTML );
	}
}

function importData( $option )
{
	$show_form = false;
	$done = false;

	$temp_dir = JPATH_SITE . '/tmp';

	$file_list = AECToolbox::getFileArray( $temp_dir, 'csv', false, true );

	$params = array();
	$lists = array();

	if ( !empty( $_FILES ) ) {
		if ( strpos( $_FILES['import_file']['name'], '.csv' ) === false ) {
			$len = strlen( $_FILES['import_file']['name'] );

			$last = strrpos( $_FILES['import_file']['name'], '.' );

			$filename = substr( $_FILES['import_file']['name'], 0, $last ) . '.csv';
		} else {
			$filename = $_FILES['import_file']['name'];
		}

		$destination = $temp_dir . '/' . $filename;

		if ( move_uploaded_file( $_FILES['import_file']['tmp_name'], $destination ) ) {
			$file_select = $filename;
		}
	} else {

	}

	if ( empty( $file_select ) ) {
		$file_select = aecGetParam( 'file_select', '' );
	}

	if ( empty( $file_select ) ) {
		$show_form = true;

		$params['file_select']			= array( 'list', '' );
		$params['MAX_FILE_SIZE']		= array( 'hidden', '5120000' );
		$params['import_file']			= array( 'file', 'Upload', 'Upload a file and select it for importing', '' );

		$file_htmllist		= array();
		$file_htmllist[]	= JHTML::_('select.option', '', JText::_('AEC_CMN_NONE_SELECTED') );

		if ( !empty( $file_list ) ) {
			foreach ( $file_list as $name ) {
				$file_htmllist[] = JHTML::_('select.option', $name, $name );
			}
		}

		$lists['file_select'] = JHTML::_('select.genericlist', $file_htmllist, 'file_select', 'size="' . min( ( count( $file_htmllist ) + 1 ), 25 ) . '"', 'value', 'text', 0 );
	} else {
		$options = array();

		if ( !empty( $_POST['assign_plan'] ) ) {
			$options['assign_plan'] = $_POST['assign_plan'];
		}

		$import = new aecImport( $temp_dir . '/' . $file_select, $options );

		if ( !$import->read() ) {
			die( 'could not read file' );
		}

		$import->parse();

		if ( !empty( $import->rows ) ) {
			$params['file_select']		= array( 'hidden', $file_select );

			if ( !isset( $_POST['convert_field_0'] ) ) {
				$fields = array(	"id" => "User ID",
									"name" => "User Full Name",
									"username" => "Username",
									"email" => "User Email",
									"password" => "Password",
									"plan_id" => "Payment Plan ID",
									"invoice_number" => "Invoice Number",
									"expiration" => "Membership Expiration"
								);

				$field_htmllist		= array();
				$field_htmllist[]	= JHTML::_('select.option', 0, 'Ignore' );

				foreach ( $fields as $name => $longname ) {
					$field_htmllist[] = JHTML::_('select.option', $name, $longname );
				}

				$cols = count( $import->rows[0] );

				$columns = array();
				for ( $i=0; $i<$cols; $i++ ) {
					$columns[] = 'convert_field_'.$i;

					$params['convert_field_'.$i] = array( 'list', '', '', '' );

					$lists['convert_field_'.$i] = JHTML::_('select.genericlist', $field_htmllist, 'convert_field_'.$i, 'size="1"', 'value', 'text', 0 );
				}

				$rows_count = count( $import->rows );

				$rowcount = min( $rows_count, 5 );

				$rows = array();
				for ( $i=0; $i<$rowcount; $i++ ) {
					$rows[] = $import->rows[$i];
				}

				$params['assign_plan'] = array( 'list', 'Assign Plan', 'Assign users to a specific payment plan. Is overridden if you provide an individual plan ID with the "Payment Plan ID" field assignment.' );

				$available_plans	= SubscriptionPlanHandler::getActivePlanList();

				$lists['assign_plan'] = JHTML::_('select.genericlist', $available_plans, 'assign_plan', 'size="5"', 'value', 'text', 0 );
			} else {
				$import->getConversionList();

				$import->import();

				$done = true;
			}
		} else {
			die( 'could not find any entries in this file' );
		}
	}

	$settingsparams = array();

	$settings = new aecSettings ( 'import', 'general' );
	$settings->fullSettingsArray( $params, $settingsparams, $lists ) ;

	// Call HTML Class
	$aecHTML = new aecHTML( $settings->settings, $settings->lists );

	$aecHTML->form = $show_form;
	$aecHTML->done = $done;

	if ( !$show_form ) {
		$aecHTML->user_rows = $rows;
		$aecHTML->user_rows_count = $rows_count;
		$aecHTML->columns = $columns;
	}

	HTML_AcctExp::import( $option, $aecHTML );
}

function exportData( $option, $cmd=null )
{
	$db = &JFactory::getDBO();

	$cmd_save = ( strcmp( 'save', $cmd ) === 0 );
	$cmd_apply = ( strcmp( 'apply', $cmd ) === 0 );
	$cmd_load = ( strcmp( 'load', $cmd ) === 0 );
	$cmd_export = ( strcmp( 'export', $cmd ) === 0 );
	$use_original = 0;

	$system_values = array();
	$filter_values = array();
	$options_values = array();
	$params_values = array();

	$getpost = array(	'system' => array( 'selected_export', 'delete', 'save', 'save_name' ),
						'filter' => array( 'planid', 'status', 'orderby' ),
						'options' => array( 'rewrite_rule' ),
						'params' => array( 'export_method' )
					);

	$postfields = 0;
	foreach( $getpost as $name => $array ) {
		$field = $name . '_values';
		$$field = new stdClass();
		foreach( $array as $vname ) {
			 $$field->$vname = aecGetParam( $vname, '' );
			 if ( !( $$field->$vname == '' ) ) {
			 	$postfields++;
			 }
		}
	}

	$lists = array();

	if ( !empty( $system_values->selected_export ) || $cmd_save || $cmd_apply ) {
		$row = new aecExport( $db );
		if ( isset( $system_values->selected_export ) ) {
			$row->load( $system_values->selected_export );
		} else {
			$row->load(0);
		}

		if ( !empty( $system_values->delete ) ) {
			// User wants to delete the entry
			$row->delete();
		} elseif ( ( $cmd_save || $cmd_apply ) && ( !empty( $system_values->selected_export ) || !empty( $system_values->save_name ) ) ) {
			// User wants to save an entry
			if ( $system_values->save == 'on' ) {
				// But as a copy of another entry
				$row->load( 0 );
			}
			$row->save( $system_values->save_name, $filter_values, $options_values, $params_values );

			if ( $system_values->save == 'on' ) {
				$system_values->selected_export = $row->getMax();
			}
		} elseif ( ( $cmd_save || $cmd_apply ) && ( empty( $system_values->selected_export ) && !empty( $system_values->save_name ) && ( $system_values->save == 'on' ) ) ) {
			// User wants to save a new entry
			$row->save( $system_values->save_name, $filter_values, $options_values, $params_values );
		}  elseif ( $cmd_load || ( ( $postfields <= 5 ) && $cmd_export )  ) {
			// User wants to load an entry
			$filter_values = $row->filter;
			$options_values = $row->options;
			$params_values = $row->params;
			$pname = $row->name;
			$use_original = 1;
		}
	}

	if ( !isset( $pname ) ) {
		$pname = $system_values->save_name;
	}

	// Always store the last ten calls, but only if something is happening
	if ( $cmd_save || $cmd_apply || $cmd_export ) {
		$autorow = new aecExport( $db );
		$autorow->load(0);
		$autorow->save( 'Autosave', $filter_values, $options_values, $params_values, true );

		if ( isset( $row ) ) {
			if ( ( $autorow->filter == $row->filter ) && ( $autorow->options == $row->options ) && ( $autorow->params == $row->params ) ) {
				$use_original = 1;
			}
		}
	}

	// Create Parameters

	$params[] = array( 'userinfobox', 100 );
	$params['selected_export']	= array( 'list', '' );
	$params['delete']			= array( 'checkbox', 0 );
	$params[] = array( '2div_end', '' );

	$params[] = array( 'userinfobox', 20 );
	$params['params_remap']	= array( 'subarea_change', 'filter' );
	$params['planid']			= array( 'list', '' );
	$params['status']			= array( 'list', '' );
	$params['orderby']			= array( 'list', '' );
	$params[] = array( '2div_end', '' );

	$params[] = array( 'userinfobox', 50 );
	$params['params_remap']	= array( 'subarea_change', 'options' );
	$params['rewrite_rule']	= array( 'inputD', '' );
	$rewriteswitches			= array( 'cms', 'user', 'subscription', 'plan' );
	$params = AECToolbox::rewriteEngineInfo( $rewriteswitches, $params );
	$params[] = array( '2div_end', '' );

	$params[] = array( 'userinfobox', 20 );
	$params['params_remap']	= array( 'subarea_change', 'params' );
	$params['save']			= array( 'checkbox', 0 );
	$params['save_name']		= array( 'inputB', $pname );
	$params['export_method']	= array( 'list', '' );
	$params[] = array( '2div_end', '' );

	// Create a list of export options
	// First, only the non-autosaved entries
	$query = 'SELECT `id`, `name`, `created_date`, `lastused_date`'
			. ' FROM #__acctexp_export'
			. ' WHERE `system` = \''
			;
	$db->setQuery( $query . '0\'' );
	$user_exports = $db->loadObjectList();

	// Then the autosaved entries
	$db->setQuery( $query . '1\'' );
	$system_exports = $db->loadObjectList();

	$entries = count( $user_exports ) + count( $system_exports );

	if ( $entries > 0 ) {
		$listitems = array();

		$user = false;
		for ( $i=0; $i < $entries; $i++ ) {
			if ( ( $i >= count( $user_exports ) ) && ( $user === false ) ) {
				$user = $i;
			}

			if ( $user === false ) {
				$listitems[] = JHTML::_('select.option', $user_exports[$i]->id, substr( $user_exports[$i]->name, 0, 64 ) . ' - ' . 'last used: ' . $user_exports[$i]->lastused_date . ', created: ' . $user_exports[$i]->created_date );
			} else {
				$ix = $i - $user;
				$listitems[] = JHTML::_('select.option', $system_exports[$ix]->id, substr( $system_exports[$ix]->name, 0, 64 ) . ' - ' . 'last used: ' . $system_exports[$ix]->lastused_date . ', created: ' . $system_exports[$ix]->created_date );
			}
		}
	} else {
		$listitems[] = JHTML::_('select.option', 0, " --- No saved Preset available --- " );
	}

	$lists['selected_export'] = JHTML::_('select.genericlist', $listitems, 'selected_export', 'size="' . max( 10, min( 20, $entries ) ) . '"', 'value', 'text', arrayValueDefault($system_values, 'selected_export', '') );

	// Get list of plans for filter
	$query = 'SELECT `id`, `name`'
			. ' FROM #__acctexp_plans'
			. ' ORDER BY `ordering`'
			;
	$db->setQuery( $query );
	$db_plans = $db->loadObjectList();

	$selected_plans = array();
	$plans = array();
	foreach ( $db_plans as $dbplan ) {
		$plans[] = JHTML::_('select.option', $dbplan->id, $dbplan->name );

		if ( !empty( $filter_values->planid ) ) {
			if ( in_array( $dbplan->id, $filter_values->planid ) ) {
				$selected_plans[] = JHTML::_('select.option', $dbplan->id, $dbplan->name );
			}
		}
	}

	$lists['planid']	= JHTML::_('select.genericlist', $plans, 'planid[]', 'class="inputbox" size="' . min( 20, count( $plans ) ) . '" multiple="multiple"', 'value', 'text', $selected_plans );

	// Statusfilter
	$group_selection = array();
	$group_selection[] = JHTML::_('select.option', 'excluded',	JText::_('AEC_SEL_EXCLUDED') );
	$group_selection[] = JHTML::_('select.option', 'pending',	JText::_('AEC_SEL_PENDING') );
	$group_selection[] = JHTML::_('select.option', 'trial',		JText::_('AEC_SEL_TRIAL') );
	$group_selection[] = JHTML::_('select.option', 'active',	JText::_('AEC_SEL_ACTIVE') );
	$group_selection[] = JHTML::_('select.option', 'expired',	JText::_('AEC_SEL_EXPIRED') );
	$group_selection[] = JHTML::_('select.option', 'closed',	JText::_('AEC_SEL_CLOSED') );
	$group_selection[] = JHTML::_('select.option', 'cancelled',	JText::_('AEC_SEL_CANCELLED') );
	$group_selection[] = JHTML::_('select.option', 'manual',	JText::_('AEC_SEL_NOT_CONFIGURED') );

	$selected_status = array();
	if ( !empty( $filter_values->status ) ) {
		foreach ( $filter_values->status as $name ) {
			$selected_status[] = JHTML::_('select.option', $name, $name );
		}
	}

	$lists['status'] = JHTML::_('select.genericlist', $group_selection, 'status[]', 'size="6" multiple="multiple"', 'value', 'text', $selected_status);

	// Ordering
	$sel = array();
	$sel[] = JHTML::_('select.option', 'expiration ASC',	JText::_('EXP_ASC') );
	$sel[] = JHTML::_('select.option', 'expiration DESC',	JText::_('EXP_DESC') );
	$sel[] = JHTML::_('select.option', 'name ASC',			JText::_('NAME_ASC') );
	$sel[] = JHTML::_('select.option', 'name DESC',			JText::_('NAME_DESC') );
	$sel[] = JHTML::_('select.option', 'username ASC',		JText::_('LOGIN_ASC') );
	$sel[] = JHTML::_('select.option', 'username DESC',		JText::_('LOGIN_DESC') );
	$sel[] = JHTML::_('select.option', 'signup_date ASC',	JText::_('SIGNUP_ASC') );
	$sel[] = JHTML::_('select.option', 'signup_date DESC',	JText::_('SIGNUP_DESC') );
	$sel[] = JHTML::_('select.option', 'lastpay_date ASC',	JText::_('LASTPAY_ASC') );
	$sel[] = JHTML::_('select.option', 'lastpay_date DESC',	JText::_('LASTPAY_DESC') );
	$sel[] = JHTML::_('select.option', 'plan_name ASC',		JText::_('PLAN_ASC') );
	$sel[] = JHTML::_('select.option', 'plan_name DESC',	JText::_('PLAN_DESC') );
	$sel[] = JHTML::_('select.option', 'status ASC',		JText::_('STATUS_ASC') );
	$sel[] = JHTML::_('select.option', 'status DESC',		JText::_('STATUS_DESC') );
	$sel[] = JHTML::_('select.option', 'type ASC',			JText::_('TYPE_ASC') );
	$sel[] = JHTML::_('select.option', 'type DESC',			JText::_('TYPE_DESC') );

	$lists['orderby'] = JHTML::_('select.genericlist', $sel, 'orderby', 'class="inputbox" size="10"', 'value', 'text', arrayValueDefault($filter_values, 'orderby', '') );

	// Export Method
	$sel = array();
	$sel[] = JHTML::_('select.option', 'csv', 'csv' );

	$lists['export_method'] = JHTML::_('select.genericlist', $sel, 'export_method', 'class="inputbox" size="4"', 'value', 'text', 'csv' );

	$settings = new aecSettings ( 'export', 'general' );

	// Repackage the objects as array
	foreach( $getpost as $name => $array ) {
		$field = $name . '_values';
		foreach( $array as $vname ) {
			if ( !empty( $$field->$name ) ) {
				$settingsparams[$name] = $$field->$name;
			} else {
				$settingsparams[$name] = "";
			}
		}
	}

	$settingsparams = array_merge( get_object_vars( $filter_values ), get_object_vars( $options_values ), get_object_vars( $params_values ) );

	$settings->fullSettingsArray( $params, $settingsparams, $lists ) ;

	// Call HTML Class
	$aecHTML = new aecHTML( $settings->settings, $settings->lists );

	if ( $cmd_export && !empty( $params_values->export_method ) ) {
		if ( $use_original ) {
			$row->useExport();
		} else {
			$autorow->useExport();
		}
	}

	if ( $cmd_save ) {
		aecRedirect( 'index.php?option=' . $option . '&task=showCentral' );
	} else {
		HTML_AcctExp::export( $option, $aecHTML );
	}
}

function toolBoxTool( $option, $cmd )
{
	$path = JPATH_SITE . '/components/com_acctexp/toolbox';

	if ( empty( $cmd ) ) {
		$list = array();

		$files = AECToolbox::getFileArray( $path, 'php', false, true );

		foreach ( $files as $n => $name ) {
			$file = $path . '/' . $name;

			include_once $file;

			$class = str_replace( '.php', '', $name );

			$tool = new $class();

			if ( !method_exists( $tool, 'Info' ) ) {
				continue;
			}

			$info = $tool->Info();

			$info['link'] = AECToolbox::deadsureURL( 'administrator/index.php?option=' . $option . '&task=toolbox&cmd=' . $class );

			$list[] = $info;
		}

		HTML_AcctExp::toolBox( $option, '', $list );
	} else {
		$file = $path . '/' . $cmd . '.php';

		include_once $file;

		$tool = new $cmd();

		$info = $tool->Info();

		$return = '';
		if ( !method_exists( $tool, 'Action' ) ) {
			$return .= '<div id="aec-toolbox-result">' . '<p>Tool doesn\'t have an action to carry out!</p>' . '</div>';
		} else {
			if ( method_exists( $tool, 'Settings' ) ) {
				$tb_settings = $tool->Settings();

				if ( !empty( $tb_settings ) ) {
					$return .= '<div id="aec-toolbox-form">';

					$lists = array();
					if ( isset( $tb_settings['lists'] ) ) {
						$lists = $tb_settings['lists'];

						unset( $tb_settings['lists'] );
					}

					// Get preset values from POST
					foreach ( $tb_settings as $n => $v ) {
						if ( isset( $_POST[$n] ) ) {
							$tb_settings[$n][3] = $_POST[$n];
						}
					}

					$settings = new aecSettings( 'TOOLBOX', 'E' );
					$settings->fullSettingsArray( $tb_settings, array(), $lists );

					// Call HTML Class
					$aecHTML = new aecHTML( $settings->settings, $settings->lists );

					foreach ( $tb_settings as $n => $v ) {
						$return .= $aecHTML->createSettingsParticle( $n );
					}

					$return .= '<input type="submit" />';
					$return .= '</div>';
				}
			}

			$return .= '<div id="aec-toolbox-result">' . $tool->Action() . '</div>';
		}

		HTML_AcctExp::toolBox( $option, $cmd, $return, $info['name'] );
	}
}

function arrayValueDefault( $array, $name, $default )
{
	if ( is_object( $array ) ) {
		if ( isset( $array->$name ) ) {
			return $array->$name;
		} else {
			return $default;
		}
	}

	if ( isset( $array[$name] ) ) {
		if ( is_array( $array[$name] ) ) {
			$selected = array();
			foreach ( $array[$name] as $value ) {
				$selected[]->value = $value;
			}

			return $selected;
		} elseif ( strpos( $array[$name], ';' ) !== false ) {
			$list = explode( ';', $array[$name] );

			$selected = array();
			foreach ( $list as $value ) {
				$selected[]->value = $value;
			}

			return $selected;
		} else {
			return $array[$name];
		}
	} else {
		return $default;
	}
}

?>