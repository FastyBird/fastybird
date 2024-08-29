<?php

declare(strict_types = 1);

use Symplify\MonorepoBuilder\ComposerJsonManipulator;
use Symplify\MonorepoBuilder\Config\MBConfig;
use Symplify\MonorepoBuilder\Release\ReleaseWorker\PushNextDevReleaseWorker;
use Symplify\MonorepoBuilder\Release\ReleaseWorker\PushTagReleaseWorker;
use Symplify\MonorepoBuilder\Release\ReleaseWorker\SetCurrentMutualDependenciesReleaseWorker;
use Symplify\MonorepoBuilder\Release\ReleaseWorker\SetNextMutualDependenciesReleaseWorker;
use Symplify\MonorepoBuilder\Release\ReleaseWorker\TagVersionReleaseWorker;
use Symplify\MonorepoBuilder\Release\ReleaseWorker\UpdateBranchAliasReleaseWorker;
use Symplify\MonorepoBuilder\Release\ReleaseWorker\UpdateReplaceReleaseWorker;

return static function (MBConfig $mbConfig): void {
	$mbConfig->packageDirectories([
		__DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'FastyBird',
		__DIR__ . DIRECTORY_SEPARATOR . 'app',
	]);
	$mbConfig->defaultBranch('main');

	$mbConfig->dataToAppend([
		ComposerJsonManipulator\ValueObject\ComposerJsonSection::REQUIRE => [
			'fastybird/datetime-factory' => '^0.7',
			'fastybird/json-api' => '^0.19',
			'fastybird/simple-auth' => '^0.14',
		],
	]);

	$mbConfig->dataToRemove([
		ComposerJsonManipulator\ValueObject\ComposerJsonSection::MINIMUM_STABILITY => 'dev',
		ComposerJsonManipulator\ValueObject\ComposerJsonSection::PREFER_STABLE => true,
	]);

	$mbConfig->workers([
		// release workers - in order to execute
		UpdateReplaceReleaseWorker::class,
		SetCurrentMutualDependenciesReleaseWorker::class,
		TagVersionReleaseWorker::class,
		PushTagReleaseWorker::class,
		SetNextMutualDependenciesReleaseWorker::class,
		UpdateBranchAliasReleaseWorker::class,
		PushNextDevReleaseWorker::class,
	]);
};
