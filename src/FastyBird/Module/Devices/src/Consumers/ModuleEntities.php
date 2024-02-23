<?php declare(strict_types = 1);

/**
 * ModuleEntities.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Consumers
 * @since          1.0.0
 *
 * @date           22.10.22
 */

namespace FastyBird\Module\Devices\Consumers;

use FastyBird\Library\Exchange\Consumers as ExchangeConsumers;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Types;
use Nette\Caching;
use function in_array;

/**
 * Module entities subscriber
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Consumers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ModuleEntities implements ExchangeConsumers\Consumer
{

	public function __construct(
		private readonly Caching\Cache $configurationBuilderCache,
		private readonly Caching\Cache $configurationRepositoryCache,
	)
	{
	}

	public function consume(
		MetadataTypes\Sources\Source $source,
		string $routingKey,
		MetadataDocuments\Document|null $document,
	): void
	{
		if ($document === null) {
			return;
		}

		if (
			$document instanceof Devices\Documents\Connectors\Connector
			&& in_array(
				$routingKey,
				[
					Devices\Constants::MESSAGE_BUS_CONNECTOR_DOCUMENT_CREATED_ROUTING_KEY,
					Devices\Constants::MESSAGE_BUS_CONNECTOR_DOCUMENT_UPDATED_ROUTING_KEY,
					Devices\Constants::MESSAGE_BUS_CONNECTOR_DOCUMENT_DELETED_ROUTING_KEY,
				],
				true,
			)
		) {
			$this->configurationBuilderCache->clean([
				Caching\Cache::Tags => [Types\ConfigurationType::CONNECTORS->value],
			]);

			$this->configurationRepositoryCache->clean([
				Caching\Cache::Tags => [$document->getId()->toString()],
			]);
		} elseif (
			$document instanceof Devices\Documents\Connectors\Properties\Property
			&& in_array(
				$routingKey,
				[
					Devices\Constants::MESSAGE_BUS_CONNECTOR_PROPERTY_DOCUMENT_CREATED_ROUTING_KEY,
					Devices\Constants::MESSAGE_BUS_CONNECTOR_PROPERTY_DOCUMENT_UPDATED_ROUTING_KEY,
					Devices\Constants::MESSAGE_BUS_CONNECTOR_PROPERTY_DOCUMENT_DELETED_ROUTING_KEY,
				],
				true,
			)
		) {
			$this->configurationBuilderCache->clean([
				Caching\Cache::Tags => [Types\ConfigurationType::CONNECTORS_PROPERTIES->value],
			]);

			$this->configurationRepositoryCache->clean([
				Caching\Cache::Tags => [$document->getId()->toString()],
			]);
		} elseif (
			$document instanceof Devices\Documents\Connectors\Controls\Control
			&& in_array(
				$routingKey,
				[
					Devices\Constants::MESSAGE_BUS_CONNECTOR_CONTROL_DOCUMENT_CREATED_ROUTING_KEY,
					Devices\Constants::MESSAGE_BUS_CONNECTOR_CONTROL_DOCUMENT_UPDATED_ROUTING_KEY,
					Devices\Constants::MESSAGE_BUS_CONNECTOR_CONTROL_DOCUMENT_DELETED_ROUTING_KEY,
				],
				true,
			)
		) {
			$this->configurationBuilderCache->clean([
				Caching\Cache::Tags => [Types\ConfigurationType::CONNECTORS_PROPERTIES->value],
			]);

			$this->configurationRepositoryCache->clean([
				Caching\Cache::Tags => [$document->getId()->toString()],
			]);
		} elseif (
			$document instanceof Devices\Documents\Devices\Device
			&& in_array(
				$routingKey,
				[
					Devices\Constants::MESSAGE_BUS_DEVICE_DOCUMENT_CREATED_ROUTING_KEY,
					Devices\Constants::MESSAGE_BUS_DEVICE_DOCUMENT_UPDATED_ROUTING_KEY,
					Devices\Constants::MESSAGE_BUS_DEVICE_DOCUMENT_DELETED_ROUTING_KEY,
				],
				true,
			)
		) {
			$this->configurationBuilderCache->clean([
				Caching\Cache::Tags => [Types\ConfigurationType::DEVICES->value],
			]);

			$this->configurationRepositoryCache->clean([
				Caching\Cache::Tags => [$document->getId()->toString()],
			]);
		} elseif (
			$document instanceof Devices\Documents\Devices\Properties\Property
			&& in_array(
				$routingKey,
				[
					Devices\Constants::MESSAGE_BUS_DEVICE_PROPERTY_DOCUMENT_CREATED_ROUTING_KEY,
					Devices\Constants::MESSAGE_BUS_DEVICE_PROPERTY_DOCUMENT_UPDATED_ROUTING_KEY,
					Devices\Constants::MESSAGE_BUS_DEVICE_PROPERTY_DOCUMENT_DELETED_ROUTING_KEY,
				],
				true,
			)
		) {
			$this->configurationBuilderCache->clean([
				Caching\Cache::Tags => [Types\ConfigurationType::DEVICES_PROPERTIES->value],
			]);

			$this->configurationRepositoryCache->clean([
				Caching\Cache::Tags => [$document->getId()->toString()],
			]);
		} elseif (
			$document instanceof Devices\Documents\Devices\Controls\Control
			&& in_array(
				$routingKey,
				[
					Devices\Constants::MESSAGE_BUS_DEVICE_CONTROL_DOCUMENT_CREATED_ROUTING_KEY,
					Devices\Constants::MESSAGE_BUS_DEVICE_CONTROL_DOCUMENT_UPDATED_ROUTING_KEY,
					Devices\Constants::MESSAGE_BUS_DEVICE_CONTROL_DOCUMENT_DELETED_ROUTING_KEY,
				],
				true,
			)
		) {
			$this->configurationBuilderCache->clean([
				Caching\Cache::Tags => [Types\ConfigurationType::DEVICES_CONTROLS->value],
			]);

			$this->configurationRepositoryCache->clean([
				Caching\Cache::Tags => [$document->getId()->toString()],
			]);
		} elseif (
			$document instanceof Devices\Documents\Channels\Channel
			&& in_array(
				$routingKey,
				[
					Devices\Constants::MESSAGE_BUS_CHANNEL_DOCUMENT_CREATED_ROUTING_KEY,
					Devices\Constants::MESSAGE_BUS_CHANNEL_DOCUMENT_UPDATED_ROUTING_KEY,
					Devices\Constants::MESSAGE_BUS_CHANNEL_DOCUMENT_DELETED_ROUTING_KEY,
				],
				true,
			)
		) {
			$this->configurationBuilderCache->clean([
				Caching\Cache::Tags => [Types\ConfigurationType::CHANNELS->value],
			]);

			$this->configurationRepositoryCache->clean([
				Caching\Cache::Tags => [$document->getId()->toString()],
			]);
		} elseif (
			$document instanceof Devices\Documents\Channels\Properties\Property
			&& in_array(
				$routingKey,
				[
					Devices\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_DOCUMENT_CREATED_ROUTING_KEY,
					Devices\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_DOCUMENT_UPDATED_ROUTING_KEY,
					Devices\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_DOCUMENT_DELETED_ROUTING_KEY,
				],
				true,
			)
		) {
			$this->configurationBuilderCache->clean([
				Caching\Cache::Tags => [Types\ConfigurationType::CHANNELS_PROPERTIES->value],
			]);

			$this->configurationRepositoryCache->clean([
				Caching\Cache::Tags => [$document->getId()->toString()],
			]);
		} elseif (
			$document instanceof Devices\Documents\Channels\Controls\Control
			&& in_array(
				$routingKey,
				[
					Devices\Constants::MESSAGE_BUS_CHANNEL_CONTROL_DOCUMENT_CREATED_ROUTING_KEY,
					Devices\Constants::MESSAGE_BUS_CHANNEL_CONTROL_DOCUMENT_UPDATED_ROUTING_KEY,
					Devices\Constants::MESSAGE_BUS_CHANNEL_CONTROL_DOCUMENT_DELETED_ROUTING_KEY,
				],
				true,
			)
		) {
			$this->configurationBuilderCache->clean([
				Caching\Cache::Tags => [Types\ConfigurationType::CHANNELS_CONTROLS->value],
			]);

			$this->configurationRepositoryCache->clean([
				Caching\Cache::Tags => [$document->getId()->toString()],
			]);
		}
	}

}
