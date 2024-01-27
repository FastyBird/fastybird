<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Models\States;

use DateTimeInterface;
use Error;
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Tools\Exceptions as ToolsExceptions;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\States;
use FastyBird\Module\Devices\Tests\Cases\Unit\BaseTestCase;
use FastyBird\Module\Devices\Tests\Fixtures;
use Nette\DI;
use Ramsey\Uuid;

final class ChannelPropertiesStatesReadingTest extends BaseTestCase
{

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws Error
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws DI\MissingServiceException
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 * @throws ToolsExceptions\InvalidArgument
	 *
	 * @dataProvider readStates
	 */
	public function testReadState(
		MetadataDocuments\DevicesModule\ChannelDynamicProperty|MetadataDocuments\DevicesModule\ChannelMappedProperty $property,
		MetadataDocuments\DevicesModule\ChannelDynamicProperty|null $parent,
		States\ChannelProperty $stored,
		bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $actual,
		bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $expected,
	): void
	{
		$channelPropertiesConfigurationRepository = $this->createMock(
			Models\Configuration\Channels\Properties\Repository::class,
		);
		$channelPropertiesConfigurationRepository
			->expects(self::exactly($parent !== null ? 1 : 0))
			->method('find')
			->willReturn($parent);

		$this->mockContainerService(
			Models\Configuration\Channels\Properties\Repository::class,
			$channelPropertiesConfigurationRepository,
		);

		$channelPropertyStateRepository = $this->createMock(Models\States\Channels\Repository::class);
		$channelPropertyStateRepository
			->expects(self::exactly(1))
			->method('find')
			->willReturn($stored);

		$this->mockContainerService(
			Models\States\Channels\Repository::class,
			$channelPropertyStateRepository,
		);

		$channelPropertiesStatesManager = $this->getContainer()->getByType(
			Models\States\ChannelPropertiesManager::class,
		);

		$state = $channelPropertiesStatesManager->read($property);

		self::assertInstanceOf(MetadataDocuments\DevicesModule\PropertyValues::class, $state);
		self::assertSame($actual, $state->getActualValue(), 'actual value check');
		self::assertSame($expected, $state->getExpectedValue(), 'expected value check');
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws Error
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws DI\MissingServiceException
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 * @throws ToolsExceptions\InvalidArgument
	 *
	 * @dataProvider getStates
	 */
	public function testGetState(
		MetadataDocuments\DevicesModule\ChannelDynamicProperty|MetadataDocuments\DevicesModule\ChannelMappedProperty $property,
		MetadataDocuments\DevicesModule\ChannelDynamicProperty|null $parent,
		States\ChannelProperty $stored,
		bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $actual,
		bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $expected,
	): void
	{
		$channelPropertiesConfigurationRepository = $this->createMock(
			Models\Configuration\Channels\Properties\Repository::class,
		);
		$channelPropertiesConfigurationRepository
			->expects(self::exactly($parent !== null ? 1 : 0))
			->method('find')
			->willReturn($parent);

		$this->mockContainerService(
			Models\Configuration\Channels\Properties\Repository::class,
			$channelPropertiesConfigurationRepository,
		);

		$channelPropertyStateRepository = $this->createMock(Models\States\Channels\Repository::class);
		$channelPropertyStateRepository
			->expects(self::exactly(1))
			->method('find')
			->willReturn($stored);

		$this->mockContainerService(
			Models\States\Channels\Repository::class,
			$channelPropertyStateRepository,
		);

		$channelPropertiesStatesManager = $this->getContainer()->getByType(
			Models\States\ChannelPropertiesManager::class,
		);

		$state = $channelPropertiesStatesManager->get($property);

		self::assertInstanceOf(MetadataDocuments\DevicesModule\PropertyValues::class, $state);
		self::assertSame($actual, $state->getActualValue(), 'actual value check');
		self::assertSame($expected, $state->getExpectedValue(), 'expected value check');
	}

	/**
	 * @return array<string, array<MetadataDocuments\DevicesModule\ChannelDynamicProperty|MetadataDocuments\DevicesModule\ChannelMappedProperty|States\ChannelProperty|bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null>>
	 */
	public static function readStates(): array
	{
		$property01 = Uuid\Uuid::fromString('108e4a68-e184-44f2-b1ab-134f5b65dc6b');
		$child01 = Uuid\Uuid::fromString('a0f77991-1ad0-4940-aa6b-ad10094b2b2c');

		$channel01 = Uuid\Uuid::fromString('1fbc5210-01e0-412d-bdc1-5ffc7b16a098');
		$channel02 = Uuid\Uuid::fromString('8a032f16-2d54-43e5-827f-102ee9cc6e71');

		return [
			/**
			 * Classic property - no scale, no transformer.
			 */
			'read_01' => [
				new MetadataDocuments\DevicesModule\ChannelDynamicProperty(
					$property01,
					MetadataTypes\PropertyType::get(MetadataTypes\PropertyType::DYNAMIC),
					$channel01,
					MetadataTypes\PropertyCategory::get(MetadataTypes\PropertyCategory::GENERIC),
					'test-property-01',
					'Testing Property 01',
					MetadataTypes\DataType::get(MetadataTypes\DataType::FLOAT),
					null,
					null,
					null,
					null,
					null,
					null,
					true,
				),
				null,
				new Fixtures\Dummy\ChannelPropertyState(
					$property01,
					'254',
					127,
					false,
					true,
				),
				254.0,
				127.0,
			],
			/**
			 * Classic property with scale transformer.
			 * Scale transformer is applied because state is loaded for reading/displaying.
			 */
			'read_02' => [
				new MetadataDocuments\DevicesModule\ChannelDynamicProperty(
					$property01,
					MetadataTypes\PropertyType::get(MetadataTypes\PropertyType::DYNAMIC),
					$channel01,
					MetadataTypes\PropertyCategory::get(MetadataTypes\PropertyCategory::GENERIC),
					'test-property-02',
					'Testing Property 02',
					MetadataTypes\DataType::get(MetadataTypes\DataType::FLOAT),
					null,
					null,
					null,
					1,
					null,
					null,
					true,
				),
				null,
				new Fixtures\Dummy\ChannelPropertyState(
					$property01,
					'254',
					127.0,
					false,
					true,
				),
				25.4,
				12.7,
			],
			/**
			 * Classic property with equation transformer.
			 * Equation transformer is applied because state is loaded for reading/displaying.
			 */
			'read_03' => [
				new MetadataDocuments\DevicesModule\ChannelDynamicProperty(
					$property01,
					MetadataTypes\PropertyType::get(MetadataTypes\PropertyType::DYNAMIC),
					$channel01,
					MetadataTypes\PropertyCategory::get(MetadataTypes\PropertyCategory::GENERIC),
					'test-property-03',
					'Testing Property 03',
					MetadataTypes\DataType::get(MetadataTypes\DataType::FLOAT),
					null,
					null,
					null,
					null,
					null,
					'equation:x=y*10|y=x/10',
					true,
				),
				null,
				new Fixtures\Dummy\ChannelPropertyState(
					$property01,
					'254',
					'0127',
					false,
					true,
				),
				25.4,
				12.7,
			],
			/**
			 * Classic property with both scale and equation transformer.
			 * Both transformers are applied because state is loaded for reading/displaying.
			 */
			'read_04' => [
				new MetadataDocuments\DevicesModule\ChannelDynamicProperty(
					$property01,
					MetadataTypes\PropertyType::get(MetadataTypes\PropertyType::DYNAMIC),
					$channel01,
					MetadataTypes\PropertyCategory::get(MetadataTypes\PropertyCategory::GENERIC),
					'test-property-04',
					'Testing Property 04',
					MetadataTypes\DataType::get(MetadataTypes\DataType::FLOAT),
					null,
					null,
					null,
					1,
					null,
					'equation:x=y*10|y=x/10',
					true,
				),
				null,
				new Fixtures\Dummy\ChannelPropertyState(
					$property01,
					'254',
					'127',
					false,
					true,
				),
				2.54,
				1.27,
			],
			/**
			 * Mapped property - no scale, no transformer.
			 */
			'read_05' => [
				new MetadataDocuments\DevicesModule\ChannelMappedProperty(
					$child01,
					MetadataTypes\PropertyType::get(MetadataTypes\PropertyType::MAPPED),
					$channel02,
					$property01,
					MetadataTypes\PropertyCategory::get(MetadataTypes\PropertyCategory::GENERIC),
					'child-property-05',
					'Child Property 05',
					MetadataTypes\DataType::get(MetadataTypes\DataType::FLOAT),
					null,
					null,
					null,
					null,
					null,
					null,
					true,
				),
				new MetadataDocuments\DevicesModule\ChannelDynamicProperty(
					$property01,
					MetadataTypes\PropertyType::get(MetadataTypes\PropertyType::DYNAMIC),
					$channel01,
					MetadataTypes\PropertyCategory::get(MetadataTypes\PropertyCategory::GENERIC),
					'test-property-05',
					'Testing Property 05',
					MetadataTypes\DataType::get(MetadataTypes\DataType::FLOAT),
					null,
					null,
					null,
					null,
					null,
					null,
					true,
				),
				new Fixtures\Dummy\ChannelPropertyState(
					$property01,
					'254',
					'127',
					false,
					true,
				),
				254.0,
				127.0,
			],
			/**
			 * Mapped property with scale transformer on mapped property.
			 * Scale transformer is applied because state is loaded for reading/displaying.
			 */
			'read_06' => [
				new MetadataDocuments\DevicesModule\ChannelMappedProperty(
					$child01,
					MetadataTypes\PropertyType::get(MetadataTypes\PropertyType::MAPPED),
					$channel02,
					$property01,
					MetadataTypes\PropertyCategory::get(MetadataTypes\PropertyCategory::GENERIC),
					'child-property-06',
					'Child Property 06',
					MetadataTypes\DataType::get(MetadataTypes\DataType::FLOAT),
					null,
					null,
					null,
					1,
					null,
					null,
					true,
				),
				new MetadataDocuments\DevicesModule\ChannelDynamicProperty(
					$property01,
					MetadataTypes\PropertyType::get(MetadataTypes\PropertyType::DYNAMIC),
					$channel01,
					MetadataTypes\PropertyCategory::get(MetadataTypes\PropertyCategory::GENERIC),
					'test-property-06',
					'Testing Property 06',
					MetadataTypes\DataType::get(MetadataTypes\DataType::FLOAT),
					null,
					null,
					null,
					null,
					null,
					null,
					true,
				),
				new Fixtures\Dummy\ChannelPropertyState(
					$property01,
					'254',
					'127',
					false,
					true,
				),
				25.4,
				12.7,
			],
			/**
			 * Mapped property with scale transformer on mapped property and on parent property.
			 * Scale transformer is applied on both properties because state is loaded for reading/displaying.
			 */
			'read_07' => [
				new MetadataDocuments\DevicesModule\ChannelMappedProperty(
					$child01,
					MetadataTypes\PropertyType::get(MetadataTypes\PropertyType::MAPPED),
					$channel02,
					$property01,
					MetadataTypes\PropertyCategory::get(MetadataTypes\PropertyCategory::GENERIC),
					'child-property-07',
					'Child Property 07',
					MetadataTypes\DataType::get(MetadataTypes\DataType::FLOAT),
					null,
					null,
					null,
					1,
					null,
					null,
					true,
				),
				new MetadataDocuments\DevicesModule\ChannelDynamicProperty(
					$property01,
					MetadataTypes\PropertyType::get(MetadataTypes\PropertyType::DYNAMIC),
					$channel01,
					MetadataTypes\PropertyCategory::get(MetadataTypes\PropertyCategory::GENERIC),
					'test-property-07',
					'Testing Property 07',
					MetadataTypes\DataType::get(MetadataTypes\DataType::FLOAT),
					null,
					null,
					null,
					1,
					null,
					null,
					true,
				),
				new Fixtures\Dummy\ChannelPropertyState(
					$property01,
					'254',
					'127',
					false,
					true,
				),
				2.5,
				1.2,
			],
			/**
			 * Mapped property with equation transformer on mapped property.
			 * Equation transformer is applied because equation transformers is used always on mapped properties.
			 */
			'read_08' => [
				new MetadataDocuments\DevicesModule\ChannelMappedProperty(
					$child01,
					MetadataTypes\PropertyType::get(MetadataTypes\PropertyType::MAPPED),
					$channel02,
					$property01,
					MetadataTypes\PropertyCategory::get(MetadataTypes\PropertyCategory::GENERIC),
					'child-property-08',
					'Child Property 08',
					MetadataTypes\DataType::get(MetadataTypes\DataType::UCHAR),
					null,
					[0, 100],
					null,
					null,
					null,
					'equation:x=y/2.54|y=x*2.54',
					true,
				),
				new MetadataDocuments\DevicesModule\ChannelDynamicProperty(
					$property01,
					MetadataTypes\PropertyType::get(MetadataTypes\PropertyType::DYNAMIC),
					$channel01,
					MetadataTypes\PropertyCategory::get(MetadataTypes\PropertyCategory::GENERIC),
					'test-property-08',
					'Testing Property 08',
					MetadataTypes\DataType::get(MetadataTypes\DataType::UCHAR),
					null,
					[0, 254],
					null,
					null,
					null,
					null,
					true,
				),
				new Fixtures\Dummy\ChannelPropertyState(
					$property01,
					'250',
					'120',
					false,
					true,
				),
				98,
				47,
			],
			/**
			 * Mapped property - no scale, no transformer.
			 * Value is rest because of different value ranges: [10, 1000] vs [0, 100]
			 * and stored value is 1000 which is over mapped property accepted range.
			 */
			'read_09' => [
				new MetadataDocuments\DevicesModule\ChannelMappedProperty(
					$child01,
					MetadataTypes\PropertyType::get(MetadataTypes\PropertyType::MAPPED),
					$channel02,
					$property01,
					MetadataTypes\PropertyCategory::get(MetadataTypes\PropertyCategory::GENERIC),
					'child-property-09',
					'Child Property 09',
					MetadataTypes\DataType::get(MetadataTypes\DataType::UCHAR),
					null,
					[0, 100],
					null,
					null,
					null,
					null,
					true,
				),
				new MetadataDocuments\DevicesModule\ChannelDynamicProperty(
					$property01,
					MetadataTypes\PropertyType::get(MetadataTypes\PropertyType::DYNAMIC),
					$channel01,
					MetadataTypes\PropertyCategory::get(MetadataTypes\PropertyCategory::GENERIC),
					'test-property-09',
					'Testing Property 09',
					MetadataTypes\DataType::get(MetadataTypes\DataType::INT),
					null,
					[10, 1_000],
					null,
					null,
					null,
					null,
					true,
				),
				new Fixtures\Dummy\ChannelPropertyState(
					$property01,
					'1000',
					'1000',
					false,
					true,
				),
				null,
				null,
			],
			/**
			 * Classic property - no scale, no transformer.
			 * System value is returned because state is loaded for reading/displaying.
			 */
			'read_10' => [
				new MetadataDocuments\DevicesModule\ChannelDynamicProperty(
					$property01,
					MetadataTypes\PropertyType::get(MetadataTypes\PropertyType::DYNAMIC),
					$channel01,
					MetadataTypes\PropertyCategory::get(MetadataTypes\PropertyCategory::GENERIC),
					'test-property-10',
					'Testing Property 10',
					MetadataTypes\DataType::get(MetadataTypes\DataType::SWITCH),
					null,
					[
						[
							MetadataTypes\SwitchPayload::ON,
							'ON',
							'true',
						],
						[
							MetadataTypes\SwitchPayload::OFF,
							'OFF',
							'false',
						],
					],
					null,
					null,
					null,
					null,
					true,
				),
				null,
				new Fixtures\Dummy\ChannelPropertyState(
					$property01,
					MetadataTypes\SwitchPayload::ON,
					MetadataTypes\SwitchPayload::OFF,
					false,
					true,
				),
				MetadataTypes\SwitchPayload::get(MetadataTypes\SwitchPayload::ON),
				MetadataTypes\SwitchPayload::get(MetadataTypes\SwitchPayload::OFF),
			],
			/**
			 * Mapped property - no scale, no transformer.
			 * System value is returned because state is loaded for reading/displaying.
			 */
			'read_11' => [
				new MetadataDocuments\DevicesModule\ChannelMappedProperty(
					$child01,
					MetadataTypes\PropertyType::get(MetadataTypes\PropertyType::MAPPED),
					$channel02,
					$property01,
					MetadataTypes\PropertyCategory::get(MetadataTypes\PropertyCategory::GENERIC),
					'child-property-11',
					'Child Property 11',
					MetadataTypes\DataType::get(MetadataTypes\DataType::SWITCH),
					null,
					[
						[
							MetadataTypes\SwitchPayload::ON,
							[
								MetadataTypes\DataTypeShort::BOOLEAN,
								'true',
							],
							[
								MetadataTypes\DataTypeShort::BOOLEAN,
								'true',
							],
						],
						[
							MetadataTypes\SwitchPayload::OFF,
							[
								MetadataTypes\DataTypeShort::BOOLEAN,
								'false',
							],
							[
								MetadataTypes\DataTypeShort::BOOLEAN,
								'false',
							],
						],
					],
					null,
					null,
					null,
					null,
					true,
				),
				new MetadataDocuments\DevicesModule\ChannelDynamicProperty(
					$property01,
					MetadataTypes\PropertyType::get(MetadataTypes\PropertyType::DYNAMIC),
					$channel01,
					MetadataTypes\PropertyCategory::get(MetadataTypes\PropertyCategory::GENERIC),
					'test-property-11',
					'Testing Property 11',
					MetadataTypes\DataType::get(MetadataTypes\DataType::SWITCH),
					null,
					[
						[
							MetadataTypes\SwitchPayload::ON,
							'ON',
							'true',
						],
						[
							MetadataTypes\SwitchPayload::OFF,
							'OFF',
							'false',
						],
					],
					null,
					null,
					null,
					null,
					true,
				),
				new Fixtures\Dummy\ChannelPropertyState(
					$property01,
					MetadataTypes\SwitchPayload::ON,
					MetadataTypes\SwitchPayload::OFF,
					false,
					true,
				),
				MetadataTypes\SwitchPayload::get(MetadataTypes\SwitchPayload::ON),
				MetadataTypes\SwitchPayload::get(MetadataTypes\SwitchPayload::OFF),
			],
		];
	}

	/**
	 * @return array<string, array<MetadataDocuments\DevicesModule\ChannelDynamicProperty|MetadataDocuments\DevicesModule\ChannelMappedProperty|States\ChannelProperty|bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null>>
	 */
	public static function getStates(): array
	{
		$property01 = Uuid\Uuid::fromString('108e4a68-e184-44f2-b1ab-134f5b65dc6b');
		$child01 = Uuid\Uuid::fromString('a0f77991-1ad0-4940-aa6b-ad10094b2b2c');

		$channel01 = Uuid\Uuid::fromString('1fbc5210-01e0-412d-bdc1-5ffc7b16a098');
		$channel02 = Uuid\Uuid::fromString('8a032f16-2d54-43e5-827f-102ee9cc6e71');

		return [
			/**
			 * Classic property - no scale, no transformer.
			 */
			'get_01' => [
				new MetadataDocuments\DevicesModule\ChannelDynamicProperty(
					$property01,
					MetadataTypes\PropertyType::get(MetadataTypes\PropertyType::DYNAMIC),
					$channel01,
					MetadataTypes\PropertyCategory::get(MetadataTypes\PropertyCategory::GENERIC),
					'test-property-01',
					'Testing Property 01',
					MetadataTypes\DataType::get(MetadataTypes\DataType::FLOAT),
					null,
					null,
					null,
					null,
					null,
					null,
					true,
				),
				null,
				new Fixtures\Dummy\ChannelPropertyState(
					$property01,
					'254',
					'127',
					false,
					true,
				),
				254.0,
				127.0,
			],
			/**
			 * Classic property with scale transformer.
			 * Scale transformer is NOT applied because state is loaded for using.
			 */
			'get_02' => [
				new MetadataDocuments\DevicesModule\ChannelDynamicProperty(
					$property01,
					MetadataTypes\PropertyType::get(MetadataTypes\PropertyType::DYNAMIC),
					$channel01,
					MetadataTypes\PropertyCategory::get(MetadataTypes\PropertyCategory::GENERIC),
					'test-property-02',
					'Testing Property 02',
					MetadataTypes\DataType::get(MetadataTypes\DataType::FLOAT),
					null,
					null,
					null,
					1,
					null,
					null,
					true,
				),
				null,
				new Fixtures\Dummy\ChannelPropertyState(
					$property01,
					'254',
					'127',
					false,
					true,
				),
				254.0,
				127.0,
			],
			/**
			 * Classic property with equation transformer.
			 * Equation transformer is NOT applied because state is loaded for using.
			 */
			'get_03' => [
				new MetadataDocuments\DevicesModule\ChannelDynamicProperty(
					$property01,
					MetadataTypes\PropertyType::get(MetadataTypes\PropertyType::DYNAMIC),
					$channel01,
					MetadataTypes\PropertyCategory::get(MetadataTypes\PropertyCategory::GENERIC),
					'test-property-03',
					'Testing Property 03',
					MetadataTypes\DataType::get(MetadataTypes\DataType::FLOAT),
					null,
					null,
					null,
					null,
					null,
					'equation:x=y*10|y=x/10',
					true,
				),
				null,
				new Fixtures\Dummy\ChannelPropertyState(
					$property01,
					'254',
					'127',
					false,
					true,
				),
				254.0,
				127.0,
			],
			/**
			 * Classic property with both scale and equation transformer.
			 * Both transformers are NOT applied because state is loaded for using.
			 */
			'get_04' => [
				new MetadataDocuments\DevicesModule\ChannelDynamicProperty(
					$property01,
					MetadataTypes\PropertyType::get(MetadataTypes\PropertyType::DYNAMIC),
					$channel01,
					MetadataTypes\PropertyCategory::get(MetadataTypes\PropertyCategory::GENERIC),
					'test-property-04',
					'Testing Property 04',
					MetadataTypes\DataType::get(MetadataTypes\DataType::FLOAT),
					null,
					null,
					null,
					1,
					null,
					'equation:x=y*10|y=x/10',
					true,
				),
				null,
				new Fixtures\Dummy\ChannelPropertyState(
					$property01,
					'254',
					'127',
					false,
					true,
				),
				254.0,
				127.0,
			],
			/**
			 * Mapped property - no scale, no transformer.
			 */
			'get_05' => [
				new MetadataDocuments\DevicesModule\ChannelMappedProperty(
					$child01,
					MetadataTypes\PropertyType::get(MetadataTypes\PropertyType::MAPPED),
					$channel02,
					$property01,
					MetadataTypes\PropertyCategory::get(MetadataTypes\PropertyCategory::GENERIC),
					'child-property-05',
					'Child Property 05',
					MetadataTypes\DataType::get(MetadataTypes\DataType::UCHAR),
					null,
					null,
					null,
					null,
					null,
					null,
					true,
				),
				new MetadataDocuments\DevicesModule\ChannelDynamicProperty(
					$property01,
					MetadataTypes\PropertyType::get(MetadataTypes\PropertyType::DYNAMIC),
					$channel01,
					MetadataTypes\PropertyCategory::get(MetadataTypes\PropertyCategory::GENERIC),
					'test-property-05',
					'Testing Property 05',
					MetadataTypes\DataType::get(MetadataTypes\DataType::UCHAR),
					null,
					[0, 254],
					null,
					null,
					null,
					null,
					true,
				),
				new Fixtures\Dummy\ChannelPropertyState(
					$property01,
					'250',
					'120',
					false,
					true,
				),
				250,
				120,
			],
			/**
			 * Mapped property with scale transformer on mapped property.
			 * Scale transformer is NOT applied because state is loaded for using.
			 */
			'get_06' => [
				new MetadataDocuments\DevicesModule\ChannelMappedProperty(
					$child01,
					MetadataTypes\PropertyType::get(MetadataTypes\PropertyType::MAPPED),
					$channel02,
					$property01,
					MetadataTypes\PropertyCategory::get(MetadataTypes\PropertyCategory::GENERIC),
					'child-property-06',
					'Child Property 06',
					MetadataTypes\DataType::get(MetadataTypes\DataType::UCHAR),
					null,
					null,
					null,
					1,
					null,
					null,
					true,
				),
				new MetadataDocuments\DevicesModule\ChannelDynamicProperty(
					$property01,
					MetadataTypes\PropertyType::get(MetadataTypes\PropertyType::DYNAMIC),
					$channel01,
					MetadataTypes\PropertyCategory::get(MetadataTypes\PropertyCategory::GENERIC),
					'test-property-06',
					'Testing Property 06',
					MetadataTypes\DataType::get(MetadataTypes\DataType::UCHAR),
					null,
					[0, 254],
					null,
					null,
					null,
					null,
					true,
				),
				new Fixtures\Dummy\ChannelPropertyState(
					$property01,
					'250',
					'120',
					false,
					true,
				),
				250,
				120,
			],
			/**
			 * Mapped property with scale transformer on parent property.
			 * Scale transformer is applied because all transformers are used on parent properties.
			 */
			'get_07' => [
				new MetadataDocuments\DevicesModule\ChannelMappedProperty(
					$child01,
					MetadataTypes\PropertyType::get(MetadataTypes\PropertyType::MAPPED),
					$channel02,
					$property01,
					MetadataTypes\PropertyCategory::get(MetadataTypes\PropertyCategory::GENERIC),
					'child-property-07',
					'Child Property 07',
					MetadataTypes\DataType::get(MetadataTypes\DataType::UCHAR),
					null,
					null,
					null,
					null,
					null,
					null,
					true,
				),
				new MetadataDocuments\DevicesModule\ChannelDynamicProperty(
					$property01,
					MetadataTypes\PropertyType::get(MetadataTypes\PropertyType::DYNAMIC),
					$channel01,
					MetadataTypes\PropertyCategory::get(MetadataTypes\PropertyCategory::GENERIC),
					'test-property-07',
					'Testing Property 07',
					MetadataTypes\DataType::get(MetadataTypes\DataType::UCHAR),
					null,
					[0, 254],
					null,
					1,
					null,
					null,
					true,
				),
				new Fixtures\Dummy\ChannelPropertyState(
					$property01,
					'250',
					'120',
					false,
					true,
				),
				25,
				12,
			],
			/**
			 * Mapped property with equation transformer on parent property.
			 * Equation transformer is applied because all transformers are used on parent properties.
			 */
			'get_08' => [
				new MetadataDocuments\DevicesModule\ChannelMappedProperty(
					$child01,
					MetadataTypes\PropertyType::get(MetadataTypes\PropertyType::MAPPED),
					$channel02,
					$property01,
					MetadataTypes\PropertyCategory::get(MetadataTypes\PropertyCategory::GENERIC),
					'child-property-08',
					'Child Property 08',
					MetadataTypes\DataType::get(MetadataTypes\DataType::UCHAR),
					null,
					null,
					null,
					null,
					null,
					null,
					true,
				),
				new MetadataDocuments\DevicesModule\ChannelDynamicProperty(
					$property01,
					MetadataTypes\PropertyType::get(MetadataTypes\PropertyType::DYNAMIC),
					$channel01,
					MetadataTypes\PropertyCategory::get(MetadataTypes\PropertyCategory::GENERIC),
					'test-property-08',
					'Testing Property 08',
					MetadataTypes\DataType::get(MetadataTypes\DataType::UCHAR),
					null,
					[0, 254],
					null,
					null,
					null,
					'equation:x=y*2.54|y=x/2.54',
					true,
				),
				new Fixtures\Dummy\ChannelPropertyState(
					$property01,
					'250',
					'120',
					false,
					true,
				),
				98,
				47,
			],
			/**
			 * Mapped property with equation transformer on mapped property.
			 * Equation transformer is applied because equation transformers is used always on mapped properties
			 */
			'get_09' => [
				new MetadataDocuments\DevicesModule\ChannelMappedProperty(
					$child01,
					MetadataTypes\PropertyType::get(MetadataTypes\PropertyType::MAPPED),
					$channel02,
					$property01,
					MetadataTypes\PropertyCategory::get(MetadataTypes\PropertyCategory::GENERIC),
					'child-property-09',
					'Child Property 09',
					MetadataTypes\DataType::get(MetadataTypes\DataType::UCHAR),
					null,
					[0, 100],
					null,
					null,
					null,
					'equation:x=y/2.54|y=x*2.54',
					true,
				),
				new MetadataDocuments\DevicesModule\ChannelDynamicProperty(
					$property01,
					MetadataTypes\PropertyType::get(MetadataTypes\PropertyType::DYNAMIC),
					$channel01,
					MetadataTypes\PropertyCategory::get(MetadataTypes\PropertyCategory::GENERIC),
					'test-property-09',
					'Testing Property 09',
					MetadataTypes\DataType::get(MetadataTypes\DataType::UCHAR),
					null,
					[0, 254],
					null,
					null,
					null,
					null,
					true,
				),
				new Fixtures\Dummy\ChannelPropertyState(
					$property01,
					'250',
					'120',
					false,
					true,
				),
				98,
				47,
			],
			/**
			 * Mapped property with both scale and equation transformer on mapped property and with scale transformer on parent property.
			 * Mapped property equation transformer is applied because equation transformers is used always on mapped properties,
			 * scale transformer is NOT applied because state is loaded for using
			 * and parent property scale transformer is applied, because parent property transformers are used always on mapped properties,
			 */
			'get_10' => [
				new MetadataDocuments\DevicesModule\ChannelMappedProperty(
					$child01,
					MetadataTypes\PropertyType::get(MetadataTypes\PropertyType::MAPPED),
					$channel02,
					$property01,
					MetadataTypes\PropertyCategory::get(MetadataTypes\PropertyCategory::GENERIC),
					'child-property-10',
					'Child Property 10',
					MetadataTypes\DataType::get(MetadataTypes\DataType::UCHAR),
					null,
					[0, 100],
					null,
					1,
					null,
					'equation:x=y/10|y=x*10',
					true,
				),
				new MetadataDocuments\DevicesModule\ChannelDynamicProperty(
					$property01,
					MetadataTypes\PropertyType::get(MetadataTypes\PropertyType::DYNAMIC),
					$channel01,
					MetadataTypes\PropertyCategory::get(MetadataTypes\PropertyCategory::GENERIC),
					'test-property-10',
					'Testing Property 10',
					MetadataTypes\DataType::get(MetadataTypes\DataType::INT),
					null,
					[10, 1_000],
					null,
					1,
					null,
					null,
					true,
				),
				new Fixtures\Dummy\ChannelPropertyState(
					$property01,
					'100',
					'1000',
					false,
					true,
				),
				1,
				10,
			],
			/**
			 * Classic property - no scale, no transformer.
			 * Device value is returned because state is loaded for using.
			 */
			'get_11' => [
				new MetadataDocuments\DevicesModule\ChannelDynamicProperty(
					$property01,
					MetadataTypes\PropertyType::get(MetadataTypes\PropertyType::DYNAMIC),
					$channel01,
					MetadataTypes\PropertyCategory::get(MetadataTypes\PropertyCategory::GENERIC),
					'test-property-11',
					'Testing Property 11',
					MetadataTypes\DataType::get(MetadataTypes\DataType::SWITCH),
					null,
					[
						[
							MetadataTypes\SwitchPayload::ON,
							'ON',
							'true',
						],
						[
							MetadataTypes\SwitchPayload::OFF,
							'OFF',
							'false',
						],
					],
					null,
					null,
					null,
					null,
					true,
				),
				null,
				new Fixtures\Dummy\ChannelPropertyState(
					$property01,
					MetadataTypes\SwitchPayload::ON,
					MetadataTypes\SwitchPayload::OFF,
					false,
					true,
				),
				'true',
				'false',
			],
			/**
			 * Classic property - no scale, no transformer.
			 * Device value with data type conversion is returned because state is loaded for using.
			 */
			'get_12' => [
				new MetadataDocuments\DevicesModule\ChannelDynamicProperty(
					$property01,
					MetadataTypes\PropertyType::get(MetadataTypes\PropertyType::DYNAMIC),
					$channel01,
					MetadataTypes\PropertyCategory::get(MetadataTypes\PropertyCategory::GENERIC),
					'test-property-12',
					'Testing Property 12',
					MetadataTypes\DataType::get(MetadataTypes\DataType::SWITCH),
					null,
					[
						[
							MetadataTypes\SwitchPayload::ON,
							'ON',
							[
								MetadataTypes\DataTypeShort::BOOLEAN,
								'true',
							],
						],
						[
							MetadataTypes\SwitchPayload::OFF,
							'OFF',
							[
								MetadataTypes\DataTypeShort::BOOLEAN,
								'false',
							],
						],
					],
					null,
					null,
					null,
					null,
					true,
				),
				null,
				new Fixtures\Dummy\ChannelPropertyState(
					$property01,
					MetadataTypes\SwitchPayload::ON,
					MetadataTypes\SwitchPayload::OFF,
					false,
					true,
				),
				true,
				false,
			],
			/**
			 * Mapped property - no scale, no transformer.
			 * Device value is returned because state is loaded for using.
			 */
			'get_13' => [
				new MetadataDocuments\DevicesModule\ChannelMappedProperty(
					$child01,
					MetadataTypes\PropertyType::get(MetadataTypes\PropertyType::MAPPED),
					$channel02,
					$property01,
					MetadataTypes\PropertyCategory::get(MetadataTypes\PropertyCategory::GENERIC),
					'child-property-13',
					'Child Property 13',
					MetadataTypes\DataType::get(MetadataTypes\DataType::SWITCH),
					null,
					[
						[
							MetadataTypes\SwitchPayload::ON,
							[
								MetadataTypes\DataTypeShort::BOOLEAN,
								'true',
							],
							[
								MetadataTypes\DataTypeShort::BOOLEAN,
								'true',
							],
						],
						[
							MetadataTypes\SwitchPayload::OFF,
							[
								MetadataTypes\DataTypeShort::BOOLEAN,
								'false',
							],
							[
								MetadataTypes\DataTypeShort::BOOLEAN,
								'false',
							],
						],
					],
					null,
					null,
					null,
					null,
					true,
				),
				new MetadataDocuments\DevicesModule\ChannelDynamicProperty(
					$property01,
					MetadataTypes\PropertyType::get(MetadataTypes\PropertyType::DYNAMIC),
					$channel01,
					MetadataTypes\PropertyCategory::get(MetadataTypes\PropertyCategory::GENERIC),
					'test-property-13',
					'Testing Property 13',
					MetadataTypes\DataType::get(MetadataTypes\DataType::SWITCH),
					null,
					[
						[
							MetadataTypes\SwitchPayload::ON,
							'ON',
							'true',
						],
						[
							MetadataTypes\SwitchPayload::OFF,
							'OFF',
							'false',
						],
					],
					null,
					null,
					null,
					null,
					true,
				),
				new Fixtures\Dummy\ChannelPropertyState(
					$property01,
					MetadataTypes\SwitchPayload::ON,
					MetadataTypes\SwitchPayload::OFF,
					false,
					true,
				),
				true,
				false,
			],
		];
	}

}
