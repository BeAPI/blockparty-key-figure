import { registerBlockType } from '@wordpress/blocks';
import './style.scss';
import Edit from './edit';
import save from './save';
import metadata from './block.json';
import { Icon, keyFigure } from '@beapi/icons';

registerBlockType( metadata.name, {
	icon: <Icon icon={ keyFigure } />,
	edit: Edit,
	save,
} );
