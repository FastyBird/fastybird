import path from 'path';
import { emptyDir, ensureDir, mkdir, readFile, readdir, copyFile, lstat, writeFile } from 'fs-extra';
import consola from 'consola';
import chalk from 'chalk';

import { packages } from './paths';

try {
	consola.info(chalk.blue('generating web ui library'));

	for (const name of Object.keys(packages)) {
		await ensureDir(packages[name].dest);
		await emptyDir(packages[name].dest);
	}

	consola.info(chalk.blue('generating web ui packages'));
	await Promise.all(Object.keys(packages).map((name) => copyPackage(packages[name].dest, packages[name].src)));

	consola.info(chalk.blue('updating packages import'));

	for (const name of Object.keys(packages)) {
		await replaceImports(packages[name].dest, packages[name].name); // Replace imports in the top-level directory
	}

	async function copyPackage(destination: string, source: string): Promise<void> {
		await mkdir(destination, { recursive: true });

		const files = await readdir(source);

		for (const file of files) {
			const sourcePath = path.join(source, file);
			const destPath = path.join(destination, file);

			const info = await lstat(sourcePath);

			if (info.isDirectory()) {
				await copyPackage(sourcePath, destPath);
			} else {
				await copyFile(sourcePath, destPath);
			}
		}
	}

	async function replaceImports(directory: string, packageName: string, relativePath?: string): Promise<void> {
		const files = await readdir(directory);

		for (const file of files) {
			const filePath = path.join(directory, file);

			const info = await lstat(filePath);

			if (info.isDirectory()) {
				const newRelativePath = relativePath ? path.join(relativePath, file) : file;

				await replaceImports(filePath, packageName, newRelativePath);
			} else if (filePath.endsWith('.ts') || filePath.endsWith('.vue')) {
				let content = await readFile(filePath, 'utf8');

				content = content.replace(
					new RegExp(`import\\s+\\{\\s*(\\w+)\\s*\\}\\s+from\\s+(['"])${packageName}\\2`, 'g'),
					`import { $1 } from '${relativePath ? `./${relativePath}/${file}` : `./${file}`}';`
				);

				await writeFile(filePath, content);
			}
		}
	}
} catch (e) {
	console.log(e);
}
