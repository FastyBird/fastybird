import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const dir = dirname(fileURLToPath(import.meta.url));

export const pathRoot = resolve(dir, '..');
export const pathSrc = resolve(pathRoot, 'src');
export const packagesPathRoot = resolve(pathRoot, '..');

export const packages: { [key: string]: { name: string; src: string; dest: string; relativeDest: string } } = {
	components: {
		name: '@fastybird/web-ui-components',
		src: resolve(packagesPathRoot, 'components', 'src'),
		dest: resolve(pathSrc, 'components'),
		relativeDest: './components/entry',
	},
	constants: {
		name: '@fastybird/web-ui-constants',
		src: resolve(packagesPathRoot, 'constants', 'src'),
		dest: resolve(pathSrc, 'constants'),
		relativeDest: './constants/entry',
	},
	directives: {
		name: '@fastybird/web-ui-directives',
		src: resolve(packagesPathRoot, 'directives', 'src'),
		dest: resolve(pathSrc, 'directives'),
		relativeDest: './directives/entry',
	},
	hooks: {
		name: '@fastybird/web-ui-hooks',
		src: resolve(packagesPathRoot, 'hooks', 'src'),
		dest: resolve(pathSrc, 'hooks'),
		relativeDest: './hooks/entry',
	},
	locale: {
		name: '@fastybird/web-ui-locale',
		src: resolve(packagesPathRoot, 'locale', 'src'),
		dest: resolve(pathSrc, 'locale'),
		relativeDest: './locale',
	},
	utils: {
		name: '@fastybird/web-ui-utils',
		src: resolve(packagesPathRoot, 'utils', 'src'),
		dest: resolve(pathSrc, 'utils'),
		relativeDest: './utils/entry',
	},
};
