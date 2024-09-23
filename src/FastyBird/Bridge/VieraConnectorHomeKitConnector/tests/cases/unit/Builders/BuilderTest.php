<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\Tests\Cases\Unit\Builders;

use Error;
use FastyBird\Bridge\VieraConnectorHomeKitConnector\Builders;
use FastyBird\Bridge\VieraConnectorHomeKitConnector\Entities;
use FastyBird\Bridge\VieraConnectorHomeKitConnector\Exceptions;
use FastyBird\Bridge\VieraConnectorHomeKitConnector\Tests;
use FastyBird\Connector\HomeKit\Entities as HomeKitEntities;
use FastyBird\Connector\Viera\Entities as VieraEntities;
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use IPub\DoctrineCrud\Exceptions as DoctrineCrudExceptions;
use Nette\DI;
use Nette\Utils;
use RuntimeException;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class BuilderTest extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 * @throws DI\MissingServiceException
	 * @throws DoctrineCrudExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Error
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws Utils\JsonException
	 * @throws RuntimeException
	 *
	 * @dataProvider builder
	 */
	public function testBuild(
		string $vieraIdentifier,
		int $expectedChannelsCnt,
		string $expectedChannels,
		string $expectedChannelsProperties,
	): void
	{
		$builder = $this->getContainer()->getByType(Builders\Builder::class);

		$connectorsRepository = $this->getContainer()->getByType(
			DevicesModels\Entities\Connectors\ConnectorsRepository::class,
		);

		$devicesRepository = $this->getContainer()->getByType(DevicesModels\Entities\Devices\DevicesRepository::class);

		$findConnectorQuery = new DevicesQueries\Entities\FindConnectors();
		$findConnectorQuery->byIdentifier('homekit');

		$connector = $connectorsRepository->findOneBy($findConnectorQuery);

		self::assertInstanceOf(HomeKitEntities\Connectors\Connector::class, $connector);

		$findVieraQuery = new DevicesQueries\Entities\FindDevices();
		$findVieraQuery->byIdentifier($vieraIdentifier);

		$viera = $devicesRepository->findOneBy($findVieraQuery, VieraEntities\Devices\Device::class);

		self::assertInstanceOf(VieraEntities\Devices\Device::class, $viera);

		$bridge = $builder->build($viera, $connector);

		self::assertSame($viera, $bridge->getParent());

		self::assertCount($expectedChannelsCnt, $bridge->getChannels());
		self::assertInstanceOf(Entities\Channels\Viera::class, $bridge->getChannels()[0]);

		$actual = [];

		foreach ($bridge->getChannels() as $channel) {
			$channelData = $channel->toArray();

			unset($channelData['id']);
			unset($channelData['properties']);
			unset($channelData['controls']);
			unset($channelData['device']);

			$actual[$channel->getIdentifier()] = $channelData;
		}

		Tests\Tools\JsonAssert::assertFixtureMatch(
			$expectedChannels,
			Utils\Json::encode($actual),
		);

		$actual = [];

		foreach ($bridge->getChannels() as $channel) {
			$actual[$channel->getIdentifier()] = [];

			foreach ($channel->getProperties() as $property) {
				$propertyData = $property->toArray();

				unset($propertyData['id']);
				unset($propertyData['channel']);

				$actual[$channel->getIdentifier()][$property->getIdentifier()] = $propertyData;
			}
		}

		Tests\Tools\JsonAssert::assertFixtureMatch(
			$expectedChannelsProperties,
			Utils\Json::encode($actual),
		);
	}

	/**
	 * @return array<string, array<string|int|array<mixed>|null>>
	 */
	public static function builder(): array
	{
		return [
			'Viera 1 - edit' => [
				'4D454930-0200-1000-8001-80C755230D19',
				13,
				__DIR__ . '/../../../fixtures/Builders/device.1.channels.json',
				__DIR__ . '/../../../fixtures/Builders/device.1.channels.properties.json',
			],
			'Viera 2 - create' => [
				'4D454930-0200-1000-8001-A81374B30314',
				3,
				__DIR__ . '/../../../fixtures/Builders/device.2.channels.json',
				__DIR__ . '/../../../fixtures/Builders/device.2.channels.properties.json',
			],
		];
	}

}
