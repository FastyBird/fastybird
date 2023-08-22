<?php declare(strict_types = 1);

/**
 * ConnectorControlName.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           30.07.22
 */

namespace FastyBird\Connector\Shelly\Types;

use Consistence;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use function strval;

/**
 * Connector control names
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ConnectorControlName extends Consistence\Enum\Enum
{

	public const DISCOVER = MetadataTypes\ControlName::NAME_DISCOVER;

	public const REBOOT = MetadataTypes\ControlName::NAME_REBOOT;

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
