<?php
/**
 * @version $Id: processorinfo.php
 * @package AEC - Account Control Expiration - Membership Manager
 * @subpackage Main Frontend
 * @copyright 2012 Copyright (C) David Deutsch
 * @author David Deutsch <skore@valanx.org> & Team AEC - http://www.valanx.org
 * @license GNU/GPL v.3 http://www.gnu.org/licenses/gpl.html or, at your option, any later version
 */

// Dont allow direct linking
defined('_JEXEC') or die( 'Direct Access to this location is not allowed.' ) ?>
<div class="processor-list">
	<?php if ( !empty( $InvoiceFactory->pp ) ) {
		if ( is_object( $InvoiceFactory->pp ) ) {
			$processor = $InvoiceFactory->pp;
			@include( $tmpl->tmpl( 'plans.processor_details' ) );
		}
	} ?>
</div>
