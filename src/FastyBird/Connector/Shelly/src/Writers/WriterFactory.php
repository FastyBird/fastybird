<?php declare(strict_types = 1);

/**
 * WriterFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Writers
 * @since          1.0.0
 *
 * @date           30.08.23
 */

namespace FastyBird\Connector\Shelly\Writers;

use FastyBird\Connector\Shelly\Documents;

/**
 * Device state writer interface factory
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Writers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface WriterFactory
{

	public function create(Documents\Connectors\Connector $connector): Writer;

}
