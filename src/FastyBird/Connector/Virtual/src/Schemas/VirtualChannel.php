<?php declare(strict_types = 1);

/**
 * VirtualChannel.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           20.10.23
 */

namespace FastyBird\Connector\Virtual\Schemas;

use FastyBird\Connector\Virtual\Entities;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Schemas as DevicesSchemas;

/**
 * Virtual channel entity schema
 *
 * @extends DevicesSchemas\Channels\Channel<Entities\VirtualChannel>
 *
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class VirtualChannel extends DevicesSchemas\Channels\Channel
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIRTUAL . '/channel/' . Entities\VirtualChannel::TYPE;

	public function getEntityClass(): string
	{
		return Entities\VirtualChannel::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
