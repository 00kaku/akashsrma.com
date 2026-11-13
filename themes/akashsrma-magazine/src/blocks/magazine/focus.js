/**
 * Crop focus for Image blocks: the point a cropped picture keeps in view (a face, say), set by dragging a
 * dot in the block's sidebar. The point is a block attribute, so the saved HTML doesn't change and older
 * images stay valid; Setup\ImageFocus prints it on the front end as the --mz-focus custom property, which
 * the magazine's image styles use as their object-position.
 */
import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { InspectorControls } from '@wordpress/block-editor';
import { Button, FocalPointPicker, PanelBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const BLOCK = 'core/image';
const CENTRE = { x: 0.5, y: 0.5 };

/**
 * The point as a CSS position, e.g. "50% 20%".
 *
 * @param {{x: number, y: number}} focus Point, each axis from 0 to 1.
 * @return {string} CSS position.
 */
const toPosition = ( { x, y } ) =>
	`${ Math.round( x * 100 ) }% ${ Math.round( y * 100 ) }%`;

addFilter(
	'blocks.registerBlockType',
	'magazine/image-focus/attribute',
	( settings, name ) =>
		BLOCK === name
			? {
					...settings,
					attributes: {
						...settings.attributes,
						magazineFocus: { type: 'object' },
					},
				}
			: settings
);

const withFocusControl = createHigherOrderComponent(
	( BlockEdit ) => ( props ) => {
		if ( BLOCK !== props.name ) {
			return <BlockEdit { ...props } />;
		}
		const { url, magazineFocus } = props.attributes;
		const setFocus = ( value ) =>
			props.setAttributes( { magazineFocus: value } );

		return (
			<>
				<BlockEdit { ...props } />
				{ url && (
					<InspectorControls>
						<PanelBody
							title={ __( 'Crop focus', 'magazine' ) }
							initialOpen={ !! magazineFocus }
						>
							<FocalPointPicker
								__nextHasNoMarginBottom
								label={ __(
									'The point a cropped picture keeps in view',
									'magazine'
								) }
								url={ url }
								value={ magazineFocus ?? CENTRE }
								onChange={ setFocus }
							/>
							{ magazineFocus && (
								<Button
									variant="secondary"
									size="small"
									onClick={ () => setFocus( undefined ) }
								>
									{ __( 'Reset to centre', 'magazine' ) }
								</Button>
							) }
						</PanelBody>
					</InspectorControls>
				) }
			</>
		);
	},
	'withMagazineImageFocusControl'
);

addFilter(
	'editor.BlockEdit',
	'magazine/image-focus/control',
	withFocusControl
);

// The same custom property on the block's wrapper in the editor, so the crop previews as it will look.
const withFocusStyle = createHigherOrderComponent(
	( BlockListBlock ) => ( props ) => {
		const focus = BLOCK === props.name && props.attributes.magazineFocus;
		if ( ! focus ) {
			return <BlockListBlock { ...props } />;
		}
		const wrapperProps = {
			...props.wrapperProps,
			style: {
				...props.wrapperProps?.style,
				'--mz-focus': toPosition( focus ),
			},
		};
		return <BlockListBlock { ...props } wrapperProps={ wrapperProps } />;
	},
	'withMagazineImageFocusStyle'
);

addFilter(
	'editor.BlockListBlock',
	'magazine/image-focus/style',
	withFocusStyle
);
