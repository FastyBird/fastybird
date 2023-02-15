<?php declare(strict_types = 1);

/**
 * WsSession.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Properties
 * @since          1.0.0
 *
 * @date           11.01.23
 */

namespace FastyBird\Connector\Shelly\Entities\API\Gen2;

use FastyBird\Connector\Shelly\Entities\Clients\Entity;
use Nette;

/**
 * Websocket session entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Properties
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class WsSession implements Entity
{

	use Nette\SmartObject;

	public function __construct(
		private readonly string $realm,
		private readonly string $username,
		private readonly int $nonce,
		private readonly int $cnonce,
		private readonly string $response,
		private readonly int $nc,
		private readonly string $algorithm,
	)
	{
	}

	public function getRealm(): string
	{
		return $this->realm;
	}

	public function getUsername(): string
	{
		return $this->username;
	}

	public function getNonce(): int
	{
		return $this->nonce;
	}

	public function getCnonce(): int
	{
		return $this->cnonce;
	}

	public function getResponse(): string
	{
		return $this->response;
	}

	public function getNc(): int
	{
		return $this->nc;
	}

	public function getAlgorithm(): string
	{
		return $this->algorithm;
	}

	/**
	 * @return array<string, string|int>
	 */
	public function toArray(): array
	{
		return [
			'realm' => $this->getRealm(),
			'username' => $this->getUsername(),
			'nonce' => $this->getNonce(),
			'cnonce' => $this->getCnonce(),
			'response' => $this->getResponse(),
			'nc' => $this->getNc(),
			'algorithm' => $this->getAlgorithm(),
		];
	}

}
