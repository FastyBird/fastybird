<?php declare(strict_types = 1);

/**
 * UserDeviceDetail.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           26.04.22
 */

namespace FastyBird\Connector\Tuya\Entities\API;

use Nette;
use function array_map;

/**
 * OpenAPI device detail entity
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class UserDeviceDetail implements Entity
{

	use Nette\SmartObject;

	/**
	 * @param array<UserDeviceDataPointStatus> $status
	 */
	public function __construct(
		private readonly string $id,
		private readonly string|null $name,
		private readonly string $uid,
		private readonly string $localKey,
		private readonly string|null $category,
		private readonly string|null $productId,
		private readonly string|null $productName,
		private readonly bool $sub,
		private readonly string $uuid,
		private readonly string|null $ownerId,
		private readonly bool $online,
		private readonly array $status,
		private readonly int|null $activeTime,
		private readonly int|null $createTime,
		private readonly int|null $updateTime,
		private readonly int|null $bizType,
		private readonly string|null $icon,
		private readonly string|null $ip,
		private readonly string|null $timeZone,
	)
	{
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function getName(): string|null
	{
		return $this->name !== '' ? $this->name : null;
	}

	public function getUid(): string
	{
		return $this->uid;
	}

	public function getLocalKey(): string
	{
		return $this->localKey;
	}

	public function getCategory(): string|null
	{
		return $this->category !== '' ? $this->category : null;
	}

	public function getProductId(): string|null
	{
		return $this->productId !== '' ? $this->productId : null;
	}

	public function getProductName(): string|null
	{
		return $this->productName !== '' ? $this->productName : null;
	}

	public function getSub(): bool
	{
		return $this->sub;
	}

	public function getUuid(): string
	{
		return $this->uuid;
	}

	public function getOwnerId(): string|null
	{
		return $this->ownerId !== '' ? $this->ownerId : null;
	}

	public function isOnline(): bool
	{
		return $this->online;
	}

	/**
	 * @return array<UserDeviceDataPointStatus>
	 */
	public function getStatus(): array
	{
		return $this->status;
	}

	public function getActiveTime(): int|null
	{
		return $this->activeTime;
	}

	public function getCreateTime(): int|null
	{
		return $this->createTime;
	}

	public function getUpdateTime(): int|null
	{
		return $this->updateTime;
	}

	public function getBizType(): int|null
	{
		return $this->bizType;
	}

	public function getIcon(): string|null
	{
		return $this->icon !== '' ? $this->icon : null;
	}

	public function getIp(): string|null
	{
		return $this->ip !== '' ? $this->ip : null;
	}

	public function getTimeZone(): string|null
	{
		return $this->timeZone !== '' ? $this->timeZone : null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getId(),
			'name' => $this->getName(),
			'uid' => $this->getUid(),
			'local_key' => $this->getLocalKey(),
			'category' => $this->getCategory(),
			'product_id' => $this->getProductId(),
			'product_name' => $this->getProductName(),
			'sub' => $this->getSub(),
			'uuid' => $this->getUuid(),
			'owner_id' => $this->getOwnerId(),
			'online' => $this->isOnline(),
			'status' => array_map(
				static fn (UserDeviceDataPointStatus $item): array => $item->toArray(),
				$this->getStatus(),
			),
			'active_time' => $this->getActiveTime(),
			'create_time' => $this->getCreateTime(),
			'update_time' => $this->getUpdateTime(),
			'biz_type' => $this->getBizType(),
			'icon' => $this->getIcon(),
			'ip' => $this->getIp(),
			'time_zone' => $this->getTimeZone(),
		];
	}

}
