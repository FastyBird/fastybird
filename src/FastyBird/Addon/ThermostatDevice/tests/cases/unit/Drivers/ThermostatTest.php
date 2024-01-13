<?php declare(strict_types = 1);

namespace FastyBird\Addon\ThermostatDevice\Tests\Cases\Unit\Drivers;

use Error;
use FastyBird\Addon\ThermostatDevice\Exceptions;
use FastyBird\Addon\ThermostatDevice\Tests;
use FastyBird\Addon\ThermostatDevice\Types;
use FastyBird\Connector\Virtual\Drivers as VirtualDrivers;
use FastyBird\Connector\Virtual\Entities as VirtualEntities;
use FastyBird\Connector\Virtual\Queue as VirtualQueue;
use FastyBird\Library\Bootstrap\Exceptions as BootstrapExceptions;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\States as DevicesStates;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette\DI;
use React\EventLoop;
use RuntimeException;
use function array_key_exists;
use function count;
use function in_array;
use function React\Async\await;

final class ThermostatTest extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws DI\MissingServiceException
	 * @throws Error
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws RuntimeException
	 */
	public function testConnect(): void
	{
		$devicesRepository = $this->getContainer()->getByType(DevicesModels\Configuration\Devices\Repository::class);

		$findDeviceQuery = new DevicesQueries\Configuration\FindDevices();
		$findDeviceQuery->byIdentifier('thermostat-office');

		$device = $devicesRepository->findOneBy($findDeviceQuery);
		self::assertInstanceOf(MetadataDocuments\DevicesModule\Device::class, $device);

		$driversManager = $this->getContainer()->getByType(VirtualDrivers\DriversManager::class);

		$driver = $driversManager->getDriver($device);

		self::assertFalse($driver->isConnected());

		await($driver->connect());

		self::assertTrue($driver->isConnected());
	}

	/**
	 * @param array<string, int|float|bool|string> $readInitialStates
	 * @param array<mixed> $expectedWriteEntities
	 *
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws DI\MissingServiceException
	 * @throws Error
	 * @throws Exceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws RuntimeException
	 *
	 * @dataProvider processThermostatData
	 */
	public function testProcess(array $readInitialStates, array $expectedWriteEntities): void
	{
		$channelPropertiesStatesManager = $this->createMock(DevicesUtilities\ChannelPropertiesStates::class);
		$channelPropertiesStatesManager
			->method('readValue')
			->willReturnCallback(
				static function (
					MetadataDocuments\DevicesModule\ChannelProperty $property,
				) use ($readInitialStates): DevicesStates\ChannelProperty|null {
					if (array_key_exists($property->getId()->toString(), $readInitialStates)) {
						$state = new Tests\Fixtures\Dummy\DummyChannelPropertyState($property->getId());
						$state->setActualValue($readInitialStates[$property->getId()->toString()]);
						$state->setValid(true);

						return $state;
					}

					return null;
				},
			);

		$this->mockContainerService(
			DevicesUtilities\ChannelPropertiesStates::class,
			$channelPropertiesStatesManager,
		);

		$storeChannelPropertyStateConsumer = $this->createMock(VirtualQueue\Consumers\StoreChannelPropertyState::class);
		$storeChannelPropertyStateConsumer
			->expects(self::exactly(count($expectedWriteEntities)))
			->method('consume')
			->with(
				self::callback(
					static function (VirtualEntities\Messages\Entity $entity) use ($expectedWriteEntities): bool {
						self::assertTrue(in_array($entity->toArray(), $expectedWriteEntities, true));

						return true;
					},
				),
			);

		$this->mockContainerService(
			VirtualQueue\Consumers\StoreChannelPropertyState::class,
			$storeChannelPropertyStateConsumer,
		);

		$devicesRepository = $this->getContainer()->getByType(DevicesModels\Configuration\Devices\Repository::class);

		$findDeviceQuery = new DevicesQueries\Configuration\FindDevices();
		$findDeviceQuery->byIdentifier('thermostat-office');

		$device = $devicesRepository->findOneBy($findDeviceQuery);
		self::assertInstanceOf(MetadataDocuments\DevicesModule\Device::class, $device);

		$driversManager = $this->getContainer()->getByType(VirtualDrivers\DriversManager::class);

		$driver = $driversManager->getDriver($device);

		await($driver->connect());

		$driver->process();

		$eventLoop = $this->getContainer()->getByType(EventLoop\LoopInterface::class);

		$eventLoop->addTimer(1, static function () use ($eventLoop): void {
			$eventLoop->stop();
		});

		$eventLoop->run();

		$queue = $this->getContainer()->getByType(VirtualQueue\Queue::class);

		self::assertFalse($queue->isEmpty());

		/** @phpstan-ignore-next-line */
		while (!$queue->isEmpty()) {
			$consumers = $this->getContainer()->getByType(VirtualQueue\Consumers::class);

			$consumers->consume();
		}
	}

	/**
	 * Target temperature range: 21.7 - 22.3
	 *
	 * @return array<string, mixed>
	 */
	public static function processThermostatData(): array
	{
		return [
			'keep_off' => [
				// Read properties initial states
				[
					'bceca543-2de7-44b1-8a33-87e9574b6731' => false, // heater_1
					'd58fe894-0d1c-4bf0-bff5-a190cab20e5c' => 22.3, // target_sensor_1
					'e2b98261-2a05-483d-be7c-ac3afe3888b2' => 24.0, // floor_sensor_1
					'17627f14-ebbf-4bc1-88fd-e8fc32d3e5de' => 22.0, // target_temperature - manual
					'1e196c5c-a469-4ec7-95e7-c4bb48d58fe0' => 17.0, // target_temperature - preset_away
					'767ddcf6-24c5-48b0-baaa-e8c7a90d3dc0' => 20.0, // target_temperature - preset_eco
					'15d157d1-0ec7-42a7-9683-51678de1ce9a' => 22.0, // target_temperature - preset_home
					'a326ba38-d188-4eac-a6ad-43bdcc84a730' => Types\HvacMode::HEAT, // hvac_mode
					'f0b8100f-5ddb-4abd-8015-d0dbf9a11aa0' => Types\Preset::MANUAL, // preset_mode
				],
				// Expected write entities
				[
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => '9808b386-9ed4-4e58-88f1-b39f5f70ef39',
						'property' => 'bceca543-2de7-44b1-8a33-87e9574b6731',
						'value' => false,
					],
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => 'c2c572b3-3248-44da-aca0-fd329e1d9418',
						'property' => 'hvac_state',
						'value' => Types\HvacState::OFF,
					],
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => 'c2c572b3-3248-44da-aca0-fd329e1d9418',
						'property' => 'actual_temperature',
						'value' => 22.3,
					],
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => 'c2c572b3-3248-44da-aca0-fd329e1d9418',
						'property' => 'actual_floor_temperature',
						'value' => 24.0,
					],
				],
			],
			'keep_on' => [
				// Read properties initial states
				[
					'bceca543-2de7-44b1-8a33-87e9574b6731' => true, // heater_1
					'd58fe894-0d1c-4bf0-bff5-a190cab20e5c' => 21.7, // target_sensor_1
					'e2b98261-2a05-483d-be7c-ac3afe3888b2' => 22.0, // floor_sensor_1
					'17627f14-ebbf-4bc1-88fd-e8fc32d3e5de' => 22.0, // target_temperature - manual
					'1e196c5c-a469-4ec7-95e7-c4bb48d58fe0' => 17.0, // target_temperature - preset_away
					'767ddcf6-24c5-48b0-baaa-e8c7a90d3dc0' => 20.0, // target_temperature - preset_eco
					'15d157d1-0ec7-42a7-9683-51678de1ce9a' => 22.0, // target_temperature - preset_home
					'a326ba38-d188-4eac-a6ad-43bdcc84a730' => Types\HvacMode::HEAT, // hvac_mode
					'f0b8100f-5ddb-4abd-8015-d0dbf9a11aa0' => Types\Preset::MANUAL, // preset_mode
				],
				// Expected write entities
				[
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => '9808b386-9ed4-4e58-88f1-b39f5f70ef39',
						'property' => 'bceca543-2de7-44b1-8a33-87e9574b6731',
						'value' => true,
					],
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => 'c2c572b3-3248-44da-aca0-fd329e1d9418',
						'property' => 'hvac_state',
						'value' => Types\HvacState::HEATING,
					],
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => 'c2c572b3-3248-44da-aca0-fd329e1d9418',
						'property' => 'actual_temperature',
						'value' => 21.7,
					],
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => 'c2c572b3-3248-44da-aca0-fd329e1d9418',
						'property' => 'actual_floor_temperature',
						'value' => 22.0,
					],
				],
			],
			'turn_heat_on' => [
				// Read properties initial states
				[
					'bceca543-2de7-44b1-8a33-87e9574b6731' => false, // heater_1
					'd58fe894-0d1c-4bf0-bff5-a190cab20e5c' => 21.6, // target_sensor_1
					'e2b98261-2a05-483d-be7c-ac3afe3888b2' => 22.0, // floor_sensor_1
					'17627f14-ebbf-4bc1-88fd-e8fc32d3e5de' => 22.0, // target_temperature - manual
					'1e196c5c-a469-4ec7-95e7-c4bb48d58fe0' => 17.0, // target_temperature - preset_away
					'767ddcf6-24c5-48b0-baaa-e8c7a90d3dc0' => 20.0, // target_temperature - preset_eco
					'15d157d1-0ec7-42a7-9683-51678de1ce9a' => 22.0, // target_temperature - preset_home
					'a326ba38-d188-4eac-a6ad-43bdcc84a730' => Types\HvacMode::HEAT, // hvac_mode
					'f0b8100f-5ddb-4abd-8015-d0dbf9a11aa0' => Types\Preset::MANUAL, // preset_mode
				],
				// Expected write entities
				[
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => '9808b386-9ed4-4e58-88f1-b39f5f70ef39',
						'property' => 'bceca543-2de7-44b1-8a33-87e9574b6731',
						'value' => true,
					],
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => 'c2c572b3-3248-44da-aca0-fd329e1d9418',
						'property' => 'hvac_state',
						'value' => Types\HvacState::HEATING,
					],
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => 'c2c572b3-3248-44da-aca0-fd329e1d9418',
						'property' => 'actual_temperature',
						'value' => 21.6,
					],
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => 'c2c572b3-3248-44da-aca0-fd329e1d9418',
						'property' => 'actual_floor_temperature',
						'value' => 22.0,
					],
				],
			],
			'turn_heat_off' => [
				// Read properties initial states
				[
					'bceca543-2de7-44b1-8a33-87e9574b6731' => true, // heater_1
					'd58fe894-0d1c-4bf0-bff5-a190cab20e5c' => 22.3, // target_sensor_1
					'e2b98261-2a05-483d-be7c-ac3afe3888b2' => 22.0, // floor_sensor_1
					'17627f14-ebbf-4bc1-88fd-e8fc32d3e5de' => 22.0, // target_temperature - manual
					'1e196c5c-a469-4ec7-95e7-c4bb48d58fe0' => 17.0, // target_temperature - preset_away
					'767ddcf6-24c5-48b0-baaa-e8c7a90d3dc0' => 20.0, // target_temperature - preset_eco
					'15d157d1-0ec7-42a7-9683-51678de1ce9a' => 22.0, // target_temperature - preset_home
					'a326ba38-d188-4eac-a6ad-43bdcc84a730' => Types\HvacMode::HEAT, // hvac_mode
					'f0b8100f-5ddb-4abd-8015-d0dbf9a11aa0' => Types\Preset::MANUAL, // preset_mode
				],
				// Expected write entities
				[
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => '9808b386-9ed4-4e58-88f1-b39f5f70ef39',
						'property' => 'bceca543-2de7-44b1-8a33-87e9574b6731',
						'value' => false,
					],
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => 'c2c572b3-3248-44da-aca0-fd329e1d9418',
						'property' => 'hvac_state',
						'value' => Types\HvacState::OFF,
					],
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => 'c2c572b3-3248-44da-aca0-fd329e1d9418',
						'property' => 'actual_temperature',
						'value' => 22.3,
					],
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => 'c2c572b3-3248-44da-aca0-fd329e1d9418',
						'property' => 'actual_floor_temperature',
						'value' => 22.0,
					],
				],
			],
			'keep_on_hysteresis' => [
				// Read properties initial states
				[
					'bceca543-2de7-44b1-8a33-87e9574b6731' => true, // heater_1
					'd58fe894-0d1c-4bf0-bff5-a190cab20e5c' => 22.0, // target_sensor_1
					'e2b98261-2a05-483d-be7c-ac3afe3888b2' => 23.0, // floor_sensor_1
					'17627f14-ebbf-4bc1-88fd-e8fc32d3e5de' => 22.0, // target_temperature - manual
					'1e196c5c-a469-4ec7-95e7-c4bb48d58fe0' => 17.0, // target_temperature - preset_away
					'767ddcf6-24c5-48b0-baaa-e8c7a90d3dc0' => 20.0, // target_temperature - preset_eco
					'15d157d1-0ec7-42a7-9683-51678de1ce9a' => 22.0, // target_temperature - preset_home
					'a326ba38-d188-4eac-a6ad-43bdcc84a730' => Types\HvacMode::HEAT, // hvac_mode
					'f0b8100f-5ddb-4abd-8015-d0dbf9a11aa0' => Types\Preset::MANUAL, // preset_mode
				],
				// Expected write entities
				[
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => 'c2c572b3-3248-44da-aca0-fd329e1d9418',
						'property' => 'actual_temperature',
						'value' => 22.0,
					],
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => 'c2c572b3-3248-44da-aca0-fd329e1d9418',
						'property' => 'actual_floor_temperature',
						'value' => 23.0,
					],
				],
			],
			'keep_off_hysteresis' => [
				// Read properties initial states
				[
					'bceca543-2de7-44b1-8a33-87e9574b6731' => false, // heater_1
					'd58fe894-0d1c-4bf0-bff5-a190cab20e5c' => 22.0, // target_sensor_1
					'e2b98261-2a05-483d-be7c-ac3afe3888b2' => 23.0, // floor_sensor_1
					'17627f14-ebbf-4bc1-88fd-e8fc32d3e5de' => 22.0, // target_temperature - manual
					'1e196c5c-a469-4ec7-95e7-c4bb48d58fe0' => 17.0, // target_temperature - preset_away
					'767ddcf6-24c5-48b0-baaa-e8c7a90d3dc0' => 20.0, // target_temperature - preset_eco
					'15d157d1-0ec7-42a7-9683-51678de1ce9a' => 22.0, // target_temperature - preset_home
					'a326ba38-d188-4eac-a6ad-43bdcc84a730' => Types\HvacMode::HEAT, // hvac_mode
					'f0b8100f-5ddb-4abd-8015-d0dbf9a11aa0' => Types\Preset::MANUAL, // preset_mode
				],
				// Expected write entities
				[
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => 'c2c572b3-3248-44da-aca0-fd329e1d9418',
						'property' => 'actual_temperature',
						'value' => 22.0,
					],
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => 'c2c572b3-3248-44da-aca0-fd329e1d9418',
						'property' => 'actual_floor_temperature',
						'value' => 23.0,
					],
				],
			],
			'floor_overheat' => [
				// Read properties initial states
				[
					'bceca543-2de7-44b1-8a33-87e9574b6731' => true, // heater_1
					'd58fe894-0d1c-4bf0-bff5-a190cab20e5c' => 21.6, // target_sensor_1
					'e2b98261-2a05-483d-be7c-ac3afe3888b2' => 28, // floor_sensor_1
					'17627f14-ebbf-4bc1-88fd-e8fc32d3e5de' => 22.0, // target_temperature - manual
					'1e196c5c-a469-4ec7-95e7-c4bb48d58fe0' => 17.0, // target_temperature - preset_away
					'767ddcf6-24c5-48b0-baaa-e8c7a90d3dc0' => 20.0, // target_temperature - preset_eco
					'15d157d1-0ec7-42a7-9683-51678de1ce9a' => 22.0, // target_temperature - preset_home
					'a326ba38-d188-4eac-a6ad-43bdcc84a730' => Types\HvacMode::HEAT, // hvac_mode
					'f0b8100f-5ddb-4abd-8015-d0dbf9a11aa0' => Types\Preset::MANUAL, // preset_mode
				],
				// Expected write entities
				[
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => '9808b386-9ed4-4e58-88f1-b39f5f70ef39',
						'property' => 'bceca543-2de7-44b1-8a33-87e9574b6731',
						'value' => false,
					],
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => 'c2c572b3-3248-44da-aca0-fd329e1d9418',
						'property' => 'hvac_state',
						'value' => Types\HvacState::OFF,
					],
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => 'c2c572b3-3248-44da-aca0-fd329e1d9418',
						'property' => 'actual_temperature',
						'value' => 21.6,
					],
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => 'c2c572b3-3248-44da-aca0-fd329e1d9418',
						'property' => 'actual_floor_temperature',
						'value' => 28.0,
					],
				],
			],
		];
	}

}
