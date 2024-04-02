import { useBlockProps, RichText } from '@wordpress/block-editor';

import classnames from 'classnames';

export default function save( { attributes } ) {
	const {
		align,
		prefix,
		number,
		decimalSeparator,
		minimumFractionDigits,
		suffix,
		description,
	} = attributes;

	const blockProps = useBlockProps.save( {
		className: classnames( {
			[ `has-text-align-${ align }` ]: align,
		} ),
	} );

	const formattedNumber = ( numberToFormat ) => {
		if ( 'none' === decimalSeparator ) {
			return numberToFormat;
		} else if ( '.' === decimalSeparator || ',' === decimalSeparator ) {
			return ( numberToFormat || 0 )
				.toString()
				.replace( '.', decimalSeparator );
		}

		const options = {
			minimumFractionDigits,
		};
		return new Intl.NumberFormat( decimalSeparator, options ).format(
			numberToFormat
		);
	};

	return (
		<div { ...blockProps }>
			<div className="wp-block-blockparty-key-figure__key">
				<span className="wp-block-blockparty-key-figure__prefix">
					{ prefix }
				</span>
				<span
					className="wp-block-blockparty-key-figure__number"
					data-increment={ number }
				>
					{ formattedNumber( number ) }
				</span>
				<span className="wp-block-blockparty-key-figure__suffix">
					{ suffix }
				</span>
			</div>
			<RichText.Content
				tagName="p"
				className="wp-block-blockparty-key-figure__description"
				value={ description }
			/>
		</div>
	);
}
