<?php declare(strict_types = 1);

/**
 * UserDeviceChildren.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           12.12.22
 */

namespace FastyBird\Connector\Tuya\Entities\API;

use Nette;

/**
 * OpenAPI device children entity
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class UserDeviceChild implements Entity
{

	use Nette\SmartObject;

	public function __construct(
		private readonly string $id,
		private readonly string $nodeId,
		private readonly string|null $name,
		private readonly string|null $icon,
		private readonly string|null $productId,
		private readonly bool $online = false,
	)
	{
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function getNodeId(): string
	{
		return $this->nodeId;
	}

	public function getName(): string|null
	{
		return $this->name !== '' ? $this->name : null;
	}

	public function getProductId(): string|null
	{
		return $this->productId !== '' ? $this->productId : null;
	}

	public function getIcon(): string|null
	{
		return $this->icon !== '' ? $this->icon : null;
	}

	public function isOnline(): bool
	{
		return $this->online;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getId(),
			'node_id' => $this->getNodeId(),
			'name' => $this->getName(),
			'product_id' => $this->getProductId(),
			'icon' => $this->getIcon(),
			'online' => $this->isOnline(),
		];
	}

}
