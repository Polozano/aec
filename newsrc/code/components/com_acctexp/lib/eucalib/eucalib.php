<?php
/**
 * @version $Id: eucalib.php
 * @package AEC - Account Control Expiration - Membership Manager
 * @subpackage Abstract Library for Joomla Components
 * @copyright 2006-2015 Copyright (C) David Deutsch
 * @author David Deutsch <skore@valanx.org> & Team AEC - http://www.valanx.org
 * @license GNU/GPL v.3 http://www.gnu.org/licenses/gpl.html or, at your option, any later version
 *
 *                         _ _ _
 *                        | (_) |
 *     ___ _   _  ___ __ _| |_| |__
 *    / _ \ | | |/ __/ _` | | | '_ \
 *   |  __/ |_| | (_| (_| | | | |_) |
 *    \___|\__,_|\___\__,_|_|_|_.__/  v1.0
 *
 * The Extremely Useful Component LIBrary will rock your socks. Seriously. Reuse it!
 */

defined('_JEXEC') or die( 'Restricted access' );

if ( !defined( '_EUCA_CFG_LOADED' ) ){
	$require_file = dirname( __FILE__ ).'/eucalib.config.php';

	if( file_exists( $require_file ) ) {
		require_once( $require_file );
	}

	$require_file = dirname( __FILE__ ).'/eucalib.common.php';

	if( !class_exists( 'paramDBTable') ) {
		require_once( $require_file );
	}
}
