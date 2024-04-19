import '@fastybird/web-ui-theme-chalk/src/index.scss';
import '@fastybird/web-ui-theme-chalk/src/reset.scss';
import '@fastybird/web-ui-theme-chalk/src/dark/css-vars.scss';

import './storybook-styles.css';

export const parameters = {
	viewMode: 'docs',
	controls: {
		matchers: {
			color: /(background|color)$/i,
			date: /Date$/,
		},
	},
	backgrounds: {
		disable: true,
	},
	darkMode: {
		darkClass: 'dark',
		lightClass: 'light',
		stylePreview: true,
		classTarget: 'html',
	},
};
