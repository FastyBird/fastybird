<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Tests\Cases\Unit\Queue\Consumers;

use Doctrine\DBAL;
use Error;
use Exception;
use FastyBird\Connector\Zigbee2Mqtt\Entities;
use FastyBird\Connector\Zigbee2Mqtt\Exceptions;
use FastyBird\Connector\Zigbee2Mqtt\Helpers;
use FastyBird\Connector\Zigbee2Mqtt\Queries;
use FastyBird\Connector\Zigbee2Mqtt\Queue;
use FastyBird\Connector\Zigbee2Mqtt\Tests\Cases\Unit\DbTestCase;
use FastyBird\Library\Bootstrap\Exceptions as BootstrapExceptions;
use FastyBird\Library\Exchange\Publisher as ExchangePublisher;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use Nette;
use Ramsey\Uuid;
use RuntimeException;
use function assert;

final class StoreBridgeDevicesTest extends DbTestCase
{

	/**
	 * @throws DBAL\Exception
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws DevicesExceptions\Runtime
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Exception
	 */
	public function testConsumeEntity(): void
	{
		$publisher = $this->createMock(ExchangePublisher\Container::class);
		$publisher
			->expects(self::exactly(33))
			->method('publish')
			->with(
				self::callback(
					// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
					static function (MetadataTypes\ModuleSource|MetadataTypes\PluginSource|MetadataTypes\ConnectorSource|MetadataTypes\AutomatorSource $source): bool {
						self::assertTrue($source->equalsValue(MetadataTypes\ModuleSource::DEVICES));

						return true;
					},
				),
				self::callback(static fn (MetadataTypes\RoutingKey $routingKey): bool => true),
				self::callback(static function (MetadataDocuments\Document|null $entity): bool {
					self::assertTrue($entity !== null);

					return true;
				}),
			);

		$this->mockContainerService(
			ExchangePublisher\Container::class,
			$publisher,
		);

		$consumer = $this->getContainer()->getByType(
			Queue\Consumers\StoreBridgeDevices::class,
		);

		$entityFactory = $this->getContainer()->getByType(
			Helpers\Entity::class,
		);

		$entity = $entityFactory->create(
			Entities\Messages\StoreBridgeDevices::class,
			[
				'connector' => Uuid\Uuid::fromString('f15d2072-fb60-421a-a85f-2566e4dc13fe'),
				'base_topic' => 'zigbee2mqtt',
				'devices' => [
					[
						'friendly_name' => '0xa4c138f06eafa3da',
						'ieee_address' => '0xa4c138f06eafa3da',
						'network_address' => 37_167,
						'interview_completed' => true,
						'interviewing' => false,
						'type' => 'EndDevice',
						'definition' => [
							'model' => 'SEN123',
							'vendor' => 'VendorName',
							'description' => 'Some sensor',
							'exposes' => [
								[
									'access' => 1,
									'description' => 'Indicates if the battery of this device is almost empty',
									'label' => 'Battery low',
									'name' => 'battery_low',
									'property' => 'battery_low',
									'type' => 'binary',
									'value_off' => false,
									'value_on' => true,
								],
								[
									'access' => 1,
									'description' => 'Remaining battery in %, can take up to 24 hours before reported.',
									'label' => 'Battery',
									'name' => 'battery',
									'property' => 'battery',
									'type' => 'numeric',
									'unit' => '%',
									'value_max' => 100,
									'value_min' => 0,
								],
								[
									'label' => 'Day time',
									'name' => 'day_time',
									'property' => 'day_time',
									'type' => 'composite',
									'features' => [
										[
											'access' => 3,
											'label' => 'Day',
											'name' => 'day',
											'property' => 'day',
											'type' => 'enum',
											'values' => ['monday', 'tuesday', 'wednesday'],
										],
										[
											'access' => 3,
											'label' => 'Hour',
											'name' => 'hour',
											'property' => 'hour',
											'type' => 'numeric',
										],
										[
											'access' => 3,
											'label' => 'Minute',
											'name' => 'minute',
											'property' => 'minute',
											'type' => 'numeric',
										],
									],
								],
							],
							'supports_ota' => false,
						],
						'description' => null,
						'manufacturer' => null,
						'model_id' => 'SEN123',
						'supported' => true,
						'disabled' => false,
					],
					[
						'friendly_name' => '0xa5c129a06eafa3da',
						'ieee_address' => '0xa5c129a06eafa3da',
						'network_address' => 30_167,
						'interview_completed' => true,
						'interviewing' => false,
						'type' => 'EndDevice',
						'definition' => [
							'model' => 'SEN321',
							'vendor' => 'VendorName',
							'description' => 'Other sensor',
							'exposes' => [
								[
									'type' => 'light',
									'features' => [
										[
											'access' => 7,
											'label' => 'State',
											'name' => 'state',
											'property' => 'state',
											'type' => 'binary',
											'value_off' => 'OFF',
											'value_on' => 'ON',
											'value_toggle' => 'TOGGLE',
										],
										[
											'access' => 7,
											'label' => 'Brightness',
											'name' => 'brightness',
											'property' => 'brightness',
											'type' => 'numeric',
											'value_min' => 0,
											'value_max' => 254,
										],
										[
											'label' => 'Color xy',
											'name' => 'color_xy',
											'property' => 'color',
											'type' => 'composite',
											'features' => [
												[
													'access' => 7,
													'label' => 'X',
													'name' => 'x',
													'property' => 'x',
													'type' => 'numeric',
												],
												[
													'access' => 7,
													'label' => 'Y',
													'name' => 'y',
													'property' => 'y',
													'type' => 'numeric',
												],
											],
										],
									],
								],
							],
							'supports_ota' => false,
						],
						'description' => null,
						'manufacturer' => null,
						'model_id' => 'SEN321',
						'supported' => true,
						'disabled' => false,
					],
				],
			],
		);

		$consumer->consume($entity);

		$connectorsRepository = $this->getContainer()->getByType(
			DevicesModels\Entities\Connectors\ConnectorsRepository::class,
		);

		$connector = $connectorsRepository->find(
			Uuid\Uuid::fromString('f15d2072-fb60-421a-a85f-2566e4dc13fe'),
			Entities\Zigbee2MqttConnector::class,
		);
		assert($connector instanceof Entities\Zigbee2MqttConnector);

		$devicesRepository = $this->getContainer()->getByType(
			DevicesModels\Entities\Devices\DevicesRepository::class,
		);

		$findDevicesQuery = new Queries\Entities\FindBridgeDevices();
		$findDevicesQuery->forConnector($connector);

		$bridge = $devicesRepository->findOneBy($findDevicesQuery, Entities\Devices\Bridge::class);
		assert($bridge instanceof Entities\Devices\Bridge);

		$findDevicesQuery = new Queries\Entities\FindSubDevices();
		$findDevicesQuery->forConnector($connector);
		$findDevicesQuery->forParent($bridge);

		$devices = $devicesRepository->findAllBy($findDevicesQuery, Entities\Devices\SubDevice::class);

		self::assertCount(2, $devices);
	}

}
