<?php
/**
 * @version $Id: mi_idevaffiliate.php 16 2007-07-02 13:29:29Z mic $
 * @package AEC - Account Control Expiration - Subscription component for Joomla! OS CMS
 * @subpackage Micro Integrations - iDevAffiliate
 * @copyright 2006/2007 Copyright (C) David Deutsch
 * @author David Deutsch <skore@skore.de> & Team AEC - http://www.globalnerd.org
 * @license GNU/GPL v.2 http://www.gnu.org/copyleft/gpl.html
 */

defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );

class mi_idevaffiliate
{
	function Info()
	{
		$info = array();
		$info['name'] = _AEC_MI_NAME_IDEV;
		$info['desc'] = _AEC_MI_DESC_IDEV;

		return $info;
	}

	function Settings( $params )
	{
		$settings = array();
		$settings['setupinfo'] = array( 'fieldset' );
		$settings['profile'] = array( 'inputC' );
		$settings['directory'] = array( 'inputC' );
		$settings['onlycustomparams'] = array( 'list_yesno' );
		$settings['customparams'] = array( 'inputD' );
		$rewriteswitches				= array( 'cms', 'user', 'expiration', 'subscription', 'plan', 'invoice' );
		$settings['rewriteInfo']		= array( 'fieldset', _AEC_MI_SET11_EMAIL, AECToolbox::rewriteEngineInfo( $rewriteswitches ) );

		return $settings;
	}

	function action( $params, $metaUser, $invoice, $plan )
	{
		global $database, $mosConfig_live_site;

		$rooturl = $this->getPath( $params );

		$getparams = array();

		if ( !empty( $params['profile'] ) ) {
			$getparams[] = 'profile=' . $params['profile'];
		}

		$getparams[] = 'idev_saleamt=' . $invoice->amount;
		$getparams[] = 'idev_ordernum=' . $invoice->invoice_number;

		if ( !empty( $params['onlycustomparams'] ) && !empty( $params['customparams'] ) ) {
			$getparams = array();
		}

		$userflags = $metaUser->objSubscription->getMIflags( $plan->id, $this->id );

		if ( !empty( $userflags['IDEV_IP_ADDRESS'] ) ) {
			$ip = $userflags['IDEV_IP_ADDRESS'];
		} else {
			$subscr_params = $metaUser->focusSubscription->getParams();

			if ( isset( $subscr_params['creator_ip'] ) ) {
				$ip = $subscr_params['creator_ip'];
			} else {
				$ip = $_SERVER['REMOTE_ADDR'];
			}

			$newflags['idev_ip_address'] = $ip;
			$metaUser->objSubscription->setMIflags( $plan->id, $this->id, $newflags );
		}

		$getparams[] = 'ip_address=' . $ip;

		if ( !empty( $params['customparams'] ) ) {
			$rw_params = AECToolbox::rewriteEngine( $params['customparams'], $metaUser, $plan, $invoice );

			$cps = explode( "\n", $rw_params );

			foreach ( $cps as $cp ) {
				$getparams[] = $cp;
			}
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $rooturl . "/sale.php?" . implode( '&amp;', $getparams ) );
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_exec($ch);
		curl_close($ch);

		return true;
	}

	function getPath( $params )
	{
		global $mosConfig_live_site;

		if ( !empty( $params['directory'] ) ) {
			if ( ( strpos( $params['directory'], 'http://' ) === 0 ) || ( strpos( $params['directory'], 'https://' ) === 0 ) ) {
				$rooturl = $params['directory'];
			} else {
				if ( strpos( "/", $params['directory'] ) !== 0 ) {
					$params['directory'] = "/" . $params['directory'];
				}

				$rooturl = $mosConfig_live_site . $params['directory'];
			}
		} else {
			$rooturl = $mosConfig_live_site . '/idevaffiliate';
		}
	}
}
?>