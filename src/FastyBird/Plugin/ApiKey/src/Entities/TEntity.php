<?php declare(strict_types = 1);

/**
 * trait TEntity
.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ApiKeyPlugin!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           21.10.22
 */

namespace FastyBird\Plugin\ApiKey\Entities;

use Ramsey\Uuid;

/**
 * Entity base trait
 *
 * @package        FastyBird:ApiKeyPlugin!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @property Uuid\UuidInterface $id
 */
trait TEntity
{

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

}
