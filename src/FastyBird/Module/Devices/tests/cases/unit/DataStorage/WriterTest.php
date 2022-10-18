<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\DataStorage;

use FastyBird\Module\Devices;
use FastyBird\Module\Devices\DataStorage;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Tests\Cases\Unit\DbTestCase;
use FastyBird\Module\Devices\Tests\Tools;
use League\Flysystem;
use Nette;
use RuntimeException;

final class WriterTest extends DbTestCase
{

	/**
	 * @throws Exceptions\InvalidArgument
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

		$this->mockContainerService(Flysystem\Filesystem::class, $filesystem);

		$writer = $this->getContainer()->getByType(DataStorage\Writer::class);
		$writer->write();
	}

}
