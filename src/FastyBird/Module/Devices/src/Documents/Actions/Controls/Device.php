<?php declare(strict_types = 1);

/**
 * Device.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           01.06.22
 */

namespace FastyBird\Module\Devices\Documents\Actions\Controls;

use FastyBird\Library\Application\ObjectMapper as ApplicationObjectMapper;
use FastyBird\Library\Exchange\Documents\Mapping as EXCHANGE;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Documents\Mapping as DOC;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices;
use Orisai\ObjectMapper;
use Ramsey\Uuid;

/**
 * Device control action document
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
#[DOC\Document]
#[EXCHANGE\RoutingMap([
	Devices\Constants::MESSAGE_BUS_DEVICE_CONTROL_ACTION_ROUTING_KEY,
])]
final class Device implements MetadataDocuments\Document
{

	public function __construct(
		#[ApplicationObjectMapper\Rules\ConsistenceEnumValue(class: MetadataTypes\ControlAction::class)]
		private readonly MetadataTypes\ControlAction $action,
		#[ApplicationObjectMapper\Rules\UuidValue()]
		private readonly Uuid\UuidInterface $device,
		#[ApplicationObjectMapper\Rules\UuidValue()]
		private readonly Uuid\UuidInterface $control,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('expected_value')]
		private readonly bool|float|int|string|null $expectedValue = null,
	)
	{
	}

	public function getAction(): MetadataTypes\ControlAction
	{
		return $this->action;
	}

	public function getDevice(): Uuid\UuidInterface
	{
		return $this->device;
	}

	public function getControl(): Uuid\UuidInterface
	{
		return $this->control;
	}

	public function getExpectedValue(): float|bool|int|string|null
	{
		return $this->expectedValue;
	}

	public function toArray(): array
	{
		return [
			'action' => $this->getAction()->getValue(),
			'device' => $this->getDevice()->toString(),
			'control' => $this->getControl()->toString(),
			'expected_value' => $this->getExpectedValue(),
		];
	}

}
