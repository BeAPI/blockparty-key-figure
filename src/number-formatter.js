/**
 * Number formatting utility for Key Figure blocks
 * Frontend-only formatting (editor formatting handled in React components)
 */

function formatNumber(numberToFormat, decimalSeparator, minimumFractionDigits) {
	if (!numberToFormat && numberToFormat !== 0) {
		return numberToFormat;
	}

	if (decimalSeparator === 'none') {
		return numberToFormat.toString();
	} else if (decimalSeparator === '.' || decimalSeparator === ',') {
		return (numberToFormat || 0).toString().replace('.', decimalSeparator);
	}

	const options = {
		minimumFractionDigits: minimumFractionDigits || 0,
	};

	try {
		return new Intl.NumberFormat(decimalSeparator, options).format(
			numberToFormat
		);
	} catch (error) {
		// Fallback to simple string conversion if formatting fails
		return numberToFormat.toString();
	}
}

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
