<?php declare(strict_types = 1);

/**
 * HomeKitChannel.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           04.03.23
 */

namespace FastyBird\Connector\HomeKit\Schemas;

use FastyBird\Connector\HomeKit\Entities;
use FastyBird\Connector\HomeKit\Entities\Channels\Channel;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Schemas as DevicesSchemas;

/**
 * HomeKit channel entity schema
 *
 * @template T of Channel
 * @extends  DevicesSchemas\Channels\Channel<T>
 *
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class HomeKitChannel extends DevicesSchemas\Channels\Channel
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\Sources\Connector::HOMEKIT . '/channel/' . Entities\Channels\Channel::TYPE;

	public function getEntityClass(): string
	{
		return Entities\Channels\Channel::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
