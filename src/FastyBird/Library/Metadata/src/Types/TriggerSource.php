<?php declare(strict_types = 1);

/**
 * ConnectorSource.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Metadata!
 * @subpackage     Types
 * @since          0.30.0
 *
 * @date           19.01.22
 */

namespace FastyBird\Metadata\Types;

use Consistence;
use FastyBird\Metadata;
use function strval;

/**
 * Triggers sources types
 *
 * @package        FastyBird:Metadata!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class TriggerSource extends Consistence\Enum\Enum
{

	/**
	 * Define types
	 */
	public const SOURCE_NOT_SPECIFIED = Metadata\Constants::NOT_SPECIFIED_SOURCE;

	public const SOURCE_TRIGGER_DEVICE_MODULE = Metadata\Constants::TRIGGER_DEVICE_MODULE;

	public const SOURCE_TRIGGER_DATE_TIME = Metadata\Constants::TRIGGER_DATE_TIME;

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
