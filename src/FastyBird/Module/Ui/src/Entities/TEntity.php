<?php declare(strict_types = 1);

/**
 * TEntity.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           25.05.20
 */

namespace FastyBird\Module\Ui\Entities;

use Ramsey\Uuid;

/**
 * Entity base trait
 *
 * @package        FastyBird:UIModule!
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
