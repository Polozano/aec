<?php
/**
 * @version $Id: acctexp.bucket.class.php
 * @package AEC - Account Control Expiration - Membership Manager
 * @subpackage Core Class
 * @copyright 2006-2015 Copyright (C) David Deutsch
 * @author David Deutsch <skore@valanx.org> & Team AEC - http://www.valanx.org
 * @license GNU/GPL v.3 http://www.gnu.org/licenses/gpl.html or, at your option, any later version
 */

// Dont allow direct linking
defined('_JEXEC') or die( 'Direct Access to this location is not allowed.' );

class aecBucketHandler
{
	public function getListForSubject( $subject )
	{
		$db = JFactory::getDBO();

		$query = 'SELECT `id`'
				. ' FROM #__acctexp_displaypipeline'
				. ' WHERE `subject` = \'' . $subject . '\''
				;
		$db->setQuery( $query );
		$buckets = xJ::getDBArray( $db );

		return $buckets;
	}

	public function getFullListForSubject( $subject )
	{
		$buckets = $this->getListForSubject( $subject );

		$array = array();
		foreach ( $buckets as $bid ) {
			$bucket = new aecBucket();
			$bucket->load( $bid );

			$array[] = $bucket;
		}

		return $array;
	}
}

class aecBucket extends serialParamDBTable
{
	/** @var int Primary key */
	var $id				= null;
	/** @var string */
	var $subject 		= null;
	/** @var datetime */
	var $created_date	= null;
	/** @var string */
	var $data 			= null;

	public function __construct()
	{
	 	parent::__construct( '#__acctexp_bucket', 'id' );
	}

	public function declareParamFields()
	{
		return array( 'params' );
	}

	/**
	 * @param string $subject
	 */
	public function stuff( $subject, $data )
	{
		$this->created_date	= date( 'Y-m-d H:i:s', ( (int) gmdate('U') ) );
		$this->subject		= $subject;
		$this->data		= $data;

		$this->check();
		$this->store();
	}

}
