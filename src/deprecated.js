// Deprecations for different versions of the block

const BLOCK_CLASS = 'wp-block-blockparty-key-figure';
const KEY_CLASS = 'wp-block-blockparty-key-figure__key';
const PREFIX_CLASS = 'wp-block-blockparty-key-figure__prefix';
const NUMBER_CLASS = 'wp-block-blockparty-key-figure__number';
const SUFFIX_CLASS = 'wp-block-blockparty-key-figure__suffix';
const DESCRIPTION_CLASS = 'wp-block-blockparty-key-figure__description';

const KEY_WRAPPER_DIV_REGEX =
	/<div[^>]*class="[^"]*wp-block-blockparty-key-figure__key/;

const baseAttributes = {
	align: { type: 'string' },
	prefix: {
		type: 'string',
		source: 'text',
		selector: `.${PREFIX_CLASS}`,
		default: '+',
	},
	number: {
		type: 'string',
		source: 'attribute',
		selector: `.${NUMBER_CLASS}`,
		attribute: 'data-increment',
		default: '100',
	},
	suffix: {
		type: 'string',
		source: 'text',
		selector: `.${SUFFIX_CLASS}`,
		default: '%',
	},
	description: {
		type: 'string',
		source: 'html',
		selector: `.${DESCRIPTION_CLASS}`,
	},
};

const createDeprecatedSave =
	({
		keyTagName = 'div',
		withDataAttributes = false,
		withFormattedNumber = false,
	}) =>
	({ attributes }) => {
		const {
			align,
			prefix,
			number,
			decimalSeparator,
			minimumFractionDigits,
			formattedNumber,
			suffix,
			description,
		} = attributes;

		const wrapperClassName = align
			? `${BLOCK_CLASS} has-text-align-${align}`
			: BLOCK_CLASS;

		const KeyWrapper = keyTagName;

		return (
			<div className={wrapperClassName}>
				<KeyWrapper className={KEY_CLASS}>
					<span className={PREFIX_CLASS}>{prefix}</span>
					<span
						className={NUMBER_CLASS}
						data-increment={number}
						{...(withDataAttributes
							? {
									'data-decimal-separator': decimalSeparator,
									'data-minimum-fraction-digits':
										minimumFractionDigits,
								}
							: {})}
					>
						{withFormattedNumber
							? formattedNumber || number
							: number}
					</span>
					<span className={SUFFIX_CLASS}>{suffix}</span>
				</KeyWrapper>
				{description && (
					<p
						className={DESCRIPTION_CLASS}
						dangerouslySetInnerHTML={{ __html: description }}
					/>
				)}
			</div>
		);
	};

const deprecated = [
	// v2 - Markup change: __key wrapper was div, now p (a11y/semantic)
	{
		attributes: {
			...baseAttributes,
			decimalSeparator: {
				type: 'string',
				source: 'attribute',
				selector: `.${NUMBER_CLASS}`,
				attribute: 'data-decimal-separator',
				default: 'none',
			},
			minimumFractionDigits: {
				type: 'string',
				source: 'attribute',
				selector: `.${NUMBER_CLASS}`,
				attribute: 'data-minimum-fraction-digits',
				default: '3',
			},
		},
		save: createDeprecatedSave({
			keyTagName: 'div',
			withDataAttributes: true,
			withFormattedNumber: false,
		}),
		isEligible(attributes, innerBlocks, { innerHTML }) {
			return (
				innerHTML &&
				innerHTML.includes('data-decimal-separator') &&
				KEY_WRAPPER_DIV_REGEX.test(innerHTML)
			);
		},
		migrate: (attributes) => attributes,
	},
	// v1 - Original format without data attributes (blocks prior 1.0.4)
	{
		attributes: {
			...baseAttributes,
			decimalSeparator: { type: 'string', default: 'none' },
			minimumFractionDigits: { type: 'number', default: 3 },
			formattedNumber: {
				type: 'string',
				source: 'text',
				selector: `.${NUMBER_CLASS}`,
			},
		},
		save: createDeprecatedSave({
			keyTagName: 'div',
			withDataAttributes: false,
			withFormattedNumber: true,
		}),
		isEligible(attributes, innerBlocks, { innerHTML }) {
			return (
				innerHTML &&
				!innerHTML.includes('data-decimal-separator') &&
				!innerHTML.includes('data-minimum-fraction-digits')
			);
		},
		migrate({ formattedNumber, ...rest }) {
			return rest;
		},
	},
];

export default deprecated;
