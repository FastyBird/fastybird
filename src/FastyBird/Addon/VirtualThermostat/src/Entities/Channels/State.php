<?php declare(strict_types = 1);

/**
 * State.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           03.02.24
 */

namespace FastyBird\Addon\VirtualThermostat\Entities\Channels;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Addon\VirtualThermostat\Types;
use FastyBird\Connector\Virtual\Entities as VirtualEntities;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;

/**
 * @ORM\Entity
 */
class State extends VirtualEntities\Channels\Channel
{

	public const TYPE = 'virtual-thermostat-addon-state';

	public static function getType(): string
	{
		return self::TYPE;
	}

	public function getSource(): MetadataTypes\Sources\Addon
	{
		return MetadataTypes\Sources\Addon::get(MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT);
	}

	public function getDiscriminatorName(): string
	{
		return self::TYPE;
	}

	public function getHvacMode(): DevicesEntities\Channels\Properties\Dynamic|null
	{
		$property = $this->properties
			->filter(
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Channels\Properties\Property $property): bool => $property->getIdentifier() === Types\ChannelPropertyIdentifier::HVAC_MODE
			)
			->first();

		if ($property instanceof DevicesEntities\Channels\Properties\Dynamic) {
			return $property;
		}

		return null;
	}

	public function getPresetMode(): DevicesEntities\Channels\Properties\Dynamic|null
	{
		$property = $this->properties
			->filter(
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Channels\Properties\Property $property): bool => $property->getIdentifier() === Types\ChannelPropertyIdentifier::PRESET_MODE
			)
			->first();

		if ($property instanceof DevicesEntities\Channels\Properties\Dynamic) {
			return $property;
		}

		return null;
	}

}
