<?php declare(strict_types = 1);

/**
 * DevicePropertyIdentifier.php
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
 * Device property identifier types
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class DevicePropertyIdentifier extends Consistence\Enum\Enum
{

	/**
	 * Define device states
	 */
	public const IDENTIFIER_IP_ADDRESS = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_IP_ADDRESS;

	public const IDENTIFIER_STATE = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_STATE;

	public const IDENTIFIER_PROTOCOL_VERSION = 'protocol_version';

	public const IDENTIFIER_LOCAL_KEY = 'local_key';

	public const IDENTIFIER_USER_IDENTIFIER = 'user_identifier';

	public const IDENTIFIER_ENCRYPTED = 'encrypted';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
