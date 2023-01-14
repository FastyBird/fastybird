<?php declare(strict_types = 1);

/**
 * ComponentEvent.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           14.01.23
 */

namespace FastyBird\Connector\Shelly\Entities\API\Gen2;

use DateTimeInterface;
use Exception;
use FastyBird\Connector\Shelly\Entities;
use Nette;
use Nette\Utils;
use function intval;

/**
 * Generation 2 device component event entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ComponentEvent implements Entities\API\Entity
{

	use Nette\SmartObject;

	public function __construct(
		private readonly string $component,
		private readonly int $id,
		private readonly string $event,
		private readonly float $ts,
	)
	{
	}

	public function getComponent(): string
	{
		return $this->component;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getEvent(): string
	{
		return $this->event;
	}

	/**
	 * @throws Exception
	 */
	public function getTimestamp(): DateTimeInterface
	{
		return Utils\DateTime::from(intval($this->ts));
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exception
	 */
	public function toArray(): array
	{
		return [
			'component' => $this->getComponent(),
			'id' => $this->getId(),
			'event' => $this->getEvent(),
			'timestamp' => $this->getTimestamp()->format(DateTimeInterface::ATOM),
		];
	}

}
