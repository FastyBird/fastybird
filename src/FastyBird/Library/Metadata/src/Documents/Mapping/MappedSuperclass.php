<?php declare(strict_types = 1);

/**
 * MappedSuperclass.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           10.02.24
 */

namespace FastyBird\Library\Metadata\Documents\Mapping;

use Attribute;

/**
 * Document definition
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class MappedSuperclass implements MappingAttribute
{

}
