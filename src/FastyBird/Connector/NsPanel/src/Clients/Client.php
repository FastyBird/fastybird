<?php declare(strict_types = 1);

/**
 * Client.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           09.07.23
 */

namespace FastyBird\Connector\NsPanel\Clients;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use React\Promise;

/**
 * Base device client interface
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface Client
{

	/**
	 * Create servers/clients
	 */
	public function connect(): void;

	/**
	 * Destroy servers/clients
	 */
	public function disconnect(): void;

	/**
	 * Write thing parameter mapped as channel
	 */
	public function writeChannelProperty(
		Entities\NsPanelDevice $device,
		DevicesEntities\Channels\Channel $channel,
		DevicesEntities\Channels\Properties\Dynamic|DevicesEntities\Channels\Properties\Mapped $property,
	): Promise\PromiseInterface;

}
