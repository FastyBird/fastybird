<?php declare(strict_types = 1);

/**
 * Container.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Exchange!
 * @subpackage     Consumers
 * @since          0.5.0
 *
 * @date           09.01.22
 */

namespace FastyBird\Library\Exchange\Consumer;

use FastyBird\Library\Exchange\Events;
use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use Psr\EventDispatcher as PsrEventDispatcher;
use SplObjectStorage;

/**
 * Exchange consumer proxy
 *
 * @package        FastyBird:Exchange!
 * @subpackage     Consumers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Container implements Consumer
{

	/** @var SplObjectStorage<Consumer, null> */
	private SplObjectStorage $consumers;

	public function __construct(
		private readonly PsrEventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
		$this->consumers = new SplObjectStorage();
	}

	public function consume(
		MetadataTypes\ModuleSource|MetadataTypes\PluginSource|MetadataTypes\ConnectorSource|MetadataTypes\TriggerSource $source,
		MetadataTypes\RoutingKey $routingKey,
		MetadataEntities\Entity|null $entity,
	): void
	{
		$this->dispatcher?->dispatch(new Events\BeforeMessageConsumed($routingKey, $entity));

		$this->consumers->rewind();

		foreach ($this->consumers as $consumer) {
			$consumer->consume($source, $routingKey, $entity);
		}

		$this->dispatcher?->dispatch(new Events\AfterMessageConsumed($routingKey, $entity));
	}

	public function register(Consumer $consumer): void
	{
		if (!$this->consumers->contains($consumer)) {
			$this->consumers->attach($consumer);
		}
	}

	public function reset(): void
	{
		$this->consumers = new SplObjectStorage();
	}

}
