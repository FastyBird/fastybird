<?php declare(strict_types = 1);

/**
 * Generic.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           30.01.24
 */

namespace FastyBird\Connector\HomeKit\Schemas\Channels;

use FastyBird\Connector\HomeKit\Entities;
use FastyBird\Library\Metadata\Types as MetadataTypes;

/**
 * Generic channel entity schema
 *
 * @template T of Entities\Channels\Generic
 * @extends  Channel<T>
 *
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Generic extends Channel
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\Sources\Connector::HOMEKIT . '/channel/' . Entities\Channels\Generic::TYPE;

	public function getEntityClass(): string
	{
		return Entities\Channels\Generic::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
