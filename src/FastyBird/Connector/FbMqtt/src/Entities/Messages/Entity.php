<?php declare(strict_types = 1);

/**
 * Entity.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Entities
 * @since          0.1.0
 *
 * @date           24.02.20
 */

namespace FastyBird\Connector\FbMqtt\Entities\Messages;

use Nette;
use Ramsey\Uuid;

/**
 * Base data entity
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class Entity
{

	use Nette\SmartObject;

	private bool $retained = false;

	public function __construct(private readonly Uuid\UuidInterface $connector, private readonly string $device)
	{
	}

	public function getConnector(): Uuid\UuidInterface
	{
		return $this->connector;
	}

	public function getDevice(): string
	{
		return $this->device;
	}

	public function isRetained(): bool
	{
		return $this->retained;
	}

	public function setRetained(bool $retained): void
	{
		$this->retained = $retained;
	}

	/**
	 * @return array<mixed>
	 */
	public function toArray(): array
	{
		return [
			'device' => $this->getDevice(),
			'retained' => $this->isRetained(),
		];
	}

}
