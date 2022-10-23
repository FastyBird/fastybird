<?php declare(strict_types = 1);

/**
 * DevicePropertiesManager.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbDevicesModuleBridge!
 * @subpackage     Models
 * @since          0.1.0
 *
 * @date           20.10.22
 */

namespace FastyBird\Bridge\RedisDbDevicesModule\Models;

use FastyBird\Bridge\RedisDbDevicesModule\States;
use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\States as DevicesStates;
use FastyBird\Plugin\RedisDb\Exceptions as RedisDbExceptions;
use FastyBird\Plugin\RedisDb\Models as RedisDbModels;
use Nette;
use Nette\Utils;
use function assert;

/**
 * Device property states manager
 *
 * @package        FastyBird:RedisDbDevicesModuleBridge!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class DevicePropertiesManager implements DevicesModels\States\IDevicePropertiesManager
{

	use Nette\SmartObject;

	/** @var RedisDbModels\StatesManager<States\DeviceProperty> */
	private RedisDbModels\StatesManager $statesManager;

	/**
	 * @phpstan-param  RedisDbModels\StatesManagerFactory<States\DeviceProperty> $statesManagerFactory
	 *
	 * @throws RedisDbExceptions\InvalidArgument
	 */
	public function __construct(
		RedisDbModels\StatesManagerFactory $statesManagerFactory,
		private readonly int $database = 0,
	)
	{
		$this->statesManager = $statesManagerFactory->create(States\DeviceProperty::class);
	}

	/**
	 * @throws RedisDbExceptions\InvalidState
	 */
	public function create(
		MetadataEntities\DevicesModule\DeviceDynamicProperty|MetadataEntities\DevicesModule\DeviceMappedProperty|DevicesEntities\Devices\Properties\Dynamic|DevicesEntities\Devices\Properties\Mapped $property,
		Utils\ArrayHash $values,
	): States\DeviceProperty
	{
		return $this->statesManager->create($property->getId(), $values, $this->database);
	}

	/**
	 * @throws RedisDbExceptions\InvalidState
	 */
	public function update(
		MetadataEntities\DevicesModule\DeviceDynamicProperty|MetadataEntities\DevicesModule\DeviceMappedProperty|DevicesEntities\Devices\Properties\Dynamic|DevicesEntities\Devices\Properties\Mapped $property,
		DevicesStates\DeviceProperty $state,
		Utils\ArrayHash $values,
	): States\DeviceProperty
	{
		assert($state instanceof States\DeviceProperty);

		return $this->statesManager->update($state, $values, $this->database);
	}

	public function delete(
		MetadataEntities\DevicesModule\DeviceDynamicProperty|MetadataEntities\DevicesModule\DeviceMappedProperty|DevicesEntities\Devices\Properties\Dynamic|DevicesEntities\Devices\Properties\Mapped $property,
		DevicesStates\DeviceProperty $state,
	): bool
	{
		assert($state instanceof States\DeviceProperty);

		return $this->statesManager->delete($state, $this->database);
	}

}
