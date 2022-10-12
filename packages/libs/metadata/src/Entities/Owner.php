<?php declare(strict_types = 1);

/**
 * Owner.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Metadata!
 * @subpackage     Entities
 * @since          0.58.0
 *
 * @date           05.06.22
 */

namespace FastyBird\Metadata\Entities;

use Ramsey\Uuid;

/**
 * Data entity owner interface
 *
 * @package        FastyBird:Metadata!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface Owner
{

	public function getOwner(): Uuid\UuidInterface|null;

}
