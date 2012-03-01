<?php
/**
 * @version $Id: form.php
 * @package AEC - Account Control Expiration - Membership Manager
 * @subpackage Main Frontend
 * @copyright 2012 Copyright (C) David Deutsch
 * @author David Deutsch <skore@valanx.org> & Team AEC - http://www.valanx.org
 * @license GNU/GPL v.3 http://www.gnu.org/licenses/gpl.html or, at your option, any later version
 */

// Dont allow direct linking
( defined('_JEXEC') || defined( '_VALID_MOS' ) ) or die( 'Direct Access to this location is not allowed.' ) ?>
<form name="form-confirm" action="<?php echo $tmpl->url( array( 'task' => 'saveSubscription') ) ?>" method="post">
	<div id="confirmation-extra">
		<div class="alert alert-success">
			<div id="confirmation-form">
				<?php
				@include( $tmpl->tmpl( 'miform' ) );

				@include( $tmpl->tmpl( 'couponform' ) );

				if ( $makegift ) { @include( $tmpl->tmpl( 'giftform' ) ); }
				?>
			</div>
		</div>
	</div>
	<?php
	@include( $tmpl->tmpl( 'btn' ) );

	@include( $tmpl->tmpl( 'processorinfo' ) );
	?>
	<?php echo JHTML::_( 'form.token' ) ?>
</form>