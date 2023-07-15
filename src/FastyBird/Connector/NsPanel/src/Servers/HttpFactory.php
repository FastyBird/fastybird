<?php declare(strict_types = 1);

/**
 * HttpFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Servers
 * @since          1.0.0
 *
 * @date           12.07.23
 */

namespace FastyBird\Connector\NsPanel\Servers;

use FastyBird\Connector\NsPanel\Entities;

/**
 * HTTP connector communication server factory
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Servers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface HttpFactory extends ServerFactory
{

	public function create(Entities\NsPanelConnector $connector): Http;

}
