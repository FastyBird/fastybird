<?php declare(strict_types = 1);

/**
 * State.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     States
 * @since          1.0.0
 *
 * @date           08.03.20
 */

namespace FastyBird\Plugin\RedisDb\States;

use FastyBird\Library\Bootstrap\ObjectMapper as BootstrapObjectMapper;
use Orisai\ObjectMapper;
use Ramsey\Uuid;

/**
 * Base state
 *
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     States
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class State implements ObjectMapper\MappedObject
{

	public const CREATED_AT_FIELD = 'createdAt';

	public const UPDATED_AT_FIELD = 'updatedAt';

	public function __construct(
		#[BootstrapObjectMapper\Rules\UuidValue()]
		private readonly Uuid\UuidInterface $id,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $raw,
	)
	{
	}

	/**
	 * @return array<string>|array<int, int|string|bool|null>
	 */
	public static function getCreateFields(): array
	{
		return [
			'id',
		];
	}

	/**
	 * @return array<string>
	 */
	public static function getUpdateFields(): array
	{
		return [];
	}

	public function getRaw(): string
	{
		return $this->raw;
	}

	/**
	 * @return array<string, mixed|null>
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getId()->toString(),
		];
	}

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

}
