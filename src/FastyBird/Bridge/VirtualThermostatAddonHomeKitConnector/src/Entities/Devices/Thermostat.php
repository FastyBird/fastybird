<?php declare(strict_types = 1);

/**
 * Thermostat.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatAddonHomeKitConnectorBridge!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           04.02.24
 */

namespace FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Entities\Devices;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Addon\VirtualThermostat\Entities as VirtualThermostatEntities;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Exceptions;
use FastyBird\Connector\HomeKit\Entities as HomeKitEntities;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use Nette\Utils;
use Ramsey\Uuid;
use function assert;
use function sprintf;

/**
 * @ORM\Entity
 */
class Thermostat extends HomeKitEntities\Devices\Device
{

	public const TYPE = 'virtual-thermostat-addon-bridge';

	/**
	 * @param array<DevicesEntities\Devices\Device>|Utils\ArrayHash<DevicesEntities\Devices\Device> $parents
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function __construct(
		string $identifier,
		HomeKitEntities\Connectors\Connector $connector,
		array|Utils\ArrayHash $parents,
		string|null $name = null,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($identifier, $connector, $name, $id);

		$validParent = false;

		foreach ($parents as $parent) {
			if ($parent instanceof VirtualThermostatEntities\Devices\Device) {
				$validParent = true;
			}
		}

		if (!$validParent) {
			throw new Exceptions\InvalidArgument(
				sprintf(
					'At least one parent have to be instance of: %s',
					VirtualThermostatEntities\Devices\Device::class,
				),
			);
		}

		$this->setParents($parents);
	}

	public static function getType(): string
	{
		return self::TYPE;
	}

	public function getDiscriminatorName(): string
	{
		return self::TYPE;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getParent(): VirtualThermostatEntities\Devices\Device
	{
		foreach ($this->parents->toArray() as $parent) {
			if ($parent instanceof VirtualThermostatEntities\Devices\Device) {
				return $parent;
			}
		}

		throw new Exceptions\InvalidState(
			'Bridged thermostat device have to have parent virtual thermostat device defined',
		);
	}

	public function getConnector(): HomeKitEntities\Connectors\Connector
	{
		$connector = parent::getConnector();
		assert($connector instanceof HomeKitEntities\Connectors\Connector);

		return $connector;
	}

}
