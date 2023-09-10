<?php declare(strict_types = 1);

/**
 * WifiState.php
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

namespace FastyBird\Connector\Shelly\Entities\API\Gen2;

use FastyBird\Connector\Shelly\Entities;
use Orisai\ObjectMapper;

/**
 * Generation 2 device wifi state entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class WifiState implements Entities\API\Entity
{

	public function __construct(
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('sta_ip')]
		private readonly string|null $staIp,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $status,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly string|null $ssid,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly int|null $rssi,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('ap_client_count')]
		private readonly int|null $apClientCount,
	)
	{
	}

	public function getStaIp(): string|null
	{
		return $this->staIp;
	}

	public function getStatus(): string
	{
		return $this->status;
	}

	public function getSsid(): string|null
	{
		return $this->ssid;
	}

	public function getRssi(): int|null
	{
		return $this->rssi;
	}

	public function getApClientCount(): int|null
	{
		return $this->apClientCount;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'sta_ip' => $this->getStaIp(),
			'status' => $this->getStatus(),
			'ssid' => $this->getSsid(),
			'rssi' => $this->getRssi(),
			'ap_client_count' => $this->getApClientCount(),
		];
	}

}
