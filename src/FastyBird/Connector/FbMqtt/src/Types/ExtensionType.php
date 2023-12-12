<?php declare(strict_types = 1);

/**
 * ExtensionType.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           05.07.22
 */

namespace FastyBird\Connector\FbMqtt\Types;

use Consistence;
use function strval;

/**
 * Extension types
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ExtensionType extends Consistence\Enum\Enum
{

	/**
	 * Define states
	 */
	public const FASTYBIRD_HARDWARE = 'com.fastybird.hardware';

	public const FASTYBIRD_FIRMWARE = 'com.fastybird.firmware';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
