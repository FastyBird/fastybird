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
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('product_key')]
		private readonly string $productKey,
		#[ObjectMapper\Rules\BoolValue()]
		private readonly bool $encrypted,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $version,
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

	public function getProductKey(): string
	{
		return $this->productKey;
	}

	public function isEncrypted(): bool
	{
		return $this->encrypted;
	}

	public function getVersion(): string
	{
		return $this->version;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'ip_address' => $this->ipAddress,
			'product_key' => $this->productKey,
			'encrypted' => $this->encrypted,
			'version' => $this->version,
		];
	}

}
