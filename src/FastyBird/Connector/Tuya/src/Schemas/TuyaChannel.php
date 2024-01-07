<?php declare(strict_types = 1);

/**
 * TuyaChannel.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           07.01.24
 */

namespace FastyBird\Connector\Tuya\Schemas;

use FastyBird\Connector\Tuya\Entities;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Schemas as DevicesSchemas;

/**
 * Tuya device channel entity schema
 *
 * @extends DevicesSchemas\Channels\Channel<Entities\TuyaChannel>
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class TuyaChannel extends DevicesSchemas\Channels\Channel
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA . '/channel/' . Entities\TuyaChannel::TYPE;

	public function getEntityClass(): string
	{
		return Entities\TuyaChannel::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
