/**
 * Number formatting utility for Key Figure blocks (frontend).
 */

import { formatNumber } from './utils/format-number';

function initializeNumberFormatting() {
	// Only run on frontend (not in editor)
	if (typeof wp !== 'undefined' && wp.data && wp.blocks) {
		return; // Skip in editor context
	}

	const numberElements = document.querySelectorAll(
		'.wp-block-blockparty-key-figure__number'
	);

	numberElements.forEach((element) => {
		const increment = element.getAttribute('data-increment');
		const decimalSeparator = element.getAttribute('data-decimal-separator');
		const minimumFractionDigits = parseInt(
			element.getAttribute('data-minimum-fraction-digits') || '0',
			10
		);

		if (increment) {
			const formattedNumber = formatNumber(
				parseFloat(increment),
				decimalSeparator,
				minimumFractionDigits
			);
			element.textContent = formattedNumber;
		}
	});
}

// Initialize on DOM ready (frontend only)
if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initializeNumberFormatting);
} else {
	initializeNumberFormatting();
}

// Export for potential use in other contexts
window.blockpartyKeyFigureFormatter = {
	formatNumber,
	initialize: initializeNumberFormatting,
};
