<?php declare(strict_types = 1);

/**
 * SensorStatus.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Properties
 * @since          0.37.0
 *
 * @date           18.07.22
 */

namespace FastyBird\Connector\Shelly\Entities\Messages;

use FastyBird\Connector\Shelly\Types;
use FastyBird\Metadata\Types as MetadataTypes;
use Nette;
use function is_scalar;
use function strval;

/**
 * Sensor status entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Properties
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class SensorStatus implements Entity
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Types\MessageSource $source,
		private readonly int $identifier,
		private readonly float|int|string|bool|MetadataTypes\SwitchPayload|null $value,
	)
	{
	}

	public function getSource(): Types\MessageSource
	{
		return $this->source;
	}

	public function getIdentifier(): int
	{
		return $this->identifier;
	}

	public function getValue(): float|int|string|bool|MetadataTypes\SwitchPayload|null
	{
		return $this->value;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'source' => $this->getSource()->getValue(),
			'identifier' => $this->getIdentifier(),
			'value' => is_scalar($this->getValue()) ? $this->getValue() : strval($this->getValue()),
		];
	}

}
