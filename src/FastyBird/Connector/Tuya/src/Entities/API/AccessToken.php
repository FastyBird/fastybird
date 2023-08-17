<?php declare(strict_types = 1);

/**
 * AccessToken.php
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
use Orisai\ObjectMapper;
use function intval;

/**
 * OpenAPI access token entity
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class AccessToken implements Entity
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $uid,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('access_token')]
		private readonly string $accessToken,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('refresh_token')]
		private readonly string $refreshToken,
		#[ObjectMapper\Rules\IntValue(unsigned: true)]
		#[ObjectMapper\Modifiers\FieldName('expire_time')]
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
