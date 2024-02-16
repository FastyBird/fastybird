<?php declare(strict_types = 1);

/**
 * CompositeExposeData.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           25.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Queue\Messages;

use FastyBird\Connector\Zigbee2Mqtt\Types;
use Orisai\ObjectMapper;
use function array_map;

/**
 * Expose data row
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class CompositeExposeData implements Message
{

	/**
	 * @param array<SingleExposeData|CompositeExposeData> $states
	 */
	public function __construct(
		#[ObjectMapper\Rules\ArrayEnumValue(cases: [Types\ExposeDataType::COMPOSITE])]
		private string $type,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $identifier,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\AnyOf([
				new ObjectMapper\Rules\MappedObjectValue(class: SingleExposeData::class),
				new ObjectMapper\Rules\MappedObjectValue(class: self::class),
			]),
			new ObjectMapper\Rules\IntValue(unsigned: true),
		)]
		private array $states,
	)
	{
	}

	public function getType(): Types\ExposeDataType
	{
		return Types\ExposeDataType::get($this->type);
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	/**
	 * @return array<SingleExposeData|CompositeExposeData>
	 */
	public function getStates(): array
	{
		return $this->states;
	}

	public function toArray(): array
	{
		return [
			'type' => $this->getType()->getValue(),
			'identifier' => $this->getIdentifier(),
			'states' => array_map(
				static fn (SingleExposeData|self $item): array => $item->toArray(),
				$this->getStates(),
			),
		];
	}

}
