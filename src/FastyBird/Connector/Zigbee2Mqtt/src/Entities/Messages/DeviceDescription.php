<?php declare(strict_types = 1);

/**
 * DeviceDescription.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           24.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Entities\Messages;

use FastyBird\Connector\Zigbee2Mqtt\Types;
use FastyBird\Library\Application\ObjectMapper as ApplicationObjectMapper;
use Orisai\ObjectMapper;
use function array_map;

/**
 * Device description message
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceDescription implements Entity
{

	/**
	 * @param array<Scene> $scenes
	 */
	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('friendly_name')]
		private readonly string $friendlyName,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('ieee_address')]
		private readonly string $ieeeAddress,
		#[ObjectMapper\Rules\IntValue(unsigned: true)]
		#[ObjectMapper\Modifiers\FieldName('network_address')]
		private readonly int $networkAddress,
		#[ObjectMapper\Rules\BoolValue()]
		#[ObjectMapper\Modifiers\FieldName('interview_completed')]
		private readonly bool $interviewCompleted,
		#[ObjectMapper\Rules\BoolValue()]
		private readonly bool $interviewing,
		#[ApplicationObjectMapper\Rules\ConsistenceEnumValue(class: Types\DeviceType::class)]
		private readonly Types\DeviceType $type,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(DeviceDefinition::class),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly DeviceDefinition|null $definition,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly string|null $description = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly string|null $manufacturer = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('model_id')]
		private readonly string|null $modelId = null,
		#[ObjectMapper\Rules\BoolValue()]
		private readonly bool $supported = true,
		#[ObjectMapper\Rules\BoolValue()]
		private readonly bool $disabled = false,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(class: Scene::class),
			new ObjectMapper\Rules\IntValue(unsigned: true),
		)]
		private readonly array $scenes = [],
	)
	{
	}

	public function getFriendlyName(): string
	{
		return $this->friendlyName;
	}

	public function getIeeeAddress(): string
	{
		return $this->ieeeAddress;
	}

	public function getNetworkAddress(): int
	{
		return $this->networkAddress;
	}

	public function isInterviewCompleted(): bool
	{
		return $this->interviewCompleted;
	}

	public function isInterviewing(): bool
	{
		return $this->interviewing;
	}

	public function getType(): Types\DeviceType
	{
		return $this->type;
	}

	public function getDefinition(): DeviceDefinition|null
	{
		return $this->definition;
	}

	public function getDescription(): string|null
	{
		return $this->description;
	}

	public function getManufacturer(): string|null
	{
		return $this->manufacturer;
	}

	public function getModelId(): string|null
	{
		return $this->modelId;
	}

	public function isSupported(): bool
	{
		return $this->supported;
	}

	public function isDisabled(): bool
	{
		return $this->disabled;
	}

	/**
	 * @return array<Scene>
	 */
	public function getScenes(): array
	{
		return $this->scenes;
	}

	public function toArray(): array
	{
		return [
			'friendly_name' => $this->getFriendlyName(),
			'ieee_address' => $this->getIeeeAddress(),
			'network_address' => $this->getNetworkAddress(),
			'interview_completed' => $this->isInterviewCompleted(),
			'interviewing' => $this->isInterviewing(),
			'type' => $this->getType()->getValue(),
			'definition' => $this->getDefinition()?->toArray(),
			'description' => $this->getDescription(),
			'manufacturer' => $this->getManufacturer(),
			'model_id' => $this->getModelId(),
			'supported' => $this->isSupported(),
			'disabled' => $this->isDisabled(),
			'scenes' => array_map(
				static fn (Scene $scene): array => $scene->toArray(),
				$this->getScenes(),
			),
		];
	}

}
