<?php declare(strict_types = 1);

/**
 * ConnectorControlName.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Types
 * @since          0.13.0
 *
 * @date           25.08.22
 */

namespace FastyBird\TuyaConnector\Types;

use Consistence;
use FastyBird\Metadata\Types as MetadataTypes;
use function strval;

/**
 * Connector control name types
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ConnectorControlName extends Consistence\Enum\Enum
{

	/**
	 * Define device states
	 */
	public const NAME_DISCOVER = 'discover';

	public const NAME_REBOOT = MetadataTypes\ControlName::NAME_REBOOT;

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
