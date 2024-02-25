<?php declare(strict_types = 1);

/**
 * ServerStatus.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           12.07.23
 */

namespace FastyBird\Connector\NsPanel\Types;

/**
 * Server status types
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum ServerStatus: string
{

	case SUCCESS = '0';

	case ENDPOINT_UNREACHABLE = 'ENDPOINT_UNREACHABLE';

	case ENDPOINT_LOW_POWER = 'ENDPOINT_LOW_POWER';

	case INVALID_DIRECTIVE = 'INVALID_DIRECTIVE';

	case NO_SUCH_ENDPOINT = 'NO_SUCH_ENDPOINT';

	case NOT_SUPPORTED_IN_CURRENT_MODE = 'NOT_SUPPORTED_IN_CURRENT_MODE';

	case INTERNAL_ERROR = 'INTERNAL_ERROR';

}
