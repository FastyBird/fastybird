<?php declare(strict_types = 1);

/**
 * LocalChild.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Properties
 * @since          0.13.0
 *
 * @date           13.12.22
 */

namespace FastyBird\Connector\Tuya\Entities\Clients;

use FastyBird\Connector\Tuya\Types;
use Nette;

/**
 * Local device child entity
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Properties
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class LocalChild implements Entity
{

	use Nette\SmartObject;

	public function __construct(
		private readonly string $identifier,
		private readonly string $nodeId,
		private readonly Types\LocalDeviceType $type,
	)
	{
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	public function getType(): Types\LocalDeviceType
	{
		return $this->type;
	}

	public function getNodeId(): string
	{
		return $this->nodeId;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'identifier' => $this->getIdentifier(),
			'type' => $this->getType()->getValue(),
			'node_id' => $this->getNodeId(),
		];
	}

}
