import { addons } from '@storybook/manager-api';
import { create } from '@storybook/theming/create';

import logo from './../src/assets/fastybird_row.svg';

const theme = create({
	brandImage: `${logo}`,
	brandTitle: 'FastyBird UI - Storybook',
});

addons.setConfig({
	theme,
	sidebar: {
		filters: {
			patterns: (item) => {
				return !(item.tags ?? []).includes('hideInSidebar');
			},
		},
	},
});
