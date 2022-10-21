<?php declare(strict_types = 1);

/**
 * Entity.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ApiKeyPlugin!
 * @subpackage     Entities
 * @since          0.1.0
 *
 * @date           21.10.22
 */

namespace FastyBird\Plugin\ApiKey\Entities;

use Ramsey\Uuid;

/**
 * Application base entity
 *
 * @package        FastyBird:ApiKeyPlugin!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @property-read Uuid\UuidInterface $id
 */
abstract class Entity
{

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	public function getPlainId(): string
	{
		return $this->id->toString();
	}

}
