import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const dir = dirname(fileURLToPath(import.meta.url));

export const pathRoot = resolve(dir, '..');
export const pathSrc = resolve(pathRoot, 'src');
export const packagesPathRoot = resolve(pathRoot, '..', 'packages');

export const packages: { [key: string]: { name: string; src: string; dest: string; relativeDest: string } } = {
	components: {
		name: '@fastybird/web-ui-components',
		src: resolve(packagesPathRoot, 'components', 'src'),
		dest: resolve(pathSrc, 'components'),
		relativeDest: './components/entry',
	},
	utils: {
		name: '@fastybird/web-ui-utils',
		src: resolve(packagesPathRoot, 'utils', 'src'),
		dest: resolve(pathSrc, 'utils'),
		relativeDest: './utils/entry',
	},
};
