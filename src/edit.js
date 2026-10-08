import { __ } from '@wordpress/i18n';
import { useEffect, useRef } from '@wordpress/element';

import {
	useBlockProps,
	RichText,
	AlignmentToolbar,
	BlockControls,
	InspectorControls,
} from '@wordpress/block-editor';

import {
	PanelBody,
	TextControl,
	SelectControl,
	__experimentalNumberControl as NumberControl, // eslint-disable-line
} from '@wordpress/components';

import classnames from 'classnames';

import { formatNumber } from './utils/format-number';

import './editor.scss';

export default function Edit({ attributes, setAttributes }) {
	const {
		align,
		number,
		decimalSeparator,
		minimumFractionDigits,
		prefix,
		suffix,
		description,
	} = attributes;

	const numberRef = useRef();
	const formattedNumber = formatNumber(
		number,
		decimalSeparator,
		minimumFractionDigits
	);

	// Update the number display when formatting changes
	useEffect(() => {
		if (numberRef.current) {
			numberRef.current.textContent = formattedNumber;
		}
	}, [formattedNumber]);

	const blockProps = useBlockProps({
		className: classnames({
			[`has-text-align-${align}`]: align,
		}),
	});

	const onChangeAlign = (newAlign) => {
		setAttributes({ align: newAlign });
	};

	const onChangePrefix = (newPrefix) => {
		setAttributes({ prefix: newPrefix });
	};

	const onChangeNumber = (newNumber) => {
		setAttributes({ number: newNumber });
	};

	const onChangeSuffix = (newSuffix) => {
		setAttributes({ suffix: newSuffix });
	};

	const onChangeDescription = (newDescription) => {
		setAttributes({ description: newDescription });
	};

	const onChangeDecimalSeparator = (newDecimalSeparator) => {
		setAttributes({ decimalSeparator: newDecimalSeparator });
	};

	const onChangeMinimumFractionDigits = (newMinimumFractionDigits) => {
		setAttributes({ minimumFractionDigits: newMinimumFractionDigits });
	};

	return (
		<>
			<InspectorControls>
				<PanelBody title={__('Settings')} initialOpen={true}>
					<TextControl
						label={__('Prefix', 'blockparty-key-figure')}
						value={prefix}
						onChange={onChangePrefix}
					/>
					<NumberControl
						label={__('Number', 'blockparty-key-figure')}
						isShiftStepEnabled={false}
						value={number}
						onChange={onChangeNumber}
					/>
					<TextControl
						label={__('Suffix', 'blockparty-key-figure')}
						value={suffix}
						onChange={onChangeSuffix}
					/>
					<SelectControl
						label={__(
							'Thousands and decimal format',
							'blockparty-key-figure'
						)}
						help={__(
							'Enter your number using a dot or a comma as decimal separator (eg. 123456.789)',
							'blockparty-key-figure'
						)}
						value={decimalSeparator}
						options={[
							{
								label: __(
									'None (123456.789)',
									'blockparty-key-figure'
								),
								value: 'none',
							},
							{
								label: __(
									'Space and comma (123 456,789)',
									'blockparty-key-figure'
								),
								value: 'fr-FR',
							},
							{
								label: __(
									'Comma and dot (123,456.789)',
									'blockparty-key-figure'
								),
								value: 'en-EN',
							},
							{
								label: __(
									'Dot and comma (123.456,789)',
									'blockparty-key-figure'
								),
								value: 'de-DE',
							},
						]}
						onChange={onChangeDecimalSeparator}
					/>
					{'none' !== decimalSeparator &&
						'.' !== decimalSeparator &&
						',' !== decimalSeparator && (
							<NumberControl
								label={__(
									'Minimum number of fraction digits',
									'blockparty-key-figure'
								)}
								isShiftStepEnabled={true}
								shiftStep={1}
								max={20}
								min={0}
								value={minimumFractionDigits}
								onChange={onChangeMinimumFractionDigits}
							/>
						)}
				</PanelBody>
			</InspectorControls>

			<BlockControls>
				<AlignmentToolbar value={align} onChange={onChangeAlign} />
			</BlockControls>

			<div {...blockProps}>
				<p className="wp-block-blockparty-key-figure__key">
					<span className="wp-block-blockparty-key-figure__prefix">
						{prefix}
					</span>
					<span
						ref={numberRef}
						className="wp-block-blockparty-key-figure__number"
						data-increment={number}
						data-decimal-separator={decimalSeparator}
						data-minimum-fraction-digits={minimumFractionDigits}
					>
						{formattedNumber}
					</span>
					<span className="wp-block-blockparty-key-figure__suffix">
						{suffix}
					</span>
				</p>
				<RichText
					tagName="p"
					className="wp-block-blockparty-key-figure__description"
					placeholder={__(
						'Description of key figure',
						'blockparty-key-figure'
					)}
					allowedFormats={[
						'core/bold',
						'core/italic',
						'core/link',
						'core/text-color',
					]}
					value={description}
					onChange={onChangeDescription}
				/>
			</div>
		</>
	);
}
