<?php declare(strict_types = 1);

/**
 * ConnectorFactory.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Devices!
 * @subpackage     Connectors
 * @since          0.67.0
 *
 * @date           03.07.22
 */

namespace FastyBird\Module\Devices\Connectors;

use FastyBird\Library\Metadata\Entities as MetadataEntities;

/**
 * Connector factory interface
 *
 * @package        FastyBird:Devices!
 * @subpackage     Connectors
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface ConnectorFactory
{

	public function create(MetadataEntities\DevicesModule\Connector $connector): Connector;

}
