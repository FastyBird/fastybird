<?php declare(strict_types = 1);

/**
 * DeviceWifiScan.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           26.04.22
 */

namespace FastyBird\Connector\Tuya\Entities\API;

use Nette;

/**
 * Device wifi scan result message entity
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceWifiScan implements Entity
{

	use Nette\SmartObject;

	/**
	 * @param array<string> $ssids
	 */
	public function __construct(private readonly string $identifier, private readonly array $ssids)
	{
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	/**
	 * @return array<string>
	 */
	public function getSsids(): array
	{
		return $this->ssids;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'identifier' => $this->getIdentifier(),
			'ssids' => $this->getSsids(),
		];
	}

}
