<?php declare(strict_types = 1);

/**
 * DataPointStatus.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           15.07.23
 */

namespace FastyBird\Connector\NsPanel\Entities\Messages;

use DateTimeInterface;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;

/**
 * Device capability status entity
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class CapabilityStatus implements Entity
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Types\Capability $capability,
		private readonly float|int|string|bool|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|DateTimeInterface|null $value,
		private readonly string|null $name,
	)
	{
	}

	public function getCapability(): Types\Capability
	{
		return $this->capability;
	}

	public function getValue(): float|int|string|bool|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|DateTimeInterface|null
	{
		return $this->value;
	}

	public function getName(): string|null
	{
		return $this->name;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'capability' => $this->getCapability()->getValue(),
			'value' => DevicesUtilities\ValueHelper::flattenValue($this->getValue()),
			'name' => $this->getName(),
		];
	}

}
