<?php declare(strict_types = 1);

/**
 * StoreDeviceState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           18.07.22
 */

namespace FastyBird\Connector\Shelly\Entities\Messages;

use Ramsey\Uuid;
use function array_map;
use function array_merge;

/**
 * Device state message entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StoreDeviceState extends Device
{

	/**
	 * @param array<PropertyState|ChannelState> $states
	 */
	public function __construct(
		Uuid\UuidInterface $connector,
		string $identifier,
		private readonly string|null $ipAddress,
		private readonly array $states,
	)
	{
		parent::__construct($connector, $identifier);
	}

	public function getIpAddress(): string|null
	{
		return $this->ipAddress;
	}

	/**
	 * @return array<PropertyState|ChannelState>
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
		return array_merge(parent::toArray(), [
			'ip_address' => $this->getIpAddress(),
			'states' => array_map(
				static fn (PropertyState|ChannelState $status): array => $status->toArray(),
				$this->getStates(),
			),
		]);
	}

}
