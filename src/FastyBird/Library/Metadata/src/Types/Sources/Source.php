<?php declare(strict_types = 1);

/**
 * Source.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           26.04.21
 */

namespace FastyBird\Library\Metadata\Types\Sources;

use Consistence;
use function strval;

/**
 * Sources types
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class Source extends Consistence\Enum\Enum
{

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
