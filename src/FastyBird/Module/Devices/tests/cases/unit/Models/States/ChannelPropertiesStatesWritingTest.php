<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Models\States;

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
use Nette\DI;
use Nette\Utils;
use Ramsey\Uuid;
use Throwable;

final class ChannelPropertiesStatesWritingTest extends BaseTestCase
{

	/**
	 * @param class-string<Throwable>|null $exception
	 *
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws Error
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws DI\MissingServiceException
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 *
	 * @dataProvider writeStates
	 */
	public function testWriteState(
		MetadataDocuments\DevicesModule\ChannelDynamicProperty|MetadataDocuments\DevicesModule\ChannelMappedProperty $property,
		MetadataDocuments\DevicesModule\ChannelDynamicProperty|null $parent,
		States\ChannelProperty|null $stored,
		Utils\ArrayHash $data,
		Utils\ArrayHash $expected,
		string|null $exception,
	): void
	{
		if ($exception !== null) {
			self::expectException($exception);
		}

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

		$channelPropertiesStatesManager = $this->createMock(Models\States\Channels\Manager::class);
		$channelPropertiesStatesManager
			->expects(self::exactly($stored !== null || $exception !== null ? 0 : 1))
			->method('create')
			->with(
				self::callback(
					static function (MetadataDocuments\DevicesModule\ChannelDynamicProperty $propertyToUpdate) use ($property, $parent): bool {
						if ($parent !== null) {
							self::assertSame($parent, $propertyToUpdate, 'Property with update check');
						} else {
							self::assertSame($property, $propertyToUpdate, 'Property with update check');
						}

						return true;
					},
				),
				self::callback(static function (Utils\ArrayHash $dataToStore) use ($expected): bool {
					self::assertSame((array) $expected, (array) $dataToStore, 'Data create check');

					return true;
				}),
			);
		$channelPropertiesStatesManager
			->expects(self::exactly($stored !== null && $exception === null ? 1 : 0))
			->method('update')
			->with(
				self::callback(
					static function (MetadataDocuments\DevicesModule\ChannelDynamicProperty $propertyToUpdate) use ($property, $parent): bool {
						if ($parent !== null) {
							self::assertSame($parent, $propertyToUpdate, 'Property with update check');
						} else {
							self::assertSame($property, $propertyToUpdate, 'Property with update check');
						}

						return true;
					},
				),
				self::callback(static function (States\ChannelProperty $stateToUpdate) use ($stored): bool {
					self::assertSame($stored, $stateToUpdate, 'State update check');

					return true;
				}),
				self::callback(static function (Utils\ArrayHash $dataToStore) use ($expected): bool {
					self::assertSame((array) $expected, (array) $dataToStore, 'Data update check');

					return true;
				}),
			);

		$this->mockContainerService(
			Models\States\Channels\Manager::class,
			$channelPropertiesStatesManager,
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

		$channelPropertiesStatesManager->write(
			$property,
			$data,
			MetadataTypes\Sources\Module::get(MetadataTypes\Sources\Module::DEVICES),
		);
	}

	/**
	 * @param class-string<Throwable>|null $exception
	 *
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws Error
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws DI\MissingServiceException
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 *
	 * @dataProvider setStates
	 */
	public function testSetState(
		MetadataDocuments\DevicesModule\ChannelDynamicProperty|MetadataDocuments\DevicesModule\ChannelMappedProperty $property,
		MetadataDocuments\DevicesModule\ChannelDynamicProperty|null $parent,
		States\ChannelProperty|null $stored,
		Utils\ArrayHash $data,
		Utils\ArrayHash $expected,
		string|null $exception,
	): void
	{
		if ($exception !== null) {
			self::expectException($exception);
		}

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

		$channelPropertiesStatesManager = $this->createMock(Models\States\Channels\Manager::class);
		$channelPropertiesStatesManager
			->expects(self::exactly($stored !== null || $exception !== null ? 0 : 1))
			->method('create')
			->with(
				self::callback(
					static function (MetadataDocuments\DevicesModule\ChannelDynamicProperty $propertyToUpdate) use ($property, $parent): bool {
						if ($parent !== null) {
							self::assertSame($parent, $propertyToUpdate, 'Property with update check');
						} else {
							self::assertSame($property, $propertyToUpdate, 'Property with update check');
						}

						return true;
					},
				),
				self::callback(static function (Utils\ArrayHash $dataToStore) use ($expected): bool {
					self::assertSame((array) $expected, (array) $dataToStore, 'Data create check');

					return true;
				}),
			);
		$channelPropertiesStatesManager
			->expects(self::exactly($stored !== null && $exception === null ? 1 : 0))
			->method('update')
			->with(
				self::callback(
					static function (MetadataDocuments\DevicesModule\ChannelDynamicProperty $propertyToUpdate) use ($property, $parent): bool {
						if ($parent !== null) {
							self::assertSame($parent, $propertyToUpdate, 'Property with update check');
						} else {
							self::assertSame($property, $propertyToUpdate, 'Property with update check');
						}

						return true;
					},
				),
				self::callback(static function (States\ChannelProperty $stateToUpdate) use ($stored): bool {
					self::assertSame($stored, $stateToUpdate, 'State update check');

					return true;
				}),
				self::callback(static function (Utils\ArrayHash $dataToStore) use ($expected): bool {
					self::assertSame((array) $expected, (array) $dataToStore, 'Data update check');

					return true;
				}),
			);

		$this->mockContainerService(
			Models\States\Channels\Manager::class,
			$channelPropertiesStatesManager,
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

		$channelPropertiesStatesManager->set(
			$property,
			$data,
			MetadataTypes\Sources\Module::get(MetadataTypes\Sources\Module::DEVICES),
		);
	}

	/**
	 * @return array<string, array<MetadataDocuments\DevicesModule\ChannelDynamicProperty|MetadataDocuments\DevicesModule\ChannelMappedProperty|States\ChannelProperty|Utils\ArrayHash|string|null>>
	 */
	public static function writeStates(): array
	{
		$property01 = Uuid\Uuid::fromString('108e4a68-e184-44f2-b1ab-134f5b65dc6b');
		$child01 = Uuid\Uuid::fromString('a0f77991-1ad0-4940-aa6b-ad10094b2b2c');

		$channel01 = Uuid\Uuid::fromString('1fbc5210-01e0-412d-bdc1-5ffc7b16a098');
		$channel02 = Uuid\Uuid::fromString('8a032f16-2d54-43e5-827f-102ee9cc6e71');

		return [
			/**
			 * Classic property - no scale, no transformer.
			 */
			'write_01' => [
				new MetadataDocuments\DevicesModule\ChannelDynamicProperty(
					$property01,
					MetadataTypes\PropertyType::DYNAMIC,
					$channel01,
					MetadataTypes\PropertyCategory::GENERIC,
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
				null,
				Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => '255',
				]),
				Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => 255.0,
					States\Property::PENDING_FIELD => true,
				]),
				null,
			],
			/**
			 * Classic property with scale transformer.
			 * Scale transformer is applied because state is written from user interface.
			 */
			'write_02' => [
				new MetadataDocuments\DevicesModule\ChannelDynamicProperty(
					$property01,
					MetadataTypes\PropertyType::DYNAMIC,
					$channel01,
					MetadataTypes\PropertyCategory::GENERIC,
					'test-property-02',
					'Testing Property 02',
					MetadataTypes\DataType::get(MetadataTypes\DataType::FLOAT),
					null,
					[100, 300],
					null,
					1,
					null,
					null,
					true,
				),
				null,
				null,
				Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => '25.5',
				]),
				Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => 255.0,
					States\Property::PENDING_FIELD => true,
				]),
				null,
			],
			/**
			 * Classic property with equation transformer.
			 * Equation transformer is applied because state is written from user interface.
			 */
			'write_03' => [
				new MetadataDocuments\DevicesModule\ChannelDynamicProperty(
					$property01,
					MetadataTypes\PropertyType::DYNAMIC,
					$channel01,
					MetadataTypes\PropertyCategory::GENERIC,
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
				null,
				Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => '25.5',
				]),
				Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => 255.0,
					States\Property::PENDING_FIELD => true,
				]),
				null,
			],
			/**
			 * Classic property with both scale and equation transformer.
			 * Both transformers are applied because state is written from user interface.
			 */
			'write_04' => [
				new MetadataDocuments\DevicesModule\ChannelDynamicProperty(
					$property01,
					MetadataTypes\PropertyType::DYNAMIC,
					$channel01,
					MetadataTypes\PropertyCategory::GENERIC,
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
				null,
				Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => '25.5',
				]),
				Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => 2_550.0,
					States\Property::PENDING_FIELD => true,
				]),
				null,
			],
			/**
			 * Mapped property - no scale, no transformer.
			 */
			'write_05' => [
				new MetadataDocuments\DevicesModule\ChannelMappedProperty(
					$child01,
					MetadataTypes\PropertyType::MAPPED,
					$channel02,
					$property01,
					MetadataTypes\PropertyCategory::GENERIC,
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
					MetadataTypes\PropertyType::DYNAMIC,
					$channel01,
					MetadataTypes\PropertyCategory::GENERIC,
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
				null,
				Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => 127.0,
				]),
				Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => 127.0,
					States\Property::PENDING_FIELD => true,
				]),
				null,
			],
			/**
			 * Mapped property with scale transformer on mapped property.
			 * Scale transformer is applied because state is written from user interface.
			 */
			'write_06' => [
				new MetadataDocuments\DevicesModule\ChannelMappedProperty(
					$child01,
					MetadataTypes\PropertyType::MAPPED,
					$channel02,
					$property01,
					MetadataTypes\PropertyCategory::GENERIC,
					'child-property-06',
					'Child Property 06',
					MetadataTypes\DataType::get(MetadataTypes\DataType::UCHAR),
					null,
					[0, 100],
					null,
					1,
					null,
					null,
					true,
				),
				new MetadataDocuments\DevicesModule\ChannelDynamicProperty(
					$property01,
					MetadataTypes\PropertyType::DYNAMIC,
					$channel01,
					MetadataTypes\PropertyCategory::GENERIC,
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
				null,
				Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => 5,
				]),
				Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => 50,
					States\Property::PENDING_FIELD => true,
				]),
				null,
			],
			/**
			 * Mapped property with equation transformer on mapped property.
			 * Equation transformer is applied because equation transformers is written from user interface.
			 */
			'write_07' => [
				new MetadataDocuments\DevicesModule\ChannelMappedProperty(
					$child01,
					MetadataTypes\PropertyType::MAPPED,
					$channel02,
					$property01,
					MetadataTypes\PropertyCategory::GENERIC,
					'child-property-07',
					'Child Property 07',
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
					MetadataTypes\PropertyType::DYNAMIC,
					$channel01,
					MetadataTypes\PropertyCategory::GENERIC,
					'test-property-07',
					'Testing Property 07',
					MetadataTypes\DataType::get(MetadataTypes\DataType::UCHAR),
					null,
					[0, 254],
					null,
					null,
					null,
					null,
					true,
				),
				null,
				Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => 47,
				]),
				Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => 119,
					States\Property::PENDING_FIELD => true,
				]),
				null,
			],
			/**
			 * Mapped property with both scale and equation transformer on mapped property.
			 * Both transformers are applied because equation transformers is written from user interface.
			 */
			'write_08' => [
				new MetadataDocuments\DevicesModule\ChannelMappedProperty(
					$child01,
					MetadataTypes\PropertyType::MAPPED,
					$channel02,
					$property01,
					MetadataTypes\PropertyCategory::GENERIC,
					'child-property-08',
					'Child Property 08',
					MetadataTypes\DataType::get(MetadataTypes\DataType::UCHAR),
					null,
					[0, 100],
					null,
					1,
					null,
					'equation:x=y/2.54|y=x*2.54',
					true,
				),
				new MetadataDocuments\DevicesModule\ChannelDynamicProperty(
					$property01,
					MetadataTypes\PropertyType::DYNAMIC,
					$channel01,
					MetadataTypes\PropertyCategory::GENERIC,
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
				null,
				Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => 4.7,
				]),
				Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => 102,
					States\Property::PENDING_FIELD => true,
				]),
				null,
			],
			/**
			 * Classic property - no scale, no transformer.
			 * Write value is ignored because is null.
			 */
			'write_09' => [
				new MetadataDocuments\DevicesModule\ChannelDynamicProperty(
					$property01,
					MetadataTypes\PropertyType::DYNAMIC,
					$channel01,
					MetadataTypes\PropertyCategory::GENERIC,
					'test-property-09',
					'Testing Property 09',
					MetadataTypes\DataType::get(MetadataTypes\DataType::FLOAT),
					null,
					null,
					null,
					null,
					null,
					null,
				),
				null,
				null,
				Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => null,
				]),
				Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => null,
					States\Property::PENDING_FIELD => false,
				]),
				null,
			],
			/**
			 * Classic property - no scale, no transformer.
			 * Write value is ignored because is empty.
			 */
			'write_10' => [
				new MetadataDocuments\DevicesModule\ChannelDynamicProperty(
					$property01,
					MetadataTypes\PropertyType::DYNAMIC,
					$channel01,
					MetadataTypes\PropertyCategory::GENERIC,
					'test-property-10',
					'Testing Property 10',
					MetadataTypes\DataType::get(MetadataTypes\DataType::FLOAT),
					null,
					null,
					null,
					null,
					null,
					null,
				),
				null,
				null,
				Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => '',
				]),
				Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => null,
					States\Property::PENDING_FIELD => false,
				]),
				null,
			],
			/**
			 * Classic property - no scale, no transformer.
			 * Error is triggered, storing actual value is not allowed
			 */
			'write_11' => [
				new MetadataDocuments\DevicesModule\ChannelDynamicProperty(
					$property01,
					MetadataTypes\PropertyType::DYNAMIC,
					$channel01,
					MetadataTypes\PropertyCategory::GENERIC,
					'test-property-11',
					'Testing Property 11',
					MetadataTypes\DataType::get(MetadataTypes\DataType::FLOAT),
					null,
					null,
					null,
					null,
					null,
					null,
				),
				null,
				null,
				Utils\ArrayHash::from([
					States\Property::ACTUAL_VALUE_FIELD => 10,
				]),
				Utils\ArrayHash::from([
					States\Property::ACTUAL_VALUE_FIELD => 10,
					States\Property::PENDING_FIELD => false,
				]),
				Exceptions\InvalidArgument::class,
			],
		];
	}

	/**
	 * @return array<string, array<MetadataDocuments\DevicesModule\ChannelDynamicProperty|MetadataDocuments\DevicesModule\ChannelMappedProperty|States\ChannelProperty|Utils\ArrayHash|string|null>>
	 */
	public static function setStates(): array
	{
		$property01 = Uuid\Uuid::fromString('108e4a68-e184-44f2-b1ab-134f5b65dc6b');
		$child01 = Uuid\Uuid::fromString('a0f77991-1ad0-4940-aa6b-ad10094b2b2c');

		$channel01 = Uuid\Uuid::fromString('1fbc5210-01e0-412d-bdc1-5ffc7b16a098');
		$channel02 = Uuid\Uuid::fromString('8a032f16-2d54-43e5-827f-102ee9cc6e71');

		return [
			/**
			 * Classic property - no scale, no transformer.
			 */
			'set_01' => [
				new MetadataDocuments\DevicesModule\ChannelDynamicProperty(
					$property01,
					MetadataTypes\PropertyType::DYNAMIC,
					$channel01,
					MetadataTypes\PropertyCategory::GENERIC,
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
				null,
				Utils\ArrayHash::from([
					States\Property::ACTUAL_VALUE_FIELD => '100',
					States\Property::EXPECTED_VALUE_FIELD => '255',
				]),
				Utils\ArrayHash::from([
					States\Property::ACTUAL_VALUE_FIELD => 100.0,
					States\Property::EXPECTED_VALUE_FIELD => 255.0,
					States\Property::VALID_FIELD => true,
					States\Property::PENDING_FIELD => true,
				]),
				null,
			],
			/**
			 * Classic property with scale transformer.
			 * Scale transformer is NOT applied because state is written from device.
			 */
			'set_02' => [
				new MetadataDocuments\DevicesModule\ChannelDynamicProperty(
					$property01,
					MetadataTypes\PropertyType::DYNAMIC,
					$channel01,
					MetadataTypes\PropertyCategory::GENERIC,
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
				null,
				Utils\ArrayHash::from([
					States\Property::ACTUAL_VALUE_FIELD => '100',
					States\Property::EXPECTED_VALUE_FIELD => '255',
				]),
				Utils\ArrayHash::from([
					States\Property::ACTUAL_VALUE_FIELD => 100.0,
					States\Property::EXPECTED_VALUE_FIELD => 255.0,
					States\Property::VALID_FIELD => true,
					States\Property::PENDING_FIELD => true,
				]),
				null,
			],
			/**
			 * Classic property with equation transformer.
			 * Equation transformer is NOT applied because state is written from device.
			 */
			'set_03' => [
				new MetadataDocuments\DevicesModule\ChannelDynamicProperty(
					$property01,
					MetadataTypes\PropertyType::DYNAMIC,
					$channel01,
					MetadataTypes\PropertyCategory::GENERIC,
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
				null,
				Utils\ArrayHash::from([
					States\Property::ACTUAL_VALUE_FIELD => '100',
					States\Property::EXPECTED_VALUE_FIELD => '255',
				]),
				Utils\ArrayHash::from([
					States\Property::ACTUAL_VALUE_FIELD => 100.0,
					States\Property::EXPECTED_VALUE_FIELD => 255.0,
					States\Property::VALID_FIELD => true,
					States\Property::PENDING_FIELD => true,
				]),
				null,
			],
			/**
			 * Classic property with both scale and equation transformer.
			 * Both transformers are NOT applied because state is written from device.
			 */
			'set_04' => [
				new MetadataDocuments\DevicesModule\ChannelDynamicProperty(
					$property01,
					MetadataTypes\PropertyType::DYNAMIC,
					$channel01,
					MetadataTypes\PropertyCategory::GENERIC,
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
				null,
				Utils\ArrayHash::from([
					States\Property::ACTUAL_VALUE_FIELD => '100',
					States\Property::EXPECTED_VALUE_FIELD => '255',
				]),
				Utils\ArrayHash::from([
					States\Property::ACTUAL_VALUE_FIELD => 100.0,
					States\Property::EXPECTED_VALUE_FIELD => 255.0,
					States\Property::VALID_FIELD => true,
					States\Property::PENDING_FIELD => true,
				]),
				null,
			],
			/**
			 * Classic property - no scale, no transformer.
			 * Provided values are ignored because are out of allowed range
			 */
			'set_05' => [
				new MetadataDocuments\DevicesModule\ChannelDynamicProperty(
					$property01,
					MetadataTypes\PropertyType::DYNAMIC,
					$channel01,
					MetadataTypes\PropertyCategory::GENERIC,
					'test-property-05',
					'Testing Property 05',
					MetadataTypes\DataType::get(MetadataTypes\DataType::FLOAT),
					null,
					[0, 100],
					null,
					null,
					null,
					null,
					true,
				),
				null,
				null,
				Utils\ArrayHash::from([
					States\Property::ACTUAL_VALUE_FIELD => '1000',
					States\Property::EXPECTED_VALUE_FIELD => '255',
				]),
				Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => null,
					States\Property::VALID_FIELD => false,
					States\Property::PENDING_FIELD => false,
				]),
				null,
			],
			/**
			 * Classic property - no scale, no transformer.
			 * Provided actual values is ignored because is out of allowed range
			 */
			'set_06' => [
				new MetadataDocuments\DevicesModule\ChannelDynamicProperty(
					$property01,
					MetadataTypes\PropertyType::DYNAMIC,
					$channel01,
					MetadataTypes\PropertyCategory::GENERIC,
					'test-property-06',
					'Testing Property 06',
					MetadataTypes\DataType::get(MetadataTypes\DataType::FLOAT),
					null,
					[0, 100],
					null,
					null,
					null,
					null,
					true,
				),
				null,
				null,
				Utils\ArrayHash::from([
					States\Property::ACTUAL_VALUE_FIELD => '1000',
					States\Property::EXPECTED_VALUE_FIELD => '55',
				]),
				Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => 55.0,
					States\Property::VALID_FIELD => false,
					States\Property::PENDING_FIELD => true,
				]),
				null,
			],
			/**
			 * Classic property - no scale, no transformer.
			 * Provided actual values is marked as INVALID and reset
			 */
			'set_07' => [
				new MetadataDocuments\DevicesModule\ChannelDynamicProperty(
					$property01,
					MetadataTypes\PropertyType::DYNAMIC,
					$channel01,
					MetadataTypes\PropertyCategory::GENERIC,
					'test-property-07',
					'Testing Property 07',
					MetadataTypes\DataType::get(MetadataTypes\DataType::FLOAT),
					null,
					[0, 100],
					'999',
					null,
					null,
					null,
					true,
				),
				null,
				null,
				Utils\ArrayHash::from([
					States\Property::ACTUAL_VALUE_FIELD => '999',
					States\Property::EXPECTED_VALUE_FIELD => '55',
				]),
				Utils\ArrayHash::from([
					States\Property::ACTUAL_VALUE_FIELD => null,
					States\Property::EXPECTED_VALUE_FIELD => 55.0,
					States\Property::VALID_FIELD => false,
					States\Property::PENDING_FIELD => true,
				]),
				null,
			],
			/**
			 * Mapped property - no scale, no transformer.
			 */
			'set_08' => [
				new MetadataDocuments\DevicesModule\ChannelMappedProperty(
					$child01,
					MetadataTypes\PropertyType::MAPPED,
					$channel02,
					$property01,
					MetadataTypes\PropertyCategory::GENERIC,
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
					MetadataTypes\PropertyType::DYNAMIC,
					$channel01,
					MetadataTypes\PropertyCategory::GENERIC,
					'test-property-08',
					'Testing Property 08',
					MetadataTypes\DataType::get(MetadataTypes\DataType::FLOAT),
					null,
					[0, 100],
					null,
					null,
					null,
					null,
					true,
				),
				null,
				Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => '55',
				]),
				Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => 55.0,
					States\Property::PENDING_FIELD => true,
				]),
				null,
			],
			/**
			 * Mapped property with scale transformer on mapped property.
			 * Scale transformer is NOT applied because state is written from device.
			 */
			'set_09' => [
				new MetadataDocuments\DevicesModule\ChannelMappedProperty(
					$child01,
					MetadataTypes\PropertyType::MAPPED,
					$channel02,
					$property01,
					MetadataTypes\PropertyCategory::GENERIC,
					'child-property-09',
					'Child Property 09',
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
					MetadataTypes\PropertyType::DYNAMIC,
					$channel01,
					MetadataTypes\PropertyCategory::GENERIC,
					'test-property-09',
					'Testing Property 09',
					MetadataTypes\DataType::get(MetadataTypes\DataType::FLOAT),
					null,
					[0, 100],
					null,
					null,
					null,
					null,
					true,
				),
				null,
				Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => '55',
				]),
				Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => 55.0,
					States\Property::PENDING_FIELD => true,
				]),
				null,
			],
			/**
			 * Mapped property with scale transformer on parent property.
			 * Scale transformer is NOT applied because state is written from device.
			 */
			'set_10' => [
				new MetadataDocuments\DevicesModule\ChannelMappedProperty(
					$child01,
					MetadataTypes\PropertyType::MAPPED,
					$channel02,
					$property01,
					MetadataTypes\PropertyCategory::GENERIC,
					'child-property-10',
					'Child Property 10',
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
					MetadataTypes\PropertyType::DYNAMIC,
					$channel01,
					MetadataTypes\PropertyCategory::GENERIC,
					'test-property-10',
					'Testing Property 10',
					MetadataTypes\DataType::get(MetadataTypes\DataType::FLOAT),
					null,
					[0, 1_000],
					null,
					1,
					null,
					null,
					true,
				),
				null,
				Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => '55',
				]),
				Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => 550.0,
					States\Property::PENDING_FIELD => true,
				]),
				null,
			],
			/**
			 * Mapped property with equation transformer on mapped property.
			 * Equation transformer is applied because equation transformers is used always on mapped properties
			 */
			'set_11' => [
				new MetadataDocuments\DevicesModule\ChannelMappedProperty(
					$child01,
					MetadataTypes\PropertyType::MAPPED,
					$channel02,
					$property01,
					MetadataTypes\PropertyCategory::GENERIC,
					'child-property-11',
					'Child Property 11',
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
					MetadataTypes\PropertyType::DYNAMIC,
					$channel01,
					MetadataTypes\PropertyCategory::GENERIC,
					'test-property-11',
					'Testing Property 11',
					MetadataTypes\DataType::get(MetadataTypes\DataType::UCHAR),
					null,
					[0, 254],
					null,
					null,
					null,
					null,
					true,
				),
				null,
				Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => '98',
				]),
				Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => 249,
					States\Property::PENDING_FIELD => true,
				]),
				null,
			],
			/**
			 * Mapped property with both scale and equation transformer on mapped property and with scale transformer on parent property.
			 * Mapped property equation transformer is applied because equation transformers is used always on mapped properties,
			 * scale transformer is NOT applied because state is written from device
			 * and parent property scale transformer is applied, because parent property transformers are used always on mapped properties,
			 */
			'set_12' => [
				new MetadataDocuments\DevicesModule\ChannelMappedProperty(
					$child01,
					MetadataTypes\PropertyType::MAPPED,
					$channel02,
					$property01,
					MetadataTypes\PropertyCategory::GENERIC,
					'child-property-12',
					'Child Property 12',
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
					MetadataTypes\PropertyType::DYNAMIC,
					$channel01,
					MetadataTypes\PropertyCategory::GENERIC,
					'test-property-12',
					'Testing Property 12',
					MetadataTypes\DataType::get(MetadataTypes\DataType::UCHAR),
					null,
					[10, 1_000],
					null,
					1,
					null,
					null,
					true,
				),
				null,
				Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => '1',
				]),
				Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => 100,
					States\Property::PENDING_FIELD => true,
				]),
				null,
			],
			/**
			 * Real world example: Tuya dimmer with int range [10, 1000] and HomeKit light bulb service with range [1, 100]
			 */
			'set_13' => [
				new MetadataDocuments\DevicesModule\ChannelMappedProperty(
					$child01,
					MetadataTypes\PropertyType::MAPPED,
					$channel02,
					$property01,
					MetadataTypes\PropertyCategory::GENERIC,
					'child-property-13',
					'Child Property 13',
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
					MetadataTypes\PropertyType::DYNAMIC,
					$channel01,
					MetadataTypes\PropertyCategory::GENERIC,
					'test-property-13',
					'Testing Property 13',
					MetadataTypes\DataType::get(MetadataTypes\DataType::UCHAR),
					null,
					[10, 1_000],
					null,
					1,
					null,
					null,
					true,
				),
				null,
				Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => '1',
				]),
				Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => 10,
					States\Property::PENDING_FIELD => true,
				]),
				null,
			],
			/**
			 * Real world example: Zigbee2MQTT dimmer with int range [0, 254] and HomeKit light bulb service with range [1, 100]
			 */
			'set_14' => [
				new MetadataDocuments\DevicesModule\ChannelMappedProperty(
					$child01,
					MetadataTypes\PropertyType::MAPPED,
					$channel02,
					$property01,
					MetadataTypes\PropertyCategory::GENERIC,
					'child-property-14',
					'Child Property 14',
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
					MetadataTypes\PropertyType::DYNAMIC,
					$channel01,
					MetadataTypes\PropertyCategory::GENERIC,
					'test-property-14',
					'Testing Property 14',
					MetadataTypes\DataType::get(MetadataTypes\DataType::UCHAR),
					null,
					[0, 254],
					null,
					null,
					null,
					null,
					true,
				),
				null,
				Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => '50',
				]),
				Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => 127,
					States\Property::PENDING_FIELD => true,
				]),
				null,
			],
			/**
			 * Actual value could be set only to dynamic property
			 */
			'set_15' => [
				new MetadataDocuments\DevicesModule\ChannelMappedProperty(
					$child01,
					MetadataTypes\PropertyType::MAPPED,
					$channel02,
					$property01,
					MetadataTypes\PropertyCategory::GENERIC,
					'child-property-15',
					'Child Property 15',
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
					MetadataTypes\PropertyType::DYNAMIC,
					$channel01,
					MetadataTypes\PropertyCategory::GENERIC,
					'test-property-15',
					'Testing Property 15',
					MetadataTypes\DataType::get(MetadataTypes\DataType::UCHAR),
					null,
					[0, 254],
					null,
					null,
					null,
					null,
					true,
				),
				null,
				Utils\ArrayHash::from([
					States\Property::ACTUAL_VALUE_FIELD => '50',
				]),
				Utils\ArrayHash::from([
					States\Property::ACTUAL_VALUE_FIELD => 127,
					States\Property::VALID_FIELD => true,
				]),
				Exceptions\InvalidArgument::class,
			],
			'set_16' => [
				new MetadataDocuments\DevicesModule\ChannelMappedProperty(
					$child01,
					MetadataTypes\PropertyType::MAPPED,
					$channel02,
					$property01,
					MetadataTypes\PropertyCategory::GENERIC,
					'child-property-16',
					'Child Property 16',
					MetadataTypes\DataType::get(MetadataTypes\DataType::SWITCH),
					null,
					[
						[
							MetadataTypes\Payloads\Switcher::ON,
							[
								MetadataTypes\DataTypeShort::BOOLEAN,
								true,
							],
							[
								MetadataTypes\DataTypeShort::BOOLEAN,
								true,
							],
						],
						[
							MetadataTypes\Payloads\Switcher::OFF,
							[
								MetadataTypes\DataTypeShort::BOOLEAN,
								false,
							],
							[
								MetadataTypes\DataTypeShort::BOOLEAN,
								false,
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
					MetadataTypes\PropertyType::DYNAMIC,
					$channel01,
					MetadataTypes\PropertyCategory::GENERIC,
					'test-property-16',
					'Testing Property 16',
					MetadataTypes\DataType::get(MetadataTypes\DataType::SWITCH),
					null,
					[
						[
							MetadataTypes\Payloads\Switcher::ON,
							'ON',
							'ON',
						],
						[
							MetadataTypes\Payloads\Switcher::OFF,
							'OFF',
							'OFF',
						],
					],
					null,
					null,
					null,
					null,
					true,
				),
				null,
				Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => true,
				]),
				Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => MetadataTypes\Payloads\Switcher::ON,
					States\Property::PENDING_FIELD => true,
				]),
				null,
			],
		];
	}

}
