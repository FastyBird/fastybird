<?php declare(strict_types = 1);

/**
 * DataPointStatus.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           04.09.22
 */

namespace FastyBird\Connector\Tuya\Entities\Messages;

use FastyBird\Library\Metadata\Types as MetadataTypes;
use Nette;
use function is_scalar;
use function strval;

/**
 * Data point status entity
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DataPointStatus implements Entity
{

	use Nette\SmartObject;

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
