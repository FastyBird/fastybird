<?php declare(strict_types = 1);

/**
 * MessageReceived.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Events
 * @since          1.0.0
 *
 * @date           22.10.22
 */

namespace FastyBird\Plugin\RedisDb\Events;

use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use Symfony\Contracts\EventDispatcher;

/**
 * Message was received with all required parameters
 *
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class MessageReceived extends EventDispatcher\Event
{

	public function __construct(
		private readonly MetadataTypes\ModuleSource|MetadataTypes\PluginSource|MetadataTypes\ConnectorSource|MetadataTypes\AutomatorSource $source,
		private readonly MetadataTypes\RoutingKey $routingKey,
		private readonly MetadataDocuments\Document|null $entity,
	)
	{
	}

	public function getSource(): MetadataTypes\AutomatorSource|MetadataTypes\ModuleSource|MetadataTypes\PluginSource|MetadataTypes\ConnectorSource
	{
		return $this->source;
	}

	public function getRoutingKey(): MetadataTypes\RoutingKey
	{
		return $this->routingKey;
	}

	public function getEntity(): MetadataDocuments\Document|null
	{
		return $this->entity;
	}

}
