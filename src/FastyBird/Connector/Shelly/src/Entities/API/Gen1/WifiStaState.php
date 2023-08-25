<?php declare(strict_types = 1);

/**
 * WifiStaState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           05.01.23
 */

namespace FastyBird\Connector\Shelly\Entities\API\Gen1;

use FastyBird\Connector\Shelly\Entities;

/**
 * Generation 1 device wifi state entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class WifiStaState implements Entities\API\Entity
{

	public function __construct(
		private readonly bool $connected,
		private readonly string|null $ssid,
		private readonly int|null $rssi,
		private readonly string|null $ip,
	)
	{
	}

	public function isConnected(): bool
	{
		return $this->connected;
	}

	public function getSsid(): string|null
	{
		return $this->ssid;
	}

	public function getRssi(): int|null
	{
		return $this->rssi;
	}

	public function getIp(): string|null
	{
		return $this->ip;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'connected' => $this->isConnected(),
			'ssid' => $this->getSsid(),
			'rssi' => $this->getRssi(),
			'ip' => $this->getIp(),
		];
	}

}
