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
use FastyBird\Library\Exchange\Exceptions;
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

	/** @var SplObjectStorage<Consumer, Info> */
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
			$info = $this->consumers->getInfo();

			if (
				$info->isEnabled()
				&& (
					$info->getRoutingKey() === null
					|| $info->getRoutingKey()->equals($routingKey)
				)
			) {
				$consumer->consume($source, $routingKey, $entity);
			}

			$this->consumers->next();
		}

		$this->dispatcher?->dispatch(new Events\AfterMessageConsumed($source, $routingKey, $entity));
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function register(Consumer $consumer, string|null $routingKey, bool $status = true): void
	{
		if ($routingKey !== null && !MetadataTypes\RoutingKey::isValidValue($routingKey)) {
			throw new Exceptions\InvalidArgument('Provided routing key is not valid');
		}

		if (!$this->consumers->contains($consumer)) {
			$this->consumers->attach($consumer, new Info(MetadataTypes\RoutingKey::get($routingKey), $status));
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
			$info = $this->consumers->getInfo();

			if ($consumer::class === $name) {
				if (!$info->isEnabled()) {
					$this->consumers->detach($consumer);
					$this->consumers->attach($consumer, new Info($info->getRoutingKey(), true));
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
			$info = $this->consumers->getInfo();

			if ($consumer::class === $name) {
				if ($info->isEnabled()) {
					$this->consumers->detach($consumer);
					$this->consumers->attach($consumer, new Info($info->getRoutingKey(), false));
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
