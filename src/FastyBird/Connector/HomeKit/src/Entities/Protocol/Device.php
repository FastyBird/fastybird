<?php declare(strict_types = 1);

/**
 * Bridge.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Entities
 * @since          0.19.0
 *
 * @date           13.09.22
 */

namespace FastyBird\Connector\HomeKit\Entities\Protocol;

use FastyBird\Connector\HomeKit\Entities;
use FastyBird\Connector\HomeKit\Types;
use Ramsey\Uuid;

/**
 * HAP bridge accessory
 *
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Device extends Accessory
{

	public function __construct(
		string $name,
		int|null $aid,
		Types\AccessoryCategory $category,
		private readonly Entities\HomeKitDevice $device,
	)
	{
		parent::__construct($name, $aid, $category);
	}

	public function getId(): Uuid\UuidInterface
	{
		return $this->device->getId();
	}

	public function getDevice(): Entities\HomeKitDevice
	{
		return $this->device;
	}

}
