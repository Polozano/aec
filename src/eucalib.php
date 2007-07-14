<?php
/**
 * @version $Id: eucalib.php 16 2007-06-25 09:04:04Z mic $
 * @package AEC - Account Control Expiration - Subscription component for Joomla! OS CMS
 * @subpackage Abstract Library for Joomla Components
 * @copyright Copyright (C) 2007 David Deutsch, All Rights Reserved
 * @author David Deutsch <skore@skore.de>
 * @license GNU/GPL v.2 or later http://www.gnu.org/copyleft/gpl.html
 *
 * The Extremely Useful Component LIBrary will rock your socks. Seriously. Reuse it!
 */

defined( '_VALID_MOS' ) or die( 'Restricted access' );

/**
* parameterized Database Table entry
*
* For use with as an abstract class that adds onto table entries
*/
class paramDBTable extends mosDBTable {

	/**
	 * Receive Parameters and decode them into an array
	 * @return array
	 */
	function getParams( $field = 'params' ) {
		if( empty( $this->$field ) ) {
			return false;
		}

		$params = explode( "\n", $this->$field );

		$array = array();
		foreach( $params as $chunk ) {
			$k = explode( '=', $chunk, 2 );
			if( isset( $k[1] ) ) {
				$array[$k[0]] = stripslashes( $k[1] );
			}else{
				$array[$k[0]] = null;
			}
			unset( $k );
		}
		return $array;
	}

	/**
	 * Encode array and set Parameter field
	 */
	function setParams( $array, $field = 'params' ) {
		$params = array();

		foreach( $array as $key => $value ) {
			if( !is_null( $key ) ) {
				$value = trim( $value );
				if( !get_magic_quotes_gpc() ) {
					$value = addslashes( $value );
				}

				$params[] = $key . '=' . $value;
			}
		}

		$this->$field = implode( "\n", $params );
	}

	/**
	 * Add an array of Parameters to an existing parameter field
	 */
	function addParams( $array, $field = 'params', $overwrite = true ) {
		$params = $this->getParams( $field );
		foreach( $array as $key => $value ) {
			if( $overwrite ) {
				$params[$key] = $value;
			}else{
				if( !isset( $params[$key] ) ) {
					$params[$key] = $value;
				}
			}
		}
		$this->setParams ($params, $field);
	}

	/**
	 * Delete a set of Parameters providing an array of key names
	 */
	function delParams( $array, $field = 'params' ) {
		$params = $this->getParams( $field );
		foreach( $array as $key ) {
			if( isset( $params[$key] ) ) {
				unset( $params[$key] );
			}
		}
		$this->setParams( $params, $field );
	}

	/**
	 * Return the differences between a new set of Parameters and the existing one
	 */
	function diffParams( $array, $field = 'params' ) {
		$diff = array();

		$params = $this->getParams( $field );
		foreach( $array as $key => $value ) {
			if( isset( $params[$key] ) ) {
				if( $value !== $params[$key] ) {
					$diff[$key] = array( $params[$key], $value );
				}
			}
		}

		if( count( $diff ) ) {
			return $diff;
		}else{
			return false;
		}
	}
}

class languageFileHandler {

	function languageFileHandler( $filepath ) {
		$this->filepath = $filepath;
	}
	
	function getConstantsArray() {

		$file = fopen( $this->filepath, "r" );
	
		$array = array();
		while( !feof( $file ) ) {
			$buffer = fgets($file, 4096);
			if( strpos( $buffer, 'define') !== false ) {
				$linearray = explode( '\'', $buffer );
				if( count( $linearray ) === 5 ) {
					$array[$linearray[1]] = $linearray[3];
				}
			}
    	}

		return $array;
	}
	
	function getHTML() {

		$file = fopen( $this->filepath, "r" );
	
		$array = array();
		while( !feof( $file ) ) {
			$buffer = fgets($file, 4096);
			if( strpos( $buffer, 'define') !== false ) {
				$linearray = explode( '\'', $buffer );
				if( count( $linearray ) === 5 ) {
					$array[$linearray[1]] = $linearray[3];
				}
			}
    	}

		return $array;
	}
}
?>