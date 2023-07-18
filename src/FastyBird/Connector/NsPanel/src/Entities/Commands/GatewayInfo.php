<?php declare(strict_types = 1);

/**
 * GatewayInfo.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           10.07.23
 */

namespace FastyBird\Connector\NsPanel\Entities\Commands;

use FastyBird\Connector\NsPanel\Entities;
use Nette;
use stdClass;

/**
 * NS Panel info definition
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class GatewayInfo implements Entities\API\Entity
{

	use Nette\SmartObject;

	public function __construct(
		private readonly string $ipAddress,
		private readonly string $macAddress,
		private readonly string $domain,
		private readonly string $firmwareVersion,
	)
	{
	}

	public function getIpAddress(): string
	{
		return $this->ipAddress;
	}

	public function getMacAddress(): string
	{
		return $this->macAddress;
	}

	public function getDomain(): string
	{
		return $this->domain;
	}

	public function getFirmwareVersion(): string
	{
		return $this->firmwareVersion;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'ip_address' => $this->getIpAddress(),
			'mac_address' => $this->getMacAddress(),
			'domain' => $this->getDomain(),
			'firmware_version' => $this->getFirmwareVersion(),
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->ip_address = $this->getIpAddress();
		$json->mac_address = $this->getMacAddress();
		$json->domain = $this->getDomain();
		$json->firmware_version = $this->getFirmwareVersion();

		return $json;
	}

}
