<?php
/**
 * @version $Id: planbtn.php
 * @package AEC - Account Control Expiration - Membership Manager
 * @subpackage Main Frontend
 * @copyright 2012 Copyright (C) David Deutsch
 * @author David Deutsch <skore@valanx.org> & Team AEC - http://www.valanx.org
 * @license GNU/GPL v.3 http://www.gnu.org/licenses/gpl.html or, at your option, any later version
 */

// Dont allow direct linking
defined('_JEXEC') or die( 'Direct Access to this location is not allowed.' ) ?>
<div class="processor-button">
	<?php echo $tmpl->btn( $gwitem->btn, $gwitem->btn['content'], $gwitem->btn['class'] ) ?>
</div>
