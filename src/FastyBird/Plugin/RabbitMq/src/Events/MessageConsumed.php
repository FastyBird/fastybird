<?php declare(strict_types = 1);

/**
 * MessageConsumed.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RabbitMqPlugin!
 * @subpackage     Events
 * @since          1.0.0
 *
 * @date           15.02.24
 */

namespace FastyBird\Plugin\RabbitMq\Events;

use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use Symfony\Contracts\EventDispatcher;

/**
 * Message was consumed by consumer
 *
 * @package        FastyBird:RabbitMqPlugin!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class MessageConsumed extends EventDispatcher\Event
{

	public function __construct(
		private readonly MetadataTypes\Sources\Source $source,
		private readonly string $routingKey,
		private readonly MetadataDocuments\Document|null $entity,
	)
	{
	}

	public function getSource(): MetadataTypes\Sources\Source
	{
		return $this->source;
	}

	public function getRoutingKey(): string
	{
		return $this->routingKey;
	}

	public function getEntity(): MetadataDocuments\Document|null
	{
		return $this->entity;
	}

}
