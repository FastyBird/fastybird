<?php declare(strict_types = 1);

/**
 * BlockDescription.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           03.01.23
 */

namespace FastyBird\Connector\Shelly\Types;

use Consistence;
use function strval;

/**
 * Block descriptions
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class BlockDescription extends Consistence\Enum\Enum
{

	public const RELAY = 'relay';

	public const ROLLER = 'roller';

	public const LIGHT = 'light';

	public const INPUT = 'input';

	public const METER = 'meter';

	public const EMETER = 'emeter';

	public const DEVICE = 'device';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
