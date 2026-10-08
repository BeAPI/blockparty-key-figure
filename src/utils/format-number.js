/**
 * Intl.NumberFormat (e.g. fr-FR) may insert U+202F (narrow no-break space) as the
 * thousands separator. Some fonts in Chromium ignore letter-spacing on text that
 * contains U+202F. Thin space (U+2009) keeps grouped digits readable and allows
 * CSS tracking (letter-spacing).
 */
const INTNL_NARROW_NO_BREAK_SPACE = /\u202f/g;
const THIN_SPACE = '\u2009';

/**
 * @param {string} formatted Formatted number string from Intl.
 * @return {string} Formatted number with thousands separators safe for CSS tracking.
 */
function normalizeIntlFormattedNumber(formatted) {
	return formatted.replace(INTNL_NARROW_NO_BREAK_SPACE, THIN_SPACE);
}

/**
 * @param {number}        numberToFormat        Value to format.
 * @param {string}        decimalSeparator      Locale id, "none", or literal "." / "," separator.
 * @param {number|string} minimumFractionDigits Minimum fraction digits for Intl locales.
 * @return {string|number|undefined}   Formatted value, or the input when formatting is skipped.
 */
export function formatNumber(
	numberToFormat,
	decimalSeparator,
	minimumFractionDigits
) {
	if (!numberToFormat && numberToFormat !== 0) {
		return numberToFormat;
	}

	if (decimalSeparator === 'none') {
		return numberToFormat.toString();
	}

	if (decimalSeparator === '.' || decimalSeparator === ',') {
		return (numberToFormat || 0).toString().replace('.', decimalSeparator);
	}

	const options = {
		minimumFractionDigits: minimumFractionDigits || 0,
	};

	try {
		return normalizeIntlFormattedNumber(
			new Intl.NumberFormat(decimalSeparator, options).format(
				numberToFormat
			)
		);
	} catch (error) {
		return numberToFormat.toString();
	}
}
