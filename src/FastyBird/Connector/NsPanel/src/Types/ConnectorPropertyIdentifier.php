<?php declare(strict_types = 1);

/**
 * ConnectorPropertyIdentifier.php
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

use FastyBird\Module\Devices\Types as DevicesTypes;

/**
 * Connector property identifier types
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum ConnectorPropertyIdentifier: string
{

	case PORT = DevicesTypes\ConnectorPropertyIdentifier::PORT->value;

	case CLIENT_MODE = 'mode';

}
