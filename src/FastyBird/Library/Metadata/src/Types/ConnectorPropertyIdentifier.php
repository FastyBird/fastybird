<?php declare(strict_types = 1);

/**
 * ConnectorPropertyIdentifier.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           08.02.22
 */

namespace FastyBird\Library\Metadata\Types;

use Consistence;
use function strval;

/**
 * Connector property identifier types
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ConnectorPropertyIdentifier extends Consistence\Enum\Enum
{

	/**
	 * Define connector properties identifiers
	 */
	public const STATE = PropertyIdentifier::STATE;

	public const SERVER = PropertyIdentifier::SERVER;

	public const PORT = PropertyIdentifier::PORT;

	public const SECURED_PORT = PropertyIdentifier::SECURED_PORT;

	public const BAUD_RATE = PropertyIdentifier::BAUD_RATE;

	public const INTERFACE = PropertyIdentifier::INTERFACE;

	public const ADDRESS = PropertyIdentifier::ADDRESS;

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
