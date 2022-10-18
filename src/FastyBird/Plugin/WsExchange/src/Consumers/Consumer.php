<?php declare(strict_types = 1);

/**
 * Consumer.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WsExchange!
 * @subpackage     Consumer
 * @since          0.19.0
 *
 * @date           08.07.22
 */

namespace FastyBird\Plugin\WsExchange\Consumers;

use FastyBird\Library\Exchange\Consumer as ExchangeConsumer;
use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Plugin\WsExchange\Publishers;
use Nette;

/**
 * Websockets exchange publisher
 *
 * @package        FastyBird:WsExchange!
 * @subpackage     Consumer
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Consumer implements ExchangeConsumer\Consumer
{

	use Nette\SmartObject;

	public function __construct(private readonly Publishers\Publisher $publisher)
	{
	}

	public function consume(
		MetadataTypes\ModuleSource|MetadataTypes\PluginSource|MetadataTypes\ConnectorSource|MetadataTypes\TriggerSource $source,
		MetadataTypes\RoutingKey $routingKey,
		MetadataEntities\Entity|null $entity,
	): void
	{
		$this->publisher->publish($source, $routingKey, $entity);
	}

}
