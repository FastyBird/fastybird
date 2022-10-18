<?php declare(strict_types = 1);

/**
 * ChannelPropertyIdentifier.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Metadata!
 * @subpackage     Types
 * @since          0.75.0
 *
 * @date           23.08.22
 */

namespace FastyBird\Library\Metadata\Types;

use Consistence;
use function strval;

/**
 * Channel property identifier types
 *
 * @package        FastyBird:Metadata!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ChannelPropertyIdentifier extends Consistence\Enum\Enum
{

	/**
	 * Define device properties identifiers
	 */
	public const IDENTIFIER_ADDRESS = PropertyIdentifier::IDENTIFIER_ADDRESS;

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
