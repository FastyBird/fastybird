<?php declare(strict_types = 1);

/**
 * Container.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Exchange!
 * @subpackage     Publishers
 * @since          0.1.0
 *
 * @date           19.12.20
 */

namespace FastyBird\Exchange\Publisher;

use FastyBird\Exchange\Events;
use FastyBird\Metadata\Entities as MetadataEntities;
use FastyBird\Metadata\Types as MetadataTypes;
use Psr\EventDispatcher as PsrEventDispatcher;
use SplObjectStorage;

/**
 * Exchange publishers proxy
 *
 * @package        FastyBird:Exchange!
 * @subpackage     Publishers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Container implements Publisher
{

	/** @var SplObjectStorage<Publisher, null> */
	private SplObjectStorage $publishers;

	public function __construct(
		private readonly PsrEventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
		$this->publishers = new SplObjectStorage();
	}

	public function publish(
		MetadataTypes\ModuleSource|MetadataTypes\PluginSource|MetadataTypes\ConnectorSource|MetadataTypes\TriggerSource $source,
		MetadataTypes\RoutingKey $routingKey,
		MetadataEntities\Entity|null $entity,
	): void
	{
		$this->dispatcher?->dispatch(new Events\BeforeMessagePublished($routingKey, $entity));

		$this->publishers->rewind();

		foreach ($this->publishers as $publisher) {
			$publisher->publish($source, $routingKey, $entity);
		}

		$this->dispatcher?->dispatch(new Events\AfterMessagePublished($routingKey, $entity));
	}

	public function register(Publisher $publisher): void
	{
		if (!$this->publishers->contains($publisher)) {
			$this->publishers->attach($publisher);
		}
	}

	public function reset(): void
	{
		$this->publishers = new SplObjectStorage();
	}

}
