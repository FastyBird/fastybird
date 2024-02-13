<?php declare(strict_types = 1);

/**
 * ContainerFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Connector
 * @since          1.0.0
 *
 * @date           21.01.24
 */

namespace FastyBird\Module\Devices\Connectors;

use FastyBird\Module\Devices\Connectors as DevicesConnectors;
use FastyBird\Module\Devices\Documents;

/**
 * Connector service executor factory
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Connector
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface ContainerFactory extends DevicesConnectors\ConnectorFactory
{

	public function create(Documents\Connectors\Connector $connector): Container;

}
