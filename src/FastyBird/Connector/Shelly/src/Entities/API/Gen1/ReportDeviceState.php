<?php declare(strict_types = 1);

/**
 * ReportDeviceState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           26.08.23
 */

namespace FastyBird\Connector\Shelly\Entities\API\Gen1;

use FastyBird\Connector\Shelly\Entities;
use function array_map;

/**
 * Generation 1 device reported state entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ReportDeviceState implements Entities\API\Entity
{

	/**
	 * @param array<DeviceBlockState> $states
	 */
	public function __construct(
		private readonly string $identifier,
		private readonly string $ipAddress,
		private readonly array $states = [],
	)
	{
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	public function getIpAddress(): string
	{
		return $this->ipAddress;
	}

	/**
	 * @return array<DeviceBlockState>
	 */
	public function getStates(): array
	{
		return $this->states;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'identifier' => $this->getIdentifier(),
			'ip_address' => $this->getIpAddress(),
			'states' => array_map(
				static fn (DeviceBlockState $state): array => $state->toArray(),
				$this->getStates(),
			),
		];
	}

}
