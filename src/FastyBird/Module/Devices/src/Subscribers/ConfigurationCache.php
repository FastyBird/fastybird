<?php declare(strict_types = 1);

/**
 * ConfigurationCache.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Subscribers
 * @since          1.0.0
 *
 * @date           11.09.24
 */

namespace FastyBird\Module\Devices\Subscribers;

use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Events;
use FastyBird\Module\Devices\Types;
use Nette;
use Nette\Caching;
use Symfony\Component\EventDispatcher;

/**
 * Module entities events
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Subscribers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ConfigurationCache implements EventDispatcher\EventSubscriberInterface
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Caching\Cache $configurationBuilderCache,
		private readonly Caching\Cache $configurationRepositoryCache,
	)
	{
	}

	public static function getSubscribedEvents(): array
	{
		return [
			Events\EntityCreated::class => 'entityChanged',
			Events\EntityUpdated::class => 'entityChanged',
			Events\EntityDeleted::class => 'entityChanged',
		];
	}

	public function entityChanged(Events\EntityCreated|Events\EntityUpdated|Events\EntityDeleted $event): void
	{
		$entity = $event->getEntity();

		if ($entity instanceof Entities\Connectors\Connector) {
			$this->configurationBuilderCache->clean([
				Caching\Cache::Tags => [Types\ConfigurationType::CONNECTORS->value],
			]);

			$this->configurationRepositoryCache->clean([
				Caching\Cache::Tags => [
					Types\ConfigurationType::CONNECTORS->value,
					$entity->getId()->toString(),
				],
			]);

		} elseif ($entity instanceof Entities\Connectors\Properties\Property) {
			$this->configurationBuilderCache->clean([
				Caching\Cache::Tags => [Types\ConfigurationType::CONNECTORS_PROPERTIES->value],
			]);

			$this->configurationRepositoryCache->clean([
				Caching\Cache::Tags => [
					Types\ConfigurationType::CONNECTORS_PROPERTIES->value,
					$entity->getId()->toString(),
				],
			]);

		} elseif ($entity instanceof Entities\Connectors\Controls\Control) {
			$this->configurationBuilderCache->clean([
				Caching\Cache::Tags => [Types\ConfigurationType::CONNECTORS_CONTROLS->value],
			]);

			$this->configurationRepositoryCache->clean([
				Caching\Cache::Tags => [
					Types\ConfigurationType::CONNECTORS_CONTROLS->value,
					$entity->getId()->toString(),
				],
			]);

		} elseif ($entity instanceof Entities\Devices\Device) {
			$this->configurationBuilderCache->clean([
				Caching\Cache::Tags => [Types\ConfigurationType::DEVICES->value],
			]);

			$this->configurationRepositoryCache->clean([
				Caching\Cache::Tags => [
					Types\ConfigurationType::DEVICES->value,
					$entity->getId()->toString(),
				],
			]);

		} elseif ($entity instanceof Entities\Devices\Properties\Property) {
			$this->configurationBuilderCache->clean([
				Caching\Cache::Tags => [Types\ConfigurationType::DEVICES_PROPERTIES->value],
			]);

			$this->configurationRepositoryCache->clean([
				Caching\Cache::Tags => [
					Types\ConfigurationType::DEVICES_PROPERTIES->value,
					$entity->getId()->toString(),
				],
			]);

		} elseif ($entity instanceof Entities\Devices\Controls\Control) {
			$this->configurationBuilderCache->clean([
				Caching\Cache::Tags => [Types\ConfigurationType::DEVICES_CONTROLS->value],
			]);

			$this->configurationRepositoryCache->clean([
				Caching\Cache::Tags => [
					Types\ConfigurationType::DEVICES_CONTROLS->value,
					$entity->getId()->toString(),
				],
			]);

		} elseif ($entity instanceof Entities\Channels\Channel) {
			$this->configurationBuilderCache->clean([
				Caching\Cache::Tags => [Types\ConfigurationType::CHANNELS->value],
			]);

			$this->configurationRepositoryCache->clean([
				Caching\Cache::Tags => [
					Types\ConfigurationType::CHANNELS->value,
					$entity->getId()->toString(),
				],
			]);

		} elseif ($entity instanceof Entities\Channels\Properties\Property) {
			$this->configurationBuilderCache->clean([
				Caching\Cache::Tags => [Types\ConfigurationType::CHANNELS_PROPERTIES->value],
			]);

			$this->configurationRepositoryCache->clean([
				Caching\Cache::Tags => [
					Types\ConfigurationType::CHANNELS_PROPERTIES->value,
					$entity->getId()->toString(),
				],
			]);

		} elseif ($entity instanceof Entities\Channels\Controls\Control) {
			$this->configurationBuilderCache->clean([
				Caching\Cache::Tags => [Types\ConfigurationType::CHANNELS_CONTROLS->value],
			]);

			$this->configurationRepositoryCache->clean([
				Caching\Cache::Tags => [
					Types\ConfigurationType::CHANNELS_CONTROLS->value,
					$entity->getId()->toString(),
				],
			]);
		}
	}

}
