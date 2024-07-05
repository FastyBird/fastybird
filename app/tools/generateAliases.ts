import fs from 'fs';
import path from 'path';

const skipPackages: string[] = ['@fastybird/metadata-library', '@fastybird/web-ui'];

const findEntryFile = (dir: string): string | null => {
	const files: string[] = fs.readdirSync(dir);

	for (const file of files) {
		const filePath = path.join(dir, file);
		const stat = fs.statSync(filePath);

		if (stat.isDirectory()) {
			const entryFilePath: string = findEntryFile(filePath);

			if (entryFilePath) {
				return entryFilePath;
			}
		} else if (file === 'entry.ts') {
			return filePath;
		}
	}

	return null;
};

const findPackageJsonFiles = (dir: string): { [key: string]: string } => {
	const files = fs.readdirSync(dir);
	const aliases = {};

	files.forEach((file) => {
		const filePath: string = path.join(dir, file);
		const stat: fs.Stats = fs.statSync(filePath);

		if (stat.isDirectory()) {
			// Recursively search subdirectories
			const subAliases: { [key: string]: string } = findPackageJsonFiles(filePath);

			Object.assign(aliases, subAliases);
		} else if (file === 'package.json') {
			// Read package.json to get entry file and package name
			const packageJson = JSON.parse(fs.readFileSync(filePath, 'utf-8'));
			const aliasName: string = packageJson.name;

			// Skip packages that are in the skipPackages array
			if (skipPackages.includes(aliasName)) {
				return;
			}

			const entryFilePath: string = findEntryFile(path.dirname(filePath));

			if (fs.existsSync(entryFilePath)) {
				aliases[aliasName] = entryFilePath;
			}
		}
	});

	return aliases;
};

// Base directory for modules
const baseDir = path.resolve(__dirname, './../../src');

// Generate aliases
const aliases = findPackageJsonFiles(baseDir);

export default aliases;
