<?php declare(strict_types = 1);

/**
 * Writer.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Writers
 * @since          1.0.0
 *
 * @date           12.07.23
 */

namespace FastyBird\Connector\NsPanel\Writers;

use FastyBird\Connector\NsPanel\Clients;
use FastyBird\Connector\NsPanel\Entities;

/**
 * Properties writer interface
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Writers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface Writer
{

	public function connect(
		Entities\NsPanelConnector $connector,
		Clients\Client $client,
	): void;

	public function disconnect(
		Entities\NsPanelConnector $connector,
		Clients\Client $client,
	): void;

}
