<?php declare(strict_types = 1);

/**
 * FbMqttChannel.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           07.01.24
 */

namespace FastyBird\Connector\FbMqtt\Schemas;

use FastyBird\Connector\FbMqtt\Entities;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Schemas as DevicesSchemas;

/**
 * FastyBird MQTT device channel entity schema
 *
 * @extends DevicesSchemas\Channels\Channel<Entities\FbMqttChannel>
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class FbMqttChannel extends DevicesSchemas\Channels\Channel
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\Sources\Connector::FB_MQTT . '/channel/' . Entities\FbMqttChannel::TYPE;

	public function getEntityClass(): string
	{
		return Entities\FbMqttChannel::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
