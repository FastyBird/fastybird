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
use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use React\Promise;

/**
 * Connector client interface
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface Client
{

	/**
	 * Create clients
	 */
	public function connect(): void;

	/**
	 * Destroy clients
	 */
	public function disconnect(): void;

	/**
	 * Write data to device
	 */
	public function writeChannelProperty(
		Entities\NsPanelDevice $device,
		Entities\NsPanelChannel $channel,
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		DevicesEntities\Channels\Properties\Dynamic|DevicesEntities\Channels\Properties\Mapped|MetadataEntities\DevicesModule\ChannelDynamicProperty|MetadataEntities\DevicesModule\ChannelMappedProperty $property,
	): Promise\PromiseInterface;

}
