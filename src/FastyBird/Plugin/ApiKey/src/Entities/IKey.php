<?php declare(strict_types = 1);

/**
 * IKey.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MiniServer!
 * @subpackage     Entities
 * @since          0.1.0
 *
 * @date           16.05.21
 */

namespace FastyBird\MiniServer\Entities\ApiKeys;

use FastyBird\MiniServer\Entities;
use FastyBird\MiniServer\Types;
use IPub\DoctrineTimestampable;

/**
 * API access key entity interface
 *
 * @package        FastyBird:MiniServer!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface IKey extends Entities\IEntity,
	DoctrineTimestampable\Entities\IEntityCreated, DoctrineTimestampable\Entities\IEntityUpdated
{

	public function setName(string $name): void;

	public function getName(): string;

	public function setKey(string $key): void;

	public function getKey(): string;

	public function setState(Types\KeyStateType $state): void;

	public function getState(): Types\KeyStateType;

}
