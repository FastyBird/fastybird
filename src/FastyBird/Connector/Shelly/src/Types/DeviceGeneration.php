<?php declare(strict_types = 1);

/**
 * DeviceGeneration.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           20.12.22
 */

namespace FastyBird\Connector\Shelly\Types;

/**
 * Device generations
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum DeviceGeneration: string
{

	case UNKNOWN = 'unknown';

	case GENERATION_1 = 'gen1';

	case GENERATION_2 = 'gen2';

	case GENERATION_3 = 'gen3';

}
