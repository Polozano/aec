<?php
/**
 * @version $Id: acctexp.history.class.php
 * @package AEC - Account Control Expiration - Membership Manager
 * @subpackage Core Class
 * @copyright 2006-2015 Copyright (C) David Deutsch
 * @author David Deutsch <skore@valanx.org> & Team AEC - http://www.valanx.org
 * @license GNU/GPL v.3 http://www.gnu.org/licenses/gpl.html or, at your option, any later version
 */

// Dont allow direct linking
defined('_JEXEC') or die( 'Direct Access to this location is not allowed.' );

class logHistory extends serialParamDBTable
{
	/** @var int Primary key */
	var $id					= null;
	/** @var int */
	var $proc_id;
	/** @var string */
	var $proc_name;
	/** @var int */
	var $user_id;
	/** @var string */
	var $user_name;
	/** @var int */
	var $plan_id;
	/** @var string */
	var $plan_name;
	/** @var datetime */
	var $transaction_date	= null;
	/** @var string */
	var $amount;
	/** @var string */
	var $invoice_number;
	/** @var string */
	var $response;

	public function logHistory()
	{
		parent::__construct( '#__acctexp_log_history', 'id' );
	}

	public function declareParamFields()
	{
		return array( 'response' );
	}

	public function load( $id=null, $reset=true )
	{
		parent::load( $id );

		if ( $this->cleanup() ) {
			$this->storeload();
		}
	}

	public function cleanup()
	{
		if ( empty( $this->response ) ) {
			return false;
		}

		if ( is_string( $this->response ) ) {
			$this->response = unserialize( base64_decode( $this->response ) );
		}

		if ( is_string( $this->response ) ) {
			$this->response = unserialize( base64_decode( $this->response ) );

			return true;
		}

		$original = $this->response;

		foreach( $this->response as $k => $v ) {
			if ( !is_array( $v ) ) {
				$this->response = unserialize( base64_decode( $k ) );

				if ( !is_array( $this->response ) ) {
					$this->response = $original;

					return false;
				}

				return true;
			} elseif ( !is_array( $k ) ) {
				$this->response = unserialize( base64_decode( $v ) );

				if ( !is_array( $this->response ) ) {
					$this->response = $original;

					return false;
				}

				return true;
			}
		}

		return false;
	}

	/**
	 * @param Invoice $objInvoice
	 */
	public function entryFromInvoice( $objInvoice, $response, $pp )
	{
		$user = new cmsUser();
		$user->load( $objInvoice->userid );

		$plan = new SubscriptionPlan();
		$plan->load( $objInvoice->usage );

		if ( $pp->id ) {
			$this->proc_id			= $pp->id;
			$this->proc_name		= $pp->processor_name;
		}

		$this->user_id			= $user->id;
		$this->user_name		= $user->username;

		if ( $plan->id ) {
			$this->plan_id			= $plan->id;
			$this->plan_name		= $plan->name;
		}

		$this->transaction_date	= date( 'Y-m-d H:i:s', ( ( (int) gmdate('U') ) ) );
		$this->amount			= $objInvoice->amount;
		$this->invoice_number	= $objInvoice->invoice_number;
		$this->response			= $response;

		$this->cleanup();

		$short	= 'history entry';
		$event	= 'Processor (' . $pp->processor_name . ') notification for ' . $objInvoice->invoice_number;
		$tags	= 'history,processor,payment';
		$params = array( 'invoice_number' => $objInvoice->invoice_number );

		$eventlog = new eventLog();
		$eventlog->issue( $short, $tags, $event, 2, $params );

		$this->check();
		$this->store();
	}
}
