<?php declare(strict_types = 1);

/**
 * PropertyStatus.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           18.07.22
 */

namespace FastyBird\Connector\Shelly\Entities\Messages;

use FastyBird\Library\Metadata\Types as MetadataTypes;
use function is_scalar;
use function strval;

/**
 * Device or channel property status entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class PropertyStatus implements Entity
{

	public function __construct(
		private readonly string $identifier,
		private readonly float|int|string|bool|MetadataTypes\SwitchPayload|null $value,
	)
	{
	}

	public function getIdentifier(): string
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
			'identifier' => $this->getIdentifier(),
			'value' => is_scalar($this->getValue()) ? $this->getValue() : strval($this->getValue()),
		];
	}

}
