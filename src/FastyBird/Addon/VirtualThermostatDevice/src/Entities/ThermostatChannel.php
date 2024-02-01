<?php declare(strict_types = 1);

/**
 * ThermostatChannel.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatDeviceAddon!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           20.10.23
 */

namespace FastyBird\Addon\VirtualThermostatDevice\Entities;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Connector\Virtual\Entities as VirtualEntities;
use FastyBird\Library\Metadata\Types as MetadataTypes;

/**
 * @ORM\Entity
 */
class ThermostatChannel extends VirtualEntities\VirtualChannel
{

	public const TYPE = 'virtual-thermostat-device-addon';

	public static function getType(): string
	{
		return self::TYPE;
	}

	public function getDiscriminatorName(): string
	{
		return self::TYPE;
	}

	public function getSource(): MetadataTypes\ConnectorSource
	{
		return MetadataTypes\ConnectorSource::get(MetadataTypes\ConnectorSource::VIRTUAL);
	}

}