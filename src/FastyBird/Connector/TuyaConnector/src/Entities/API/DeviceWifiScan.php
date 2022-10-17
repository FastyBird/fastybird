<?php declare(strict_types = 1);

/**
 * DeviceWifiScan.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 * @since          0.13.0
 *
 * @date           26.04.22
 */

namespace FastyBird\TuyaConnector\Entities\API;

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
	 * @param Array<string> $ssids
	 */
	public function __construct(private readonly string $identifier, private readonly array $ssids)
	{
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	/**
	 * @return Array<string>
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
