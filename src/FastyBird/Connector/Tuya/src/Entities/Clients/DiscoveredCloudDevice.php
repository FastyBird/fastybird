<?php declare(strict_types = 1);

/**
 * StoreCloudDevice.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           15.08.23
 */

namespace FastyBird\Connector\Tuya\Entities\Clients;

use Orisai\ObjectMapper;

/**
 * Discovered cloud device entity
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DiscoveredCloudDevice implements Entity
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $id,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('ip_address')]
		private readonly string $ipAddress,
	)
	{
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function getIpAddress(): string
	{
		return $this->ipAddress;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'ip_address' => $this->ipAddress,
		];
	}

}
