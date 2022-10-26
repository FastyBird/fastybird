<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\DataStorage;

use Exception;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\DataStorage;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Tests\Cases\Unit\DbTestCase;
use FastyBird\Module\Devices\Tests\Tools;
use League\Flysystem;
use Nette;
use RuntimeException;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class WriterTest extends DbTestCase
{

	/**
	 * @throws Exception
	 * @throws Exceptions\InvalidArgument
	 * @throws Flysystem\FilesystemException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 */
	public function testWriteConfiguration(): void
	{
		$filesystem = $this->createMock(Flysystem\Filesystem::class);
		$filesystem
			->method('write')
			->with(
				Devices\Constants::CONFIGURATION_FILE_FILENAME,
				self::callback(static function ($data): bool {
					Tools\JsonAssert::assertFixtureMatch(
						__DIR__ . '/../../../fixtures/DataStorage/devices-module-data.json',
						$data,
					);

					return true;
				}),
			);
		$filesystem
			->method('read')
			->with(Devices\Constants::CONFIGURATION_FILE_FILENAME)
			->willReturn(
				Nette\Utils\FileSystem::read(__DIR__ . '/../../../fixtures/DataStorage/devices-module-data.json'),
			);

		$this->mockContainerService(Flysystem\Filesystem::class, $filesystem);

		$writer = $this->getContainer()->getByType(DataStorage\Writer::class);
		$writer->write();
	}

}
