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
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Nette;
use Ramsey\Uuid;
use RuntimeException;
use function array_diff;
use function array_merge;
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

		$findConnectorQuery = new DevicesQueries\Entities\FindConnectors();
		$findConnectorQuery->byId(Uuid\Uuid::fromString('f15d2072-fb60-421a-a85f-2566e4dc13fe'));

		$connector = $connectorsRepository->findOneBy($findConnectorQuery);
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

		$channelsRepository = $this->getContainer()->getByType(
			DevicesModels\Entities\Channels\ChannelsRepository::class,
		);

		$transformed = [];

		foreach ($devices as $device) {
			$deviceProperties = [];

			foreach ($device->getProperties() as $property) {
				$data = $property->toArray();
				unset($data['id']);
				unset($data['device']);

				$deviceProperties[] = $data;
			}

			$findChannelsQuery = new DevicesQueries\Entities\FindChannels();
			$findChannelsQuery->forDevice($device);

			$deviceChannels = $channelsRepository->findAllBy($findChannelsQuery);

			$channels = [];

			foreach ($deviceChannels as $channel) {
				$channelProperties = [];

				foreach ($channel->getProperties() as $property) {
					$data = $property->toArray();
					unset($data['id']);
					unset($data['channel']);

					$channelProperties[] = $data;
				}

				$data = $channel->toArray();
				unset($data['id']);
				unset($data['device']);

				$channels[] = array_merge($data, ['properties' => $channelProperties]);
			}

			$data = $device->toArray();
			unset($data['id']);

			$transformed[] = array_merge(
				$data,
				[
					'properties' => $deviceProperties,
					'channels' => $channels,
				],
			);
		}

		$expected = [
			[
				'type' => 'zigbee2mqtt-sub-device',
				'category' => 'generic',
				'identifier' => '0xa4c138f06eafa3da',
				'name' => 'Some sensor',
				'comment' => null,
				'connector' => 'f15d2072-fb60-421a-a85f-2566e4dc13fe',
				'parents' => [
					'c9cdc7c2-9ae0-4339-93b7-18530aec0c42',
				],
				'children' => [],
				'properties' => [
					[
						'type' => 'dynamic',
						'category' => 'generic',
						'identifier' => 'state',
						'name' => 'State',
						'data_type' => 'enum',
						'unit' => null,
						'format' => [
							'connected',
							'disconnected',
							'alert',
							'unknown',
						],
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'created_at' => '2020-04-01T12:00:00+00:00',
						'updated_at' => '2020-04-01T12:00:00+00:00',
						'settable' => false,
						'queryable' => false,
						'owner' => null,
						'children' => [],
					],
					[
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'friendly_name',
						'name' => 'Friendly name',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'created_at' => '2020-04-01T12:00:00+00:00',
						'updated_at' => '2020-04-01T12:00:00+00:00',
						'default' => null,
						'value' => '0xa4c138f06eafa3da',
						'owner' => null,
						'children' => [],
					],
					[
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'address',
						'name' => 'Address',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'created_at' => '2020-04-01T12:00:00+00:00',
						'updated_at' => '2020-04-01T12:00:00+00:00',
						'default' => null,
						'value' => '0xa4c138f06eafa3da',
						'owner' => null,
						'children' => [],
					],
					[
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'disabled',
						'name' => 'Disabled',
						'data_type' => 'bool',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'created_at' => '2020-04-01T12:00:00+00:00',
						'updated_at' => '2020-04-01T12:00:00+00:00',
						'default' => null,
						'value' => false,
						'owner' => null,
						'children' => [],
					],
					[
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'supported',
						'name' => 'Supported',
						'data_type' => 'bool',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'created_at' => '2020-04-01T12:00:00+00:00',
						'updated_at' => '2020-04-01T12:00:00+00:00',
						'default' => null,
						'value' => true,
						'owner' => null,
						'children' => [],
					],
					[
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'type',
						'name' => 'Type',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'created_at' => '2020-04-01T12:00:00+00:00',
						'updated_at' => '2020-04-01T12:00:00+00:00',
						'default' => null,
						'value' => 'EndDevice',
						'owner' => null,
						'children' => [],
					],
					[
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'hardware_model',
						'name' => 'Hardware model',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'created_at' => '2020-04-01T12:00:00+00:00',
						'updated_at' => '2020-04-01T12:00:00+00:00',
						'default' => null,
						'value' => 'SEN123',
						'owner' => null,
						'children' => [],
					],
					[
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'hardware_manufacturer',
						'name' => 'Hardware manufacturer',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'created_at' => '2020-04-01T12:00:00+00:00',
						'updated_at' => '2020-04-01T12:00:00+00:00',
						'default' => null,
						'value' => 'VendorName',
						'owner' => null,
						'children' => [],
					],
				],
				'controls' => [],
				'channels' => [
					[
						'type' => 'general',
						'category' => 'generic',
						'identifier' => 'binary_battery_low',
						'name' => 'Battery low',
						'comment' => null,
						'properties' => [
							[
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'battery_low',
								'name' => 'Battery low',
								'data_type' => 'bool',
								'unit' => null,
								'format' => null,
								'invalid' => null,
								'scale' => null,
								'step' => null,
								'created_at' => '2020-04-01T12:00:00+00:00',
								'updated_at' => '2020-04-01T12:00:00+00:00',
								'settable' => false,
								'queryable' => false,
								'owner' => null,
								'children' => [],
							],
						],
						'controls' => [],
						'connector' => 'f15d2072-fb60-421a-a85f-2566e4dc13fe',
						'owner' => null,
						'created_at' => '2020-04-01T12:00:00+00:00',
						'updated_at' => '2020-04-01T12:00:00+00:00',
					],
					[
						'type' => 'general',
						'category' => 'generic',
						'identifier' => 'composite_day_time',
						'name' => 'Day time',
						'comment' => null,
						'properties' => [
							[
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'day',
								'name' => 'Day',
								'data_type' => 'enum',
								'unit' => null,
								'format' => [
									'monday',
									'tuesday',
									'wednesday',
								],
								'invalid' => null,
								'scale' => null,
								'step' => null,
								'created_at' => '2020-04-01T12:00:00+00:00',
								'updated_at' => '2020-04-01T12:00:00+00:00',
								'settable' => true,
								'queryable' => false,
								'owner' => null,
								'children' => [],
							],
							[
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'hour',
								'name' => 'Hour',
								'data_type' => 'float',
								'unit' => null,
								'format' => null,
								'invalid' => null,
								'scale' => null,
								'step' => null,
								'created_at' => '2020-04-01T12:00:00+00:00',
								'updated_at' => '2020-04-01T12:00:00+00:00',
								'settable' => true,
								'queryable' => false,
								'owner' => null,
								'children' => [],
							],
							[
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'minute',
								'name' => 'Minute',
								'data_type' => 'float',
								'unit' => null,
								'format' => null,
								'invalid' => null,
								'scale' => null,
								'step' => null,
								'created_at' => '2020-04-01T12:00:00+00:00',
								'updated_at' => '2020-04-01T12:00:00+00:00',
								'settable' => true,
								'queryable' => false,
								'owner' => null,
								'children' => [],
							],
						],
						'controls' => [],
						'connector' => 'f15d2072-fb60-421a-a85f-2566e4dc13fe',
						'owner' => null,
						'created_at' => '2020-04-01T12:00:00+00:00',
						'updated_at' => '2020-04-01T12:00:00+00:00',
					],
					[
						'type' => 'general',
						'category' => 'generic',
						'identifier' => 'numeric_battery',
						'name' => 'Battery',
						'comment' => null,
						'properties' => [
							[
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'battery',
								'name' => 'Battery',
								'data_type' => 'float',
								'unit' => '%',
								'format' => [
									0.0,
									100.0,
								],
								'invalid' => null,
								'scale' => null,
								'step' => null,
								'created_at' => '2020-04-01T12:00:00+00:00',
								'updated_at' => '2020-04-01T12:00:00+00:00',
								'settable' => false,
								'queryable' => false,
								'owner' => null,
								'children' => [],
							],
						],
						'controls' => [],
						'connector' => 'f15d2072-fb60-421a-a85f-2566e4dc13fe',
						'owner' => null,
						'created_at' => '2020-04-01T12:00:00+00:00',
						'updated_at' => '2020-04-01T12:00:00+00:00',
					],

				],
				'owner' => null,
				'created_at' => '2020-04-01T12:00:00+00:00',
				'updated_at' => '2020-04-01T12:00:00+00:00',
			],
			[
				'type' => 'zigbee2mqtt-sub-device',
				'category' => 'generic',
				'identifier' => '0xa5c129a06eafa3da',
				'name' => 'Other sensor',
				'comment' => null,
				'connector' => 'f15d2072-fb60-421a-a85f-2566e4dc13fe',
				'parents' => [
					'c9cdc7c2-9ae0-4339-93b7-18530aec0c42',
				],
				'children' => [],
				'properties' => [
					[
						'type' => 'dynamic',
						'category' => 'generic',
						'identifier' => 'state',
						'name' => 'State',
						'data_type' => 'enum',
						'unit' => null,
						'format' => [
							'connected',
							'disconnected',
							'alert',
							'unknown',
						],
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'created_at' => '2020-04-01T12:00:00+00:00',
						'updated_at' => '2020-04-01T12:00:00+00:00',
						'settable' => false,
						'queryable' => false,
						'owner' => null,
						'children' => [],
					],
					[
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'friendly_name',
						'name' => 'Friendly name',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'created_at' => '2020-04-01T12:00:00+00:00',
						'updated_at' => '2020-04-01T12:00:00+00:00',
						'default' => null,
						'value' => '0xa5c129a06eafa3da',
						'owner' => null,
						'children' => [],
					],
					[
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'address',
						'name' => 'Address',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'created_at' => '2020-04-01T12:00:00+00:00',
						'updated_at' => '2020-04-01T12:00:00+00:00',
						'default' => null,
						'value' => '0xa5c129a06eafa3da',
						'owner' => null,
						'children' => [],
					],
					[
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'disabled',
						'name' => 'Disabled',
						'data_type' => 'bool',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'created_at' => '2020-04-01T12:00:00+00:00',
						'updated_at' => '2020-04-01T12:00:00+00:00',
						'default' => null,
						'value' => false,
						'owner' => null,
						'children' => [],
					],
					[
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'supported',
						'name' => 'Supported',
						'data_type' => 'bool',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'created_at' => '2020-04-01T12:00:00+00:00',
						'updated_at' => '2020-04-01T12:00:00+00:00',
						'default' => null,
						'value' => true,
						'owner' => null,
						'children' => [],
					],
					[
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'type',
						'name' => 'Type',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'created_at' => '2020-04-01T12:00:00+00:00',
						'updated_at' => '2020-04-01T12:00:00+00:00',
						'default' => null,
						'value' => 'EndDevice',
						'owner' => null,
						'children' => [],
					],
					[
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'hardware_model',
						'name' => 'Hardware model',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'created_at' => '2020-04-01T12:00:00+00:00',
						'updated_at' => '2020-04-01T12:00:00+00:00',
						'default' => null,
						'value' => 'SEN321',
						'owner' => null,
						'children' => [],
					],
					[
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'hardware_manufacturer',
						'name' => 'Hardware manufacturer',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'created_at' => '2020-04-01T12:00:00+00:00',
						'updated_at' => '2020-04-01T12:00:00+00:00',
						'default' => null,
						'value' => 'VendorName',
						'owner' => null,
						'children' => [],
					],
				],
				'controls' => [],
				'channels' => [
					[
						'type' => 'general',
						'category' => 'generic',
						'identifier' => 'light_binary_state',
						'name' => 'State',
						'comment' => null,
						'properties' => [
							[
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'state',
								'name' => 'State',
								'data_type' => 'switch',
								'unit' => null,
								'format' => [
									[
										0 => 'switch_on',
										1 => 'ON',
										2 => 'ON',
									],
									[
										0 => 'switch_off',
										1 => 'OFF',
										2 => 'OFF',
									],
									[
										0 => 'switch_toggle',
										1 => 'TOGGLE',
										2 => 'TOGGLE',
									],
								],
								'invalid' => null,
								'scale' => null,
								'step' => null,
								'created_at' => '2020-04-01T12:00:00+00:00',
								'updated_at' => '2020-04-01T12:00:00+00:00',
								'settable' => true,
								'queryable' => true,
								'owner' => null,
								'children' => [],
							],
						],
						'controls' => [],
						'connector' => 'f15d2072-fb60-421a-a85f-2566e4dc13fe',
						'owner' => null,
						'created_at' => '2020-04-01T12:00:00+00:00',
						'updated_at' => '2020-04-01T12:00:00+00:00',
					],
					[
						'type' => 'general',
						'category' => 'generic',
						'identifier' => 'light_composite_color',
						'name' => 'Color xy',
						'comment' => null,
						'properties' => [
							[
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'x',
								'name' => 'X',
								'data_type' => 'float',
								'unit' => null,
								'format' => null,
								'invalid' => null,
								'scale' => null,
								'step' => null,
								'created_at' => '2020-04-01T12:00:00+00:00',
								'updated_at' => '2020-04-01T12:00:00+00:00',
								'settable' => true,
								'queryable' => true,
								'owner' => null,
								'children' => [],
							],
							[
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'y',
								'name' => 'Y',
								'data_type' => 'float',
								'unit' => null,
								'format' => null,
								'invalid' => null,
								'scale' => null,
								'step' => null,
								'created_at' => '2020-04-01T12:00:00+00:00',
								'updated_at' => '2020-04-01T12:00:00+00:00',
								'settable' => true,
								'queryable' => true,
								'owner' => null,
								'children' => [],
							],
						],
						'controls' => [],
						'connector' => 'f15d2072-fb60-421a-a85f-2566e4dc13fe',
						'owner' => null,
						'created_at' => '2020-04-01T12:00:00+00:00',
						'updated_at' => '2020-04-01T12:00:00+00:00',
					],
					[
						'type' => 'general',
						'category' => 'generic',
						'identifier' => 'light_numeric_brightness',
						'name' => 'Brightness',
						'comment' => null,
						'properties' => [
							[
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'brightness',
								'name' => 'Brightness',
								'data_type' => 'float',
								'unit' => null,
								'format' => [
									0.0,
									254.0,
								],
								'invalid' => null,
								'scale' => null,
								'step' => null,
								'created_at' => '2020-04-01T12:00:00+00:00',
								'updated_at' => '2020-04-01T12:00:00+00:00',
								'settable' => true,
								'queryable' => true,
								'owner' => null,
								'children' => [],
							],
						],
						'controls' => [],
						'connector' => 'f15d2072-fb60-421a-a85f-2566e4dc13fe',
						'owner' => null,
						'created_at' => '2020-04-01T12:00:00+00:00',
						'updated_at' => '2020-04-01T12:00:00+00:00',
					],

				],
				'owner' => null,
				'created_at' => '2020-04-01T12:00:00+00:00',
				'updated_at' => '2020-04-01T12:00:00+00:00',
			],
		];

		self::assertTrue(is_array($expected));
		self::assertTrue(is_array($transformed));

		self::assertTrue($expected !== []);
		self::assertTrue($transformed !== []);

		$diff = array_diff($expected, $transformed);

		self::assertEmpty($diff);
	}

}
