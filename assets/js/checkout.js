/**
 * WPFH Frictionless Checkout Frontend Scripts
 */
(function($) {
	'use strict';

	$(document).ready(function() {
		// Update button label on dynamic cart updates
		$(document.body).on('updated_checkout', function() {
			var totalText = $('.order-total .woocommerce-Price-amount').text();
			if (totalText && (totalText.indexOf('0.00') !== -1 || totalText.indexOf('$0') !== -1)) {
				$('#place_order').text('Complete Free Download');
			}
		});

		// Auto focus first empty input if none focused
		if (!$(':focus').length) {
			$('#billing_first_name').focus();
		}
	});
})(jQuery);
