<?php declare(strict_types = 1);

/**
 * TuyaTokenInfo.php
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

use DateTimeInterface;
use Nette;
use function intval;

/**
 * OpenAPI token entity
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class TuyaTokenInfo implements Entity
{

	use Nette\SmartObject;

	public function __construct(
		private readonly string $uid,
		private readonly string $accessToken,
		private readonly string $refreshToken,
		private readonly int $expireTime,
	)
	{
	}

	public function getUid(): string
	{
		return $this->uid;
	}

	public function getAccessToken(): string
	{
		return $this->accessToken;
	}

	public function getRefreshToken(): string
	{
		return $this->refreshToken;
	}

	public function getExpireTime(): int
	{
		return $this->expireTime;
	}

	public function isExpired(DateTimeInterface $now): bool
	{
		return !(($this->getExpireTime() - 60 * 1_000) > intval($now->format('Uv')));
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'uid' => $this->getUid(),
			'access_token' => $this->getAccessToken(),
			'refresh_token' => $this->getRefreshToken(),
			'expire_time' => $this->getExpireTime(),
		];
	}

}
