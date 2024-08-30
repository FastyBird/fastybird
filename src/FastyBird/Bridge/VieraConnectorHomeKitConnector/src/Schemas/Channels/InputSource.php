<?php declare(strict_types = 1);

/**
 * InputSource.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VieraConnectorHomeKitConnectorBridge!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           24.08.24
 */

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\Schemas\Channels;

use FastyBird\Bridge\VieraConnectorHomeKitConnector\Entities;
use FastyBird\Library\Metadata\Types as MetadataTypes;

/**
 * Input button type channel entity schema
 *
 * @template T of Entities\Channels\InputSource
 * @extends  Viera<T>
 *
 * @package        FastyBird:VieraConnectorHomeKitConnectorBridge!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class InputSource extends Viera
{

	/**
	 * Define entity schema type string
	 */
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	public const SCHEMA_TYPE = MetadataTypes\Sources\Bridge::VIERA_CONNECTOR_HOMEKIT_CONNECTOR->value . '/channel/' . Entities\Channels\InputSource::TYPE;

	public function getEntityClass(): string
	{
		return Entities\Channels\InputSource::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
