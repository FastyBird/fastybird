<?php declare(strict_types = 1);

/**
 * IlluminationLevel.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           09.07.23
 */

namespace FastyBird\Connector\NsPanel\Types\Payloads;

/**
 * Illumination level payload types
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum IlluminationLevel: string implements Payload
{

	case BRIGHTER = 'brighter';

	case DARKER = 'darker';

}
