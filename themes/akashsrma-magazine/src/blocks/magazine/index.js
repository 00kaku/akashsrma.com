/**
 * Editor side of the Magazine block: it has no settings, so the editor shows the server rendering.
 */
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';

import metadata from './block.json';
import './style.scss';
import './editor.scss';

function Edit() {
	return (
		<div { ...useBlockProps() }>
			<ServerSideRender block={ metadata.name } />
		</div>
	);
}

registerBlockType( metadata.name, { edit: Edit } );
