<?php
/**
 * @version $Id: 2checkout.php,v 1.0 2007/06/21 09:22:22 mic Exp $ $Revision: 1.0 $
 * @package AEC - Account Control Expiration - Subscription component for Joomla! OS CMS
 * @subpackage Processors - 2CheckOut
 * @copyright Copyright (C) 2004-2007 Helder Garcia, David Deutsch
 * @author Helder Garcia, Davin Deutsch, mic (http://www.joomx.com)
 * @license GNU/GPL v.2 http://www.gnu.org/copyleft/gpl.html
 */
// Copyright (C) 2006-2007 David Deutsch
// All rights reserved.
// This source file is part of the Account Expiration Control Component, a  Joomla
// custom Component By Helder Garcia and David Deutsch - http://www.globalnerd.org
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License (GPL)
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// Please note that the GPL states that any headers in files and
// Copyright notices as well as credits in headers, source files
// and output (screens, prints, etc.) can not be removed.
// You can extend them with your own credits, though...
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//
// The "GNU General Public License" (GPL) is available at
// http://www.gnu.org/copyleft/gpl.html.

// Dont allow direct linking
defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );

class processor_2checkout {

	function processor_2checkout () {
		global $mosConfig_absolute_path;

		if( !defined( '_AEC_LANG_PROCESSOR' ) ) {
			$langPath = $mosConfig_absolute_path . '/components/com_acctexp/processors/com_acctexp_language_processors/';
			if (file_exists( $langPath . $GLOBALS['mosConfig_lang'] . '.php' )) {
				include_once( $langPath . $GLOBALS['mosConfig_lang'] . '.php' );
			}else{
				include_once( $langPath . 'english.php' );
			}
		}
	}

	function info () {
		$info = array();
		$info['name']				= '2checkout';
		$info['longname'] 			= _AEC_PROC_INFO_2CO_LNAME;
		$info['statement'] 			= _AEC_PROC_INFO_2CO_STMNT;
		$info['description'] 		= _DESCRIPTION_2CHECKOUT;
		$info['cc_list'] 			= "visa,mastercard,discover,americanexpress,echeck,jcb,dinersclub";
		$info['recurring'] 			= 0;

		return $info;
	}

	function settings () {
		$settings = array();
		$settings['sid']			= '2checkout sid';
		$settings['secret_word']	= 'secret_word';
		$settings['testmode']		= 0;
		$settings['alt2courl']		= '';
		$settings['info']			= ''; // new mic
		$settings['rewriteinfo']	= ''; // new mic
		$settings['item_name']		= 'Subscription at [[cms_live_site]] - User: [[user_name]] ([[user_username]])';

		return $settings;
	}

	function backend_settings () {
		$settings = array();
		$settings['testmode']		= array( 'list_yesno' );
		$settings['sid']			= array( 'inputC' );
		$settings['secret_word']	= array( 'inputC' );
		$settings['info']			= array( 'fieldset' );
		$settings['alt2courl']		= array( 'list_yesno' );
		$settings['item_name']		= array( 'inputE' );

		$rewriteswitches			= array( 'cms', 'user', 'expiration', 'subscription', 'plan' );
        $settings['rewriteInfo']	= array( 'fieldset', _AEC_MI_REWRITING_INFO,
        							AECToolbox::rewriteEngineInfo( $rewriteswitches ) );

		return $settings;
	}

	function createGatewayLink ( $int_var, $cfg, $metaUser, $new_subscription ) {
		global $mosConfig_live_site;

		if( $cfg['alt2courl'] ) {
			$var['post_url']		= 'https://www2.2checkout.com/2co/buyer/purchase';
		}else{
			$var['post_url']		= 'https://www.2checkout.com/2co/buyer/purchase';
		}

		if( $cfg['testmode'] ) {
			$var['testmode']		= 1;
			$var['demo']			= 'Y';
		}

		$var['sid']					= $cfg['sid'];
		$var['invoice_number']		= $int_var['invoice'];
		$var['fixed']				= 'Y';
		$var['total'] = $int_var['amount'];

		$var['cust_id']			= $metaUser->cmsUser->id;
		$var['cart_order_id']	= AECToolbox::rewriteEngine($cfg['item_name'], $metaUser, $new_subscription);
		$var['username']		= $metaUser->cmsUser->username;
		$var['name']			= $metaUser->cmsUser->name;

		return $var;
	}

	function parseNotification ( $post, $cfg ) {
		$description			= $post['cart_order_id'];
		$key					= $post['key'];
		$total					= $post['total'];
		$userid					= $post['cust_id'];
	    $invoice_number			= $post['invoice_number'];
	    $order_number			= $post['order_number'];
		$username				= $post['username'];
		$name					= $post['name'];
		$planid					= $post['planid'];
		$name					= $post['name'];

		if ($cfg['testmode']) {
			$string_to_hash	= $cfg['secret_word'].$cfg['sid']."1".$total;
		} else {
			$string_to_hash	= $cfg['secret_word'].$cfg['sid'].$order_number.$total;
		}
		$check_key		= strtoupper(md5($string_to_hash));

		$response = array();
		$response['invoice'] = $invoice_number;
		$response['valid'] = (strcmp($check_key, $key) == 0);

		return $response;
	}

}
?>