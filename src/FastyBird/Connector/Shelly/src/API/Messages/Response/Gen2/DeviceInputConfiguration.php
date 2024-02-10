<?php declare(strict_types = 1);

/**
 * DeviceInputConfiguration.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           26.12.22
 */

namespace FastyBird\Connector\Shelly\API\Messages\Response\Gen2;

use FastyBird\Connector\Shelly\API;
use FastyBird\Connector\Shelly\Types;
use FastyBird\Library\Application\ObjectMapper as ApplicationObjectMapper;
use Orisai\ObjectMapper;

/**
 * Generation 2 device input configuration message
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceInputConfiguration implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\IntValue(unsigned: true)]
		private readonly int $id,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly string|null $name,
		#[ApplicationObjectMapper\Rules\ConsistenceEnumValue(class: Types\InputType::class)]
		private readonly Types\InputType $type,
		#[ObjectMapper\Rules\BoolValue()]
		#[ObjectMapper\Modifiers\FieldName('invert')]
		private readonly bool $inverted,
		#[ObjectMapper\Rules\BoolValue()]
		#[ObjectMapper\Modifiers\FieldName('factory_reset')]
		private readonly bool $factoryReset,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('report_thr')]
		private readonly int|null $reportThreshold,
	)
	{
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getType(): Types\ComponentType
	{
		return Types\ComponentType::get(Types\ComponentType::INPUT);
	}

	public function getName(): string|null
	{
		return $this->name;
	}

	public function getInputType(): Types\InputType
	{
		return $this->type;
	}

	public function isInverted(): bool
	{
		return $this->inverted;
	}

	public function hasFactoryReset(): bool
	{
		return $this->factoryReset;
	}

	public function getReportThreshold(): int|null
	{
		return $this->reportThreshold;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getId(),
			'type' => $this->getType()->getValue(),
			'name' => $this->getName(),
			'input_type' => $this->getInputType()->getValue(),
			'inverted' => $this->isInverted(),
			'factory_reset' => $this->hasFactoryReset(),
			'report_threshold' => $this->getReportThreshold(),
		];
	}

}
