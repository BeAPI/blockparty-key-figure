import { registerBlockType } from '@wordpress/blocks';
import './style.scss';
import Edit from './edit';
import save from './save';
import metadata from './block.json';
import { Icon, keyFigure } from '@beapi/icons';

// Deprecations for different versions of the block
const deprecated = [
	// v1 - Original format without data attributes (blocks prior 1.0.4)
	{
		attributes: {
			align: {
				type: 'string',
			},
			decimalSeparator: {
				type: 'string',
				default: 'none',
			},
			minimumFractionDigits: {
				type: 'number',
				default: 3,
			},
			prefix: {
				type: 'string',
				source: 'text',
				selector: '.wp-block-blockparty-key-figure__prefix',
				default: '+',
			},
			number: {
				type: 'string',
				source: 'attribute',
				selector: '.wp-block-blockparty-key-figure__number',
				attribute: 'data-increment',
				default: '100',
			},
			formattedNumber: {
				type: 'string',
				source: 'text',
				selector: '.wp-block-blockparty-key-figure__number',
			},
			suffix: {
				type: 'string',
				source: 'text',
				selector: '.wp-block-blockparty-key-figure__suffix',
				default: '%',
			},
			description: {
				type: 'string',
				source: 'html',
				selector: '.wp-block-blockparty-key-figure__description',
			},
		},
		save({ attributes }) {
			const {
				align,
				prefix,
				number,
				formattedNumber,
				suffix,
				description,
			} = attributes;

			return (
				<div
					className={
						align
							? `wp-block-blockparty-key-figure has-text-align-${align}`
							: 'wp-block-blockparty-key-figure'
					}
				>
					<div className="wp-block-blockparty-key-figure__key">
						<span className="wp-block-blockparty-key-figure__prefix">
							{prefix}
						</span>
						<span
							className="wp-block-blockparty-key-figure__number"
							data-increment={number}
						>
							{formattedNumber || number}
						</span>
						<span className="wp-block-blockparty-key-figure__suffix">
							{suffix}
						</span>
					</div>
					{description && (
						<p
							className="wp-block-blockparty-key-figure__description"
							dangerouslySetInnerHTML={{ __html: description }}
						/>
					)}
				</div>
			);
		},
		isEligible(attributes, innerBlocks, { innerHTML }) {
			// Check for original format without any data attributes
			return (
				innerHTML &&
				!innerHTML.includes('data-decimal-separator') &&
				!innerHTML.includes('data-minimum-fraction-digits')
			);
		},
		migrate(attributes) {
			// Remove the formattedNumber attribute as it's not needed in the new version
			const { formattedNumber, ...newAttributes } = attributes;
			return newAttributes;
		},
	},
];

registerBlockType(metadata.name, {
	icon: <Icon icon={keyFigure} />,
	edit: Edit,
	save,
	deprecated,
});
