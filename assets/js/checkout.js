/**
 * WPFH Frictionless Checkout Frontend Scripts
 */
(function($) {
	'use strict';

	function parseCheckoutAmount(text) {
		if (!text || !/\d/.test(text)) {
			return null;
		}
		// Keep digits, dots, commas only
		var cleaned = text.replace(/[^\d.,]/g, '');
		if (!cleaned) {
			return null;
		}
		// Normalize decimal separator: if both '.' and ',' exist, the last one is the decimal separator.
		var lastDot = cleaned.lastIndexOf('.');
		var lastComma = cleaned.lastIndexOf(',');
		if (lastDot !== -1 && lastComma !== -1) {
			if (lastComma > lastDot) {
				cleaned = cleaned.replace(/\./g, '').replace(',', '.');
			} else {
				cleaned = cleaned.replace(/,/g, '');
			}
		} else if (lastComma !== -1) {
			// Single comma: decimal if 1-2 digits follow it, otherwise thousands separator.
			var afterComma = cleaned.length - lastComma - 1;
			if (afterComma === 1 || afterComma === 2) {
				cleaned = cleaned.replace(',', '.');
			} else {
				cleaned = cleaned.replace(/,/g, '');
			}
		}
		var val = parseFloat(cleaned);
		return isNaN(val) ? null : val;
	}

	$(document).ready(function() {
		// Update button label on dynamic cart updates
		$(document.body).on('updated_checkout', function() {
			var $amountEl = $('.order-total .woocommerce-Price-amount').first();
			var amount = parseCheckoutAmount($amountEl.text());
			if (amount === null) {
				return;
			}
			var freeLabel = (typeof wpfh_fc_vars !== 'undefined' && wpfh_fc_vars.free_button_text) ? wpfh_fc_vars.free_button_text : 'Complete Free Download';
			var paidLabel = (typeof wpfh_fc_vars !== 'undefined' && wpfh_fc_vars.paid_button_text) ? wpfh_fc_vars.paid_button_text : 'Complete Order & Get Access';
			if (amount === 0) {
				$('#place_order').text(freeLabel).val(freeLabel);
			} else {
				$('#place_order').text(paidLabel).val(paidLabel);
			}
		});

		// Auto focus first empty input if none focused
		if (!$(':focus').length) {
			$('#billing_first_name').focus();
		}
	});
})(jQuery);
