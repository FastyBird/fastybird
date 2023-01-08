<?php declare(strict_types = 1);

/**
 * DiscoveredLocalDevice.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           20.12.22
 */

namespace FastyBird\Connector\Shelly\Entities\Clients;

use FastyBird\Connector\Shelly\Types;
use Nette;

/**
 * Discovered local device entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DiscoveredLocalDevice implements Entity
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Types\DeviceGeneration $generation,
		private readonly string $id,
		private readonly string $type,
		private readonly string $ipAddress,
		private readonly string|null $domain,
	)
	{
	}

	public function getGeneration(): Types\DeviceGeneration
	{
		return $this->generation;
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function getIdentifier(): string
	{
		return $this->getId() . '-' . $this->getType();
	}

	public function getIpAddress(): string
	{
		return $this->ipAddress;
	}

	public function getDomain(): string|null
	{
		return $this->domain;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'generation' => $this->getGeneration()->getValue(),
			'id' => $this->getId(),
			'type' => $this->getType(),
			'ip_address' => $this->getIpAddress(),
			'domain' => $this->getDomain(),
		];
	}

}
