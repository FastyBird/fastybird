<?php declare(strict_types = 1);

/**
 * NsPanelChannel.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           21.07.23
 */

namespace FastyBird\Connector\NsPanel\Schemas;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Schemas as DevicesSchemas;

/**
 * NS Panel device channel entity schema
 *
 * @extends DevicesSchemas\Channels\Channel<Entities\NsPanelChannel>
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class NsPanelChannel extends DevicesSchemas\Channels\Channel
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL . '/channel/' . Entities\NsPanelChannel::TYPE;

	public function getEntityClass(): string
	{
		return Entities\NsPanelChannel::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
