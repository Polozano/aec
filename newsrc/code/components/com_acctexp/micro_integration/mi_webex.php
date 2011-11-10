<?php
/**
 * @version $Id: mi_webex.php
 * @package AEC - Account Control Expiration - Membership Manager
 * @subpackage Micro Integrations - Webex
 * @copyright 2011 Copyright (C) David Deutsch
 * @author David Deutsch <skore@valanx.org> & Team AEC - http://www.valanx.org
 * @license GNU/GPL v.2 http://www.gnu.org/licenses/old-licenses/gpl-2.0.html or, at your option, any later version
 */

// Dont allow direct linking
( defined('_JEXEC') || defined( '_VALID_MOS' ) ) or die( 'Direct Access to this location is not allowed.' );

class mi_webex
{
	function Info()
	{
		$info = array();
		$info['name'] = JText::_('AEC_MI_WEBEX_NAME');
		$info['desc'] = JText::_('AEC_MI_WEBEX_DESC');

		return $info;
	}

	function Settings()
	{
		$settings = array();
		$settings['hosted_name']		= array( 'inputC' );
		$settings['pid']				= array( 'inputC' );
		//$settings['customparams']		= array( 'inputD' );

		$rewriteswitches				= array( 'cms', 'user', 'expiration', 'subscription', 'plan', 'invoice' );

		$settings						= AECToolbox::rewriteEngineInfo( $rewriteswitches, $settings );

		return $settings;
	}

	function CommonData()
	{
		return array( 'hosted_name', 'pid' );
	}

	function action( $request )
	{
		$db = &JFactory::getDBO();

		return true;
	}

	function on_userchange_action( $request )
	{
		$password = $this->getPWrequest( $request );

		if ( !( strcmp( $request->trace, 'registration' ) === 0 ) ) {
			$ht = $this->getHTAccess( $this->settings );

			$userlist = $ht->getUsers();

			if ( in_array( $request->row->username, $userlist ) ) {
				$ht->delUser( $request->row->username );

				if ( $this->settings['use_md5'] ) {
					$ht->addUser( $request->row->username, $request->row->password );
				} else {
					$ht->addUser( $request->row->username, $apachepw->apachepw );
				}

				$ht->addLogin();
			}
		}

		return true;
	}

	function getPWrequest( $request )
	{
		if ( !empty( $request->post['password_clear'] ) ) {
			return $request->post['password_clear'];
		} elseif ( !empty( $request->post['password'] ) ) {
			return $request->post['password'];
		} elseif ( !empty( $request->post['password2'] ) ) {
			return $request->post['password2'];
		} else {
			return "";
		}
	}

	function apiUserSignup( $request )
	{
		$name = $request->metaUser->explodeName();

		$array = array(	'AT' => 'SU',
						'FN' => $name['first'],
						'LN' => $name['last'],
						'EM' => $request->metaUser->cmsUser->email,
						'PW' => $request->metaUser->cmsUser->password,
						'PID' => $this->settings['pid'],
						'WID' => $request->metaUser->username,
		);

		return $this->apiCall( $array, $request );
	}

	function apiActivateUser( $request )
	{
		$array = array(	'AT' => 'AC',
						'PID' => $this->settings['pid'],
						'WID' => $request->metaUser->username,
		);

		return $this->apiCall( $array, $request );
	}

	function apiDeactivateUser( $request )
	{
		$array = array(	'AT' => 'IN',
						'PID' => $this->settings['pid'],
						'WID' => $request->metaUser->username,
		);

		return $this->apiCall( $array, $request );
	}

	function apiCall( $array, $request )
	{
		global $aecConfig;

		if ( !empty( $this->settings[$array['AT'].'_customparams'] ) ) {
			$rw_params = AECToolbox::rewriteEngineRQ( $this->settings[$array['AT'].'_customparams'], $request );

			if ( strpos( $rw_params, "\r\n" ) !== false ) {
				$cps = explode( "\r\n", $rw_params );
			} else {
				$cps = explode( "\n", $rw_params );
			}

			foreach ( $cps as $cp ) {
				$array[] = $cp;
			}
		}

		$req = array();
		foreach ( $array as $key => $value ) {
			$req[] = $key.'='.urlencode( stripslashes( $value ) );
		}

		$path = '/' . $this->settings['hosted_name'] . '/m.php?' . implode( '&', $req );
		$url = 'https://' . $this->settings['hosted_name'] . '.webex.com' . $path;

		if ( $aecConfig->cfg['curl_default'] ) {
			$response = processor::doTheCurl( $url, array() );
			if ( $response === false ) {
				// If curl doesn't work try using fsockopen
				$response = processor::doTheHttp( $url, $path, array() );
			}
		} else {
			$response = processor::doTheHttp( $url, $path, array() );
			if ( $response === false ) {
				// If fsockopen doesn't work try using curl
				$response = processor::doTheCurl( $url, array() );
			}
		}

		return $response;
	}
}
?>
