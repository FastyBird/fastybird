<?php declare(strict_types = 1);

/**
 * DeviceMeterState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           03.01.23
 */

namespace FastyBird\Connector\Shelly\Entities\API\Gen1;

use DateTimeInterface;
use Exception;
use FastyBird\Connector\Shelly\Entities;
use Nette\Utils;
use Orisai\ObjectMapper;
use function intval;

/**
 * Generation 1 device meter state entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceMeterState implements Entities\API\Entity
{

	/**
	 * @param array<float> $counters
	 */
	public function __construct(
		#[ObjectMapper\Rules\FloatValue()]
		private readonly float $power,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\BoolValue(),
		])]
		private readonly float|bool $overpower,
		#[ObjectMapper\Rules\BoolValue(castBoolLike: true)]
		#[ObjectMapper\Modifiers\FieldName('is_valid')]
		private readonly bool $valid,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly float|null $timestamp,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\FloatValue(),
		)]
		private readonly array $counters,
		#[ObjectMapper\Rules\FloatValue()]
		private readonly float $total,
	)
	{
	}

	public function getPower(): float
	{
		return $this->power;
	}

	public function getOverpower(): float|bool
	{
		return $this->overpower;
	}

	public function isValid(): bool
	{
		return $this->valid;
	}

	/**
	 * @throws Exception
	 */
	public function getTimestamp(): DateTimeInterface|null
	{
		if ($this->timestamp !== null) {
			return Utils\DateTime::from(intval($this->timestamp));
		}

		return null;
	}

	/**
	 * @return array<float>
	 */
	public function getCounters(): array
	{
		return $this->counters;
	}

	public function getTotal(): float
	{
		return $this->total;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exception
	 */
	public function toArray(): array
	{
		return [
			'power' => $this->getPower(),
			'overpower' => $this->getOverpower(),
			'valid' => $this->isValid(),
			'timestamp' => $this->getTimestamp()?->format(DateTimeInterface::ATOM),
			'counters' => $this->getCounters(),
			'total' => $this->getTotal(),
		];
	}

}
