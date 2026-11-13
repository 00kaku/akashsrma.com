/**
 * Editor side of the Code & live links block. The links are post fields (code_url, live_url,
 * is_private), edited in the block's sidebar; the front end is rendered by render.php.
 */
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, TextControl, ToggleControl } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { __ } from '@wordpress/i18n';

import metadata from './block.json';
import './style.scss';

function Edit( { context: { postId, postType } } ) {
	const [ meta = {}, setMeta ] = useEntityProp(
		'postType',
		postType,
		'meta',
		postId
	);
	const set = ( key ) => ( value ) => setMeta( { ...meta, [ key ]: value } );

	const links = [
		meta.code_url && __( 'Code →', 'akashsrma-features' ),
		meta.live_url &&
			`${ meta.live_label || __( 'Live', 'akashsrma-features' ) } →`,
	].filter( Boolean );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Links', 'akashsrma-features' ) }>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						type="url"
						label={ __( 'Code URL', 'akashsrma-features' ) }
						value={ meta.code_url || '' }
						onChange={ set( 'code_url' ) }
					/>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						type="url"
						label={ __( 'Live URL', 'akashsrma-features' ) }
						value={ meta.live_url || '' }
						onChange={ set( 'live_url' ) }
					/>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Live link label', 'akashsrma-features' ) }
						help={ __(
							'For example “Live site” or “Play it”.',
							'akashsrma-features'
						) }
						placeholder={ __( 'Live', 'akashsrma-features' ) }
						value={ meta.live_label || '' }
						onChange={ set( 'live_label' ) }
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __(
							'Private client work',
							'akashsrma-features'
						) }
						checked={ !! meta.is_private }
						onChange={ set( 'is_private' ) }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...useBlockProps() }>
				{ meta.is_private && (
					<p className="project-links__note">
						{ __(
							'Code and site private — client work',
							'akashsrma-features'
						) }
					</p>
				) }
				{ ! meta.is_private &&
					( links.length
						? links.map( ( label ) => (
								<span
									key={ label }
									className="project-links__a"
								>
									{ label }
								</span>
							) )
						: __(
								'Add the code and live URLs in the block sidebar.',
								'akashsrma-features'
							) ) }
			</div>
		</>
	);
}

registerBlockType( metadata.name, { edit: Edit } );
