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

use Orisai\ObjectMapper;

/**
 * NS Panel info definition
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class GatewayInfo implements Entity
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('ip_address')]
		private readonly string $ipAddress,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('mac_address')]
		private readonly string $macAddress,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $domain,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('firmware_version')]
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

}
