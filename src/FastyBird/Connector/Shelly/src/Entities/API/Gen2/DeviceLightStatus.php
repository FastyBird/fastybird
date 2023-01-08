<?php declare(strict_types = 1);

/**
 * DeviceLightStatus.php
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
use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Types;
use Nette;
use Nette\Utils;
use function intval;

/**
 * Generation 2 device light status entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceLightStatus implements Entities\API\Entity
{

	use Nette\SmartObject;

	public function __construct(
		private readonly int $id,
		private readonly string $source,
		private readonly bool $output,
		private readonly int $brightness,
		private readonly float|null $timerStartedAt,
		private readonly int|null $timerDuration,
	)
	{
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getType(): Types\ComponentType
	{
		return Types\ComponentType::get(Types\ComponentType::TYPE_LIGHT);
	}

	public function getSource(): string
	{
		return $this->source;
	}

	public function getOutput(): bool
	{
		return $this->output;
	}

	public function getBrightness(): int
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

	public function getTimerDuration(): int|null
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
