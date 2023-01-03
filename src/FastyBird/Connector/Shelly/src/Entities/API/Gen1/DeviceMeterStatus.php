<?php declare(strict_types = 1);

/**
 * DeviceMeterStatus.php
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
use Nette;
use Nette\Utils;
use function intval;

/**
 * Generation 1 device meter status entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceMeterStatus implements Entities\API\Entity
{

	use Nette\SmartObject;

	/**
	 * @param array<float> $counters
	 */
	public function __construct(
		private readonly float $power,
		private readonly float $overpower,
		private readonly bool $isValid,
		private readonly float $timestamp,
		private readonly array $counters,
		private readonly float $total,
	)
	{
	}

	public function getPower(): float
	{
		return $this->power;
	}

	public function getOverpower(): float
	{
		return $this->overpower;
	}

	public function isValid(): bool
	{
		return $this->isValid;
	}

	/**
	 * @throws Exception
	 */
	public function getTimestamp(): DateTimeInterface
	{
		return Utils\DateTime::from(intval($this->timestamp));
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
			'timestamp' => $this->getTimestamp()->format(DateTimeInterface::ATOM),
			'counters' => $this->getCounters(),
			'total' => $this->getTotal(),
		];
	}

}
