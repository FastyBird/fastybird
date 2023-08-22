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

use Orisai\ObjectMapper;

/**
 * OpenAPI user device child entity
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class UserDeviceChild implements Entity
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $id,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('node_id')]
		private readonly string $nodeId,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly string|null $name,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly string|null $icon,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('product_id')]
		private readonly string|null $productId,
		#[ObjectMapper\Rules\BoolValue()]
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
