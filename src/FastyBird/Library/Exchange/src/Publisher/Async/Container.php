<?php declare(strict_types = 1);

/**
 * Container.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ExchangeLibrary!
 * @subpackage     Publishers
 * @since          1.0.0
 *
 * @date           19.12.20
 */

namespace FastyBird\Library\Exchange\Publisher\Async;

use FastyBird\Library\Exchange\Events;
use FastyBird\Library\Exchange\Publisher;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use Psr\EventDispatcher as PsrEventDispatcher;
use React\Promise;
use SplObjectStorage;
use Throwable;

/**
 * Exchange async publishers proxy
 *
 * @package        FastyBird:ExchangeLibrary!
 * @subpackage     Publishers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Container implements Publisher\Async\Publisher
{

	/** @var SplObjectStorage<Publisher\Async\Publisher, null> */
	private SplObjectStorage $publishers;

	public function __construct(
		private readonly PsrEventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
		$this->publishers = new SplObjectStorage();
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 */
	public function publish(
		MetadataTypes\Sources\Source $source,
		string $routingKey,
		MetadataDocuments\Document|null $entity,
	): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		$promises = [];

		$this->dispatcher?->dispatch(new Events\BeforeMessagePublished($source, $routingKey, $entity));

		$this->publishers->rewind();

		foreach ($this->publishers as $publisher) {
			$promises[] = $publisher->publish($source, $routingKey, $entity);
		}

		Promise\all($promises)
			->then(function () use ($source, $routingKey, $entity, $deferred): void {
				$this->dispatcher?->dispatch(new Events\AfterMessagePublished($source, $routingKey, $entity));

				$deferred->resolve(true);
			})
			->catch(static function (Throwable $ex) use ($deferred): void {
				$deferred->reject($ex);
			});

		return $deferred->promise();
	}

	public function register(Publisher\Async\Publisher $publisher): void
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
