<?php
/**
 * @version $Id: cart/html.php
 * @package AEC - Account Control Expiration - Membership Manager
 * @subpackage Main Frontend
 * @copyright 2012 Copyright (C) David Deutsch
 * @author David Deutsch <skore@valanx.org> & Team AEC - http://www.valanx.org
 * @license GNU/GPL v.2 http://www.gnu.org/licenses/old-licenses/gpl-2.0.html or, at your option, any later version
 */

// Dont allow direct linking
( defined('_JEXEC') || defined( '_VALID_MOS' ) ) or die( 'Direct Access to this location is not allowed.' );

if ( !empty( $tmpl->cfg['tos'] ) ) {
	$js = 'function submitPayment() {
		if ( document.confirmForm.tos.checked ) {
			document.confirmForm.submit();
		} else {
			alert("' . html_entity_decode( JText::_('CONFIRM_TOS_ERROR') ) . ' )");
		}
	}';

	$tmpl->addScriptDeclaration( $js );
}

$tmpl->addDefaultCSS();

@include( $tmpl->tmpl( 'cart' ) );
