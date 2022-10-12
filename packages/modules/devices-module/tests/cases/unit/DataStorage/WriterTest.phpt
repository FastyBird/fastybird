<?php declare(strict_types = 1);

namespace Tests\Cases\Unit;

use FastyBird\DevicesModule;
use FastyBird\DevicesModule\DataStorage;
use League\Flysystem;
use Mockery;
use Tester\Assert;
use Tests\Tools;

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../DbTestCase.php';

/**
 * @testCase
 */
final class WriterTest extends DbTestCase
{

	public function testWriteConfiguration(): void
	{
		$filesystem = Mockery::mock(Flysystem\Filesystem::class);
		$filesystem
			->shouldReceive('write')
			->withArgs(function (string $filename, string $data): bool {
				Assert::same(DevicesModule\Constants::CONFIGURATION_FILE_FILENAME, $filename);

				Tools\JsonAssert::assertFixtureMatch(
					__DIR__ . '/../../../fixtures/DataStorage/devices-module-data.json',
					$data
				);

				return true;
			});

		$this->mockContainerService(
			Flysystem\Filesystem::class,
			$filesystem
		);

		$writer = $this->getContainer()->getByType(DataStorage\Writer::class);
		$writer->write();
	}

}

$test_case = new WriterTest();
$test_case->run();
