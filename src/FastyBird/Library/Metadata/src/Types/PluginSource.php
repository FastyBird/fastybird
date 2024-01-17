<?php declare(strict_types = 1);

/**
 * PluginSource.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           08.01.22
 */

namespace FastyBird\Library\Metadata\Types;

use Consistence;
use FastyBird\Library\Metadata;
use function strval;

/**
 * Plugins sources types
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class PluginSource extends Consistence\Enum\Enum
{

	/**
	 * Define types
	 */
	public const NOT_SPECIFIED = Metadata\Constants::NOT_SPECIFIED_SOURCE;

	public const COUCHDB = Metadata\Constants::PLUGIN_COUCHDB_SOURCE;

	public const RABBITMQ = Metadata\Constants::PLUGIN_RABBITMQ_SOURCE;

	public const REDISDB = Metadata\Constants::PLUGIN_REDISDB_SOURCE;

	public const WS_SERVER = Metadata\Constants::PLUGIN_WS_SERVER_SOURCE;

	public const WEB_SERVER = Metadata\Constants::PLUGIN_WEB_SERVER_SOURCE;

	public const API_KEY = Metadata\Constants::PLUGIN_API_KEY;

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
