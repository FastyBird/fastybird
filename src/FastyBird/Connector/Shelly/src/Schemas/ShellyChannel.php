<?php declare(strict_types = 1);

/**
 * ShellyChannel.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           07.01.24
 */

namespace FastyBird\Connector\Shelly\Schemas;

use FastyBird\Connector\Shelly\Entities;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Schemas as DevicesSchemas;

/**
 * Shelly device channel entity schema
 *
 * @extends DevicesSchemas\Channels\Channel<Entities\ShellyChannel>
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ShellyChannel extends DevicesSchemas\Channels\Channel
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\ConnectorSource::SHELLY . '/channel/' . Entities\ShellyChannel::TYPE;

	public function getEntityClass(): string
	{
		return Entities\ShellyChannel::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
