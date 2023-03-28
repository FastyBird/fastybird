<?php declare(strict_types = 1);

/**
 * Container.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ExchangeLibrary!
 * @subpackage     Consumers
 * @since          1.0.0
 *
 * @date           09.01.22
 */

namespace FastyBird\Library\Exchange\Consumers;

use FastyBird\Library\Exchange\Events;
use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use Psr\EventDispatcher as PsrEventDispatcher;
use SplObjectStorage;

/**
 * Exchange consumer proxy
 *
 * @package        FastyBird:ExchangeLibrary!
 * @subpackage     Consumers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Container implements Consumer
{

	/** @var SplObjectStorage<Consumer, bool> */
	private SplObjectStorage $consumers;

	public function __construct(
		private readonly PsrEventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
		$this->consumers = new SplObjectStorage();
	}

	public function consume(
		MetadataTypes\ModuleSource|MetadataTypes\PluginSource|MetadataTypes\ConnectorSource|MetadataTypes\AutomatorSource $source,
		MetadataTypes\RoutingKey $routingKey,
		MetadataEntities\Entity|null $entity,
	): void
	{
		$this->dispatcher?->dispatch(new Events\BeforeMessageConsumed($source, $routingKey, $entity));

		$this->consumers->rewind();

		while ($this->consumers->valid()) {
			$consumer = $this->consumers->current();
			$status = $this->consumers->getInfo();

			if ($status) {
				$consumer->consume($source, $routingKey, $entity);
			}

			$this->consumers->next();
		}

		$this->dispatcher?->dispatch(new Events\AfterMessageConsumed($source, $routingKey, $entity));
	}

	public function register(Consumer $consumer, bool $status = true): void
	{
		if (!$this->consumers->contains($consumer)) {
			$this->consumers->attach($consumer, $status);
		}
	}

	/**
	 * @phpstan-param class-string<Consumer> $name
	 */
	public function enable(string $name): void
	{
		$this->consumers->rewind();

		while ($this->consumers->valid()) {
			$consumer = $this->consumers->current();
			$status = $this->consumers->getInfo();

			if ($consumer::class === $name) {
				if (!$status) {
					$this->consumers->detach($consumer);
					$this->consumers->attach($consumer, true);
				}

				return;
			}

			$this->consumers->next();
		}
	}

	/**
	 * @phpstan-param class-string<Consumer> $name
	 */
	public function disable(string $name): void
	{
		$this->consumers->rewind();

		while ($this->consumers->valid()) {
			$consumer = $this->consumers->current();
			$status = $this->consumers->getInfo();

			if ($consumer::class === $name) {
				if ($status) {
					$this->consumers->detach($consumer);
					$this->consumers->attach($consumer, false);
				}

				return;
			}

			$this->consumers->next();
		}
	}

	public function reset(): void
	{
		$this->consumers = new SplObjectStorage();
	}

}
