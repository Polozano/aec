<?php
/**
 * @version $Id: mi_supporttimetracker.php
 * @package AEC - Account Control Expiration - Membership Manager
 * @subpackage Micro Integrations - Support Time tracker
 * @copyright 2010 Copyright (C) David Deutsch
 * @author David Deutsch <skore@skore.de> & Team AEC - http://www.valanx.org
 * @license GNU/GPL v.2 http://www.gnu.org/licenses/old-licenses/gpl-2.0.html or, at your option, any later version
 */

// Dont allow direct linking
( defined('_JEXEC') || defined( '_VALID_MOS' ) ) or die( 'Direct Access to this location is not allowed.' );

class mi_supporttimetracker extends MI
{
	function Info()
	{
		$info = array();
		$info['name'] = 'Support Time Tracker';
		$info['desc'] = 'Simple time tracker that can be used to keep track of support hours';

		return $info;
	}

	function Settings()
	{
		$settings = array();
		$settings['add_minutes']		= array( 'inputE', 'Add Support Minutes', 'Add this amount of minutes to the user account' );

		$rewriteswitches				= array( 'cms', 'user', 'expiration', 'subscription', 'plan', 'invoice' );
		$settings['rewriteInfo']		= array( 'fieldset', _AEC_MI_SET11_EMAIL, AECToolbox::rewriteEngineInfo( $rewriteswitches ) );

		return $settings;
	}

	function Defaults()
	{
		$defaults = array();
		$defaults['userid']	= "[[user_id]]";
		$defaults['email']	= "[[user_email]]";

		return $defaults;
	}

	function profile_info( $request )
	{
		$minutes = $this->getSupportMinutes( $request->metaUser );

		if ( empty( $minutes ) ) {
			$message = "You don't have any Support Time left for this account";
		} else {
			$hrs = $minutes / 60;
			$min = $minutes % 60;

			$message = "You have <strong>" . $hrs . " hour" . ( ( $hrs == 1 ) ? 's' : '' ) . ' and ' . $min . " minute" . ( ( $min == 1 ) ? 's' : '' ) . '</strong> Support Time left in this account.';
		}

		return $message;
	}

	function relayAction( $request )
	{
		if ( $request->action == 'action' ) {
			if ( !empty( $this->settings['add_minutes'] ) ) {
				$details = "The User has added support time to the account.";

				if ( !empty( $request->plan->id ) ) {
					$details .= " Plan: " . $request->plan->id;
				}

				$this->updateSupportMinutes( $request->metaUser, $this->settings['add_minutes'], 0, $details );
			}
		}

		return true;
	}

	function admin_form( $request )
	{
		$history = $this->getSupportHistory( $request->metaUser );

		$settings = array();

		$settings['log_minutes']	= array( 'inputC', 'Log Minutes', 'The amount of minutes you want to log for this user account. You can also supply a negative value to correct a mistake' );
		$settings['remove_last']	= array( 'list_yesno', 'Remove Last', 'If you want to correct your last log, set this option to Yes, provide details below and save.' );
		$settings['details']		= array( 'inputD', 'Details', 'Give details on the update' );

		if ( empty( $history ) ) {
			$settings['history']	= array( 'fieldset', 'History', 'There is no history for this account' );
		} else {
			global $mainframe;

			$history_table = '<table>';
			$history_table .= '<tr><th>ID</th><th>Date</th><th>Minutes</th><th>Change</th><th>Minutes Used</th><th>Used Change</th><th>Details</th></tr>';

			foreach ( $history as $id => $entry ) {
				$history_table .= '<tr>'
									. '<td>' . $id . '</td>'
									. '<td>' . date( 'Y-m-d H:i:s', $entry['tstamp'] + ( $mainframe->getCfg( 'offset' ) * 3600 ) ) . '</td>'
									. '<td>' . $entry['support_minutes'] . '</td>'
									. '<td>' . $entry['minutes_added'] . '</td>'
									. '<td>' . $entry['support_minutes_used'] . ' Used</td>'
									. '<td>' . $entry['minutes_used'] . ' Change</td>'
									. '<td>' . $entry['details'] . '</td>'
									. '</tr>';
			}

			$history_table .= '</table>';

			$settings['history']	= array( 'fieldset', 'History', $history_table );
		}

		return $settings;
	}

	function admin_form_save( $request )
	{
		if ( !empty( $request->params['log_minutes'] ) ) {
			$this->updateSupportMinutes( $request->metaUser, 0, $request->params['log_minutes'], $request->params['details'] );

			$request->params['log_minutes']	= 0;
			$request->params['details']		= '';
		}
	}

	function getSupportHistory( $metaUser )
	{
		$uparams = $metaUser->meta->getCustomParams();

		if ( !empty( $uparams['support_minutes_history'] ) ) {
			return $uparams['support_minutes_history'];
		}

		return array();
	}

	function getSupportMinutes( $metaUser )
	{
		$uparams = $metaUser->meta->getCustomParams();

		if ( isset( $uparams['support_minutes'] ) ) {
			if ( !empty( $uparams['support_minutes_used'] ) ) {
				return $uparams['support_minutes'] - $uparams['support_minutes_used'];
			} else {
				return $uparams['support_minutes'];
			}
		}

		return 0;
	}

	function updateSupportMinutes( $metaUser, $minutes, $use_minutes, $details )
	{
		$uparams = $metaUser->meta->getCustomParams();

		if ( !empty( $uparams['support_minutes_history'] ) ) {
			$history = $uparams['support_minutes_history'];
		} else {
			$history = array();
		}

		if ( !empty( $minutes ) && !empty( $uparams['support_minutes'] ) ) {
			$uparams['support_minutes'] = $uparams['support_minutes'] + $minutes;
		} elseif ( !empty( $minutes ) ) {
			$uparams['support_minutes'] = $minutes;
		}

		if ( !empty( $use_minutes ) && !empty( $uparams['support_minutes_used'] ) ) {
			$uparams['support_minutes_used'] = $uparams['support_minutes_used'] - $use_minutes;
		} elseif ( !empty( $minutes ) ) {
			$uparams['support_minutes_used'] = $use_minutes;
		}

		$params		= array(	'support_minutes_history'	=> $history,
								'support_minutes_used'		=> $uparams['support_minutes_used'],
								'support_minutes'			=> $uparams['support_minutes']
							);

		$history = $this->getSupportHistory( $metaUser );

		$history[]	= array(	'tstamp'				=> time(),
								'support_minutes'		=> $params['support_minutes'],
								'minutes_added'			=> $minutes,
								'support_minutes_used'	=> $params['support_minutes_used'],
								'minutes_used'			=> $use_minutes,
								'details'				=> $details
							);

		$metaUser->meta->setCustomParams( $params );
		$metaUser->meta->storeload();
	}
}
?>
