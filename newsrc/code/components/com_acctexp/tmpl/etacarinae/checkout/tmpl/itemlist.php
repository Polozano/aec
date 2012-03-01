<?php
/**
 * @version $Id: itemlist.php
 * @package AEC - Account Control Expiration - Membership Manager
 * @subpackage Main Frontend
 * @copyright 2012 Copyright (C) David Deutsch
 * @author David Deutsch <skore@valanx.org> & Team AEC - http://www.valanx.org
 * @license GNU/GPL v.3 http://www.gnu.org/licenses/gpl.html or, at your option, any later version
 */

// Dont allow direct linking
( defined('_JEXEC') || defined( '_VALID_MOS' ) ) or die( 'Direct Access to this location is not allowed.' ) ?>

<div id="aec-checkout">
<?php if ( !empty( $InvoiceFactory->cartobject ) && !empty( $InvoiceFactory->cart ) ) {
	@include( $tmpl->tmpl( 'plans.backtocart' ) );
} ?>
<?php foreach ( $itemlist as $item ) { ?>
		<div class="checkout-list-item">
			<div class="checkout-list-item-description">
				<?php if ( !empty( $item['name'] ) ) {
					if ( !empty( $item['quantity'] ) ) { ?>
						<h4><?php echo $item['name'] . ( ( $item['quantity'] > 1 ) ? " (&times;" . $item['quantity'] . ")" : '' ) ?></h4>
					<?php } else { ?>
						<h4><?php echo $item['name'] ?></h4>
					<?php } ?>
				<?php } ?>
				<?php if ( !empty( $item['desc'] ) ) { ?>
					<p><?php echo $item['desc'] ?></p>
				<?php } ?>
			</div>
			<?php if ( !empty( $item['terms'] ) ) { ?>
				<div class="checkout-list-item-terms">
					<?php foreach ( $item['terms'] as $term ) { ?>
						<div class="checkout-list-term list-term-<?php echo $term['type'] ?><?php echo $term['current'] ? 'list-term-current':'' ?>">
							<h4><?php echo JText::_( strtoupper( $ttype ) ) . $term['applicable'] ?></h4>
							<?php if ( !empty( $term['duration'] ) ) { ?>
								<p><?php echo JText::_('AEC_CHECKOUT_DURATION') . ': ' . $term['duration'] ?></p>
							<?php } ?>
							<div class="checkout-term-cost">
								<?php foreach ( $term['cost'] as $cost ) { ?>
									<div class="checkout-cost-<?php echo $cost['type'] ?>">
										<p><?php echo $cost['details'] . ': ' . $cost['cost'] ?></p>
									</div>
								<?php } ?>
							</div>
						</div>
					<?php } ?>
				</div>
			<?php } ?>
		</div>
<?php } ?>


<?php if ( count( $InvoiceFactory->items->itemlist ) > 1 ) {
		//echo '<tr class="aec_term_row_sep"><td colspan="2"></td></tr>';
		//echo '<tr class="aec_term_totalhead current_period"><th colspan="2" class="' . $ttype . '">' . JText::_('CART_ROW_TOTAL') . '</th></tr>';

		if ( !empty( $InvoiceFactory->items->total ) ) {
			$c = AECToolbox::formatAmount( $InvoiceFactory->items->total->renderCost(), $InvoiceFactory->payment->currency );

			//echo '<tr class="aec_term_costrow current_period"><td class="aec_term_totaltitle">' . JText::_('AEC_CHECKOUT_TOTAL') . ':' . '</td><td class="aec_term_costamount">' . $c . '</td></tr>';
		}

		if ( !empty( $InvoiceFactory->items->discount ) ) {
			// Iterate through full discounts
			foreach ( $InvoiceFactory->items->discount as $citems ) {
				foreach ( $citems as $ccitem ) {
					$citem = $ccitem->renderCost();

					foreach ( $citem as $cost ) {
						if ( $cost->type == 'discount' ) {
							$t = JText::_( strtoupper( 'aec_checkout_' . $cost->type ) );

							$amount = AECToolbox::correctAmount( $cost->cost['amount'] );

							$c = AECToolbox::formatAmount( $amount, $InvoiceFactory->payment->currency );

							if ( !empty( $cost->cost['details'] ) ) {
								$t .= '&nbsp;(' . $cost->cost['details'] . ')';
							}

							if ( !empty( $cost->cost['coupon'] ) ) {
								$t .= '&nbsp;['
										. $tmpl->lnk( array(	'task' => 'InvoiceRemoveCoupon',
															'invoice' => $InvoiceFactory->invoice->invoice_number,
															'coupon_code' => $citem->cost['coupon']
															), JText::_('CHECKOUT_INVOICE_COUPON_REMOVE') )
										. ']';
							}

							//echo '<tr class="aec_term_' . $cost->type . ' current_period"><td class="aec_term_' . $cost->type . 'title">' . $t . ':' . '</td><td class="aec_term_' . $cost->type . 'amount">' . $c . '</td></tr>';
						}
					}
				}
			}
		}

		if ( !empty( $InvoiceFactory->items->tax ) ) {
			foreach ( $InvoiceFactory->items->tax as $titems ) {
				foreach ( $titems['terms']->terms as $titem ) {
					$citem = $titem->renderCost();

					foreach ( $citem as $cost ) {
						if ( $cost->type == 'tax' ) {
							$t = JText::_( strtoupper( 'aec_checkout_' . $cost->type ) );

							$amount = AECToolbox::correctAmount( $cost->cost['amount'] );

							$c = AECToolbox::formatAmount( $amount, $InvoiceFactory->payment->currency );

							if ( !empty( $cost->cost['details'] ) ) {
								$t .= '&nbsp;( ' . $cost->cost['details'] . ' )';
							}

							//echo '<tr class="aec_term_' . $cost->type . '"><td class="aec_term_' . $cost->type . 'title">' . $t . ':' . '</td><td class="aec_term_' . $cost->type . 'amount">' . $c . '</td></tr>';
						}
					}
				}
			}

		}

		if ( !empty( $InvoiceFactory->items->grand_total ) ) {
			$c = AECToolbox::formatAmount( $InvoiceFactory->items->grand_total->renderCost(), $InvoiceFactory->payment->currency );

			//echo '<tr class="aec_term_totalrow current_period"><td class="aec_term_totaltitle">' . JText::_('AEC_CHECKOUT_GRAND_TOTAL') . ':' . '</td><td class="aec_term_totalamount">' . $c . '</td></tr>';
		}

		//echo '<tr class="aec_term_row_sep"><td colspan="2"></td></tr>';
	}
?>
</div>