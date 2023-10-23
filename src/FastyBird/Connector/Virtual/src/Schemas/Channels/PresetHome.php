<?php declare(strict_types = 1);

/**
 * PresetHome.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           23.10.23
 */

namespace FastyBird\Connector\Virtual\Schemas\Channels;

use FastyBird\Connector\Virtual\Entities;
use FastyBird\Connector\Virtual\Schemas;
use FastyBird\Library\Metadata\Types as MetadataTypes;

/**
 * Preset home channel entity schema
 *
 * @template T of Entities\Channels\PresetHome
 * @extends  Schemas\VirtualChannel<T>
 *
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class PresetHome extends Schemas\VirtualChannel
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIRTUAL . '/channel/' . Entities\Channels\PresetHome::TYPE;

	public function getEntityClass(): string
	{
		return Entities\Channels\PresetHome::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
