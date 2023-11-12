<?php declare(strict_types = 1);

/**
 * TOwner.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           05.06.22
 */

namespace FastyBird\Library\Metadata\Entities;

use Ramsey\Uuid;

/**
 * Transformer owner trait
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @property-read Uuid\UuidInterface|null $owner
 */
trait TOwner
{

	public function getOwner(): Uuid\UuidInterface|null
	{
		return $this->owner;
	}

}
