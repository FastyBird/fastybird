<?php declare(strict_types = 1);

/**
 * DiscoveredLocalDevice.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           15.05.23
 */

namespace FastyBird\Connector\Sonoff\Clients\Messages\Response;

use FastyBird\Connector\Sonoff\Clients;
use Orisai\ObjectMapper;

/**
 * Discovered local device entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class DiscoveredLocalDevice implements Clients\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $ipAddress,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string|null $domain,
		#[ObjectMapper\Rules\IntValue(unsigned: true)]
		private int $port,
	)
	{
	}

	public function getIpAddress(): string
	{
		return $this->ipAddress;
	}

	public function getDomain(): string|null
	{
		return $this->domain;
	}

	public function getPort(): int
	{
		return $this->port;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'ip_address' => $this->getIpAddress(),
			'domain' => $this->getDomain(),
			'port' => $this->getPort(),
		];
	}

}
