import { addons } from '@storybook/addons';
import { create } from '@storybook/theming/create';
import logo from './../src/static/fastybird_row.svg';

const theme = create({
	brandImage: `${logo}`,
	brandTitle: 'FastyBird UI - Storybook',
});

addons.setConfig({
	theme,
});
