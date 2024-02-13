<?php declare(strict_types = 1);

/**
 * Connector.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Connectors
 * @since          1.0.0
 *
 * @date           21.01.24
 */

namespace FastyBird\Module\Devices\Connectors;

use Evenement;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\Events;
use FastyBird\Module\Devices\Exceptions;
use React\Promise;
use Symfony\Component\EventDispatcher;
use function array_key_exists;

/**
 * Devices connectors container
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Connectors
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Container implements Connector, EventDispatcher\EventSubscriberInterface
{

	use Evenement\EventEmitterTrait;

	private Connector|null $service = null;

	/**
	 * @param array<string, ConnectorFactory> $factories
	 */
	public function __construct(
		private readonly array $factories,
		private readonly Documents\Connectors\Connector $connector,
	)
	{
	}

	public static function getSubscribedEvents(): array
	{
		return [
			Events\TerminateConnector::class => 'processTermination',
			Events\RestartConnector::class => 'processRestart',
		];
	}

	public function processTermination(Events\TerminateConnector $event): void
	{
		$this->service?->emit(Devices\Constants::EVENT_TERMINATE, [$event]);
	}

	public function processRestart(Events\RestartConnector $event): void
	{
		$this->service?->emit(Devices\Constants::EVENT_RESTART, [$event]);
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function execute(bool $standalone = true): Promise\PromiseInterface
	{
		return $this->getService($this->connector)->execute();
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function discover(): Promise\PromiseInterface
	{
		return $this->getService($this->connector)->discover();
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function terminate(): void
	{
		$this->getService($this->connector)->terminate();
	}

	public function hasUnfinishedTasks(): bool
	{
		return false;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	private function getService(Documents\Connectors\Connector $connector): Connector
	{
		if ($this->service === null) {
			$factory = $this->getServiceFactory($connector);

			$this->service = $factory->create($connector);

			$this->service->on(
				Devices\Constants::EVENT_TERMINATE,
				function (Devices\Events\TerminateConnector $event): void {
					$this->emit(Devices\Constants::EVENT_TERMINATE, [$event]);
				},
			);

			$this->service->on(
				Devices\Constants::EVENT_RESTART,
				function (Devices\Events\RestartConnector $event): void {
					$this->emit(Devices\Constants::EVENT_RESTART, [$event]);
				},
			);
		}

		return $this->service;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	private function getServiceFactory(Documents\Connectors\Connector $connector): ConnectorFactory
	{
		if (array_key_exists($connector::getType(), $this->factories)) {
			return $this->factories[$connector::getType()];
		}

		throw new Exceptions\InvalidState('Connector service factory is not registered');
	}

}
