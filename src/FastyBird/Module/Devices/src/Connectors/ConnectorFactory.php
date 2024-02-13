<?php declare(strict_types = 1);

/**
 * ConnectorFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Connectors
 * @since          1.0.0
 *
 * @date           03.07.22
 */

namespace FastyBird\Module\Devices\Connectors;

use FastyBird\Module\Devices\Documents;

/**
 * Connector factory interface
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Connectors
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface ConnectorFactory
{

	public function create(Documents\Connectors\Connector $connector): Connector;

}
