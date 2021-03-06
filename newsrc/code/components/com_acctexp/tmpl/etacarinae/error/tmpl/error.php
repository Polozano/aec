<?php
/**
 * @version $Id: error.php
 * @package AEC - Account Control Expiration - Membership Manager
 * @subpackage Main Frontend
 * @copyright 2012 Copyright (C) David Deutsch
 * @author David Deutsch <skore@valanx.org> & Team AEC - http://www.valanx.org
 * @license GNU/GPL v.3 http://www.gnu.org/licenses/gpl.html or, at your option, any later version
 */

// Dont allow direct linking
defined('_JEXEC') or die( 'Direct Access to this location is not allowed.' ); ?>
<div id="aec">
	<div id="aec-error">
		<div class="componentheading"><?php echo JText::_('CHECKOUT_ERROR_TITLE') ?></div>
		<p><?php echo JText::_('CHECKOUT_ERROR_EXPLANATION') . ( $error ? ( ': ' . $error ) : '' ) ?></p>
		<p><?php if ( !$suppressactions ) { echo JText::_('CHECKOUT_ERROR_OPENINVOICE'); @include( $tmpl->tmpl( 'pending.invoice_links' ) ); } ?></p>
	</div>
</div>
