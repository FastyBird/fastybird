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
use Orisai\ObjectMapper;
use stdClass;

/**
 * NS Panel report its description data response definition
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class GetGatewayInfoData implements Entities\API\Entity
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $ip,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('mac')]
		private readonly string $macAddress,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $domain,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('fw_version')]
		private readonly string $firmwareVersion,
	)
	{
	}

	public function getIpAddress(): string
	{
		return $this->ip;
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
		$json->ip = $this->getIpAddress();
		$json->mac = $this->getMacAddress();
		$json->domain = $this->getDomain();
		$json->fw_version = $this->getFirmwareVersion();

		return $json;
	}

}
