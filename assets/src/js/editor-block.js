import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

registerBlockType(
	'bloom-ux/wp-cpt-events',
	{
		edit: () => {
			return (
				<p>
					{__('Events for everybody, FTW!', 'bloom-ux-events')}
				</p>
			)
		},
		save: () => null
	}
);
