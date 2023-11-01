<?php declare(strict_types = 1);

/**
 * DeviceLightState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           26.12.22
 */

namespace FastyBird\Connector\Shelly\Entities\API\Gen2;

use DateTimeInterface;
use Exception;
use FastyBird\Connector\Shelly;
use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Types;
use Nette\Utils;
use Orisai\ObjectMapper;
use function intval;

/**
 * Generation 2 device light state entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceLightState implements Entities\API\Entity
{

	public function __construct(
		#[ObjectMapper\Rules\IntValue(unsigned: true)]
		private readonly int $id,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly string|null $source,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly bool|null $output,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\ArrayEnumValue(cases: [Shelly\Constants::VALUE_NOT_AVAILABLE]),
		])]
		private readonly int|string $brightness,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('timer_started_at')]
		private readonly float|null $timerStartedAt,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('timer_duration')]
		private readonly float|null $timerDuration,
	)
	{
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getType(): Types\ComponentType
	{
		return Types\ComponentType::get(Types\ComponentType::LIGHT);
	}

	public function getSource(): string|null
	{
		return $this->source;
	}

	public function getOutput(): bool|null
	{
		return $this->output;
	}

	public function getBrightness(): int|string
	{
		return $this->brightness;
	}

	/**
	 * @throws Exception
	 */
	public function getTimerStartedAt(): DateTimeInterface|null
	{
		if ($this->timerStartedAt !== null) {
			return Utils\DateTime::from(intval($this->timerStartedAt));
		}

		return null;
	}

	public function getTimerDuration(): float|null
	{
		return $this->timerDuration;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exception
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getId(),
			'type' => $this->getType()->getValue(),
			'source' => $this->getSource(),
			'output' => $this->getOutput(),
			'brightness' => $this->getBrightness(),
			'timer_started_at' => $this->getTimerStartedAt()?->format(DateTimeInterface::ATOM),
			'timer_duration' => $this->getTimerDuration(),
		];
	}

}
