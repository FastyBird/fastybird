<?php declare(strict_types = 1);

/**
 * Transformer.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     ValueObjects
 * @since          1.0.0
 *
 * @date           03.02.23
 */

namespace FastyBird\Library\Metadata\ValueObjects;

/**
 * Transformer base value object interface
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     ValueObjects
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface Transformer
{

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array;

}
