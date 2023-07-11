<?php declare(strict_types = 1);

/**
 * GetGatewayInfoData.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           09.07.23
 */

namespace FastyBird\Connector\NsPanel\Entities\API\Response;

use FastyBird\Connector\NsPanel\Entities;
use Nette;
use stdClass;

/**
 * NS Panel report its status data response definition
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class GetGatewayInfoData implements Entities\API\Entity
{

	use Nette\SmartObject;

	public function __construct(
		private readonly string $ip,
		private readonly string $mac,
		private readonly string $domain,
		private readonly string $fwVersion,
	)
	{
	}

	public function getIpAddress(): string
	{
		return $this->ip;
	}

	public function getMacAddress(): string
	{
		return $this->mac;
	}

	public function getDomain(): string
	{
		return $this->domain;
	}

	public function getFwVersion(): string
	{
		return $this->fwVersion;
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
			'firmware_version' => $this->getFwVersion(),
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->ip = $this->getIpAddress();
		$json->mac = $this->getMacAddress();
		$json->domain = $this->getDomain();
		$json->fw_version = $this->getFwVersion();

		return $json;
	}

}
