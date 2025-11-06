import { useBlockProps, RichText } from '@wordpress/block-editor';

import classnames from 'classnames';

export default function save({ attributes }) {
	const {
		align,
		prefix,
		number,
		decimalSeparator,
		minimumFractionDigits,
		suffix,
		description,
	} = attributes;

	const blockProps = useBlockProps.save({
		className: classnames({
			[`has-text-align-${align}`]: align,
		}),
	});

	return (
		<div {...blockProps}>
			<div className="wp-block-blockparty-key-figure__key">
				<span className="wp-block-blockparty-key-figure__prefix">
					{prefix}
				</span>
				<span
					className="wp-block-blockparty-key-figure__number"
					data-increment={number}
					data-decimal-separator={decimalSeparator}
					data-minimum-fraction-digits={minimumFractionDigits}
				>
					{number}
				</span>
				<span className="wp-block-blockparty-key-figure__suffix">
					{suffix}
				</span>
			</div>
			<RichText.Content
				tagName="p"
				className="wp-block-blockparty-key-figure__description"
				value={description}
			/>
		</div>
	);
}
