<?
/**
 * @version $Id: confirmation.php
 * @package AEC - Account Control Expiration - Membership Manager
 * @subpackage Main Frontend
 * @copyright 2012 Copyright (C) David Deutsch
 * @author David Deutsch <skore@valanx.org> & Team AEC - http://www.valanx.org
 * @license GNU/GPL v.2 http://www.gnu.org/licenses/old-licenses/gpl-2.0.html or, at your option, any later version
 */

// Dont allow direct linking
( defined('_JEXEC') || defined( '_VALID_MOS' ) ) or die( 'Direct Access to this location is not allowed.' ) ?>
<div class="componentheading"><?php echo JText::_('CONFIRM_TITLE') ?></div>
<div id="confirmation">
	<?php @include( $tmpl->tmpl( 'confirminfo' ); ?>
	<?php @include( $tmpl->tmpl( 'confirmform' ); ?>
</div>
