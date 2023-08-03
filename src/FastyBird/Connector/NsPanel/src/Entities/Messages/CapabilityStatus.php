<?php declare(strict_types = 1);

/**
 * CapabilityStatus.php
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
use FastyBird\Library\Bootstrap\ObjectMapper as BootstrapObjectMapper;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Orisai\ObjectMapper;
use Ramsey\Uuid;

/**
 * Device status definition
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class CapabilityStatus implements Entity
{

	public function __construct(
		#[BootstrapObjectMapper\Rules\UuidValue()]
		private readonly Uuid\UuidInterface $chanel,
		#[BootstrapObjectMapper\Rules\UuidValue()]
		private readonly Uuid\UuidInterface $property,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\BoolValue(),
			new BootstrapObjectMapper\Rules\ConsistenceEnumValue(class: MetadataTypes\ButtonPayload::class),
			new BootstrapObjectMapper\Rules\ConsistenceEnumValue(class: MetadataTypes\SwitchPayload::class),
			new ObjectMapper\Rules\DateTimeValue(format: DateTimeInterface::ATOM),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly float|int|string|bool|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|DateTimeInterface|null $value,
	)
	{
	}

	public function getChanel(): Uuid\UuidInterface
	{
		return $this->chanel;
	}

	public function getProperty(): Uuid\UuidInterface
	{
		return $this->property;
	}

	public function getValue(): float|int|string|bool|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|DateTimeInterface|null
	{
		return $this->value;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'channel' => $this->getChanel()->toString(),
			'property' => $this->getProperty()->toString(),
			'value' => DevicesUtilities\ValueHelper::flattenValue($this->getValue()),
		];
	}

}
