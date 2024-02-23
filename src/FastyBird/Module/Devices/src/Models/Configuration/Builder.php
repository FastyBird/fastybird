<?php declare(strict_types = 1);

/**
 * Builder.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 * @since          1.0.0
 *
 * @date           13.11.23
 */

namespace FastyBird\Module\Devices\Models\Configuration;

use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Types;
use Flow\JSONPath;
use Nette\Caching;
use Orisai\DataSources;
use Throwable;
use TypeError;
use ValueError;
use function array_key_exists;
use function assert;
use function is_string;

/**
 * Configuration builder
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Builder
{

	/** @var array<string, JSONPath\JSONPath> */
	private array $configuration = [];

	public function __construct(
		private readonly Models\Entities\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly Models\Entities\Connectors\Properties\PropertiesRepository $connectorsPropertiesRepository,
		private readonly Models\Entities\Connectors\Controls\ControlsRepository $connectorsControlsRepository,
		private readonly Models\Entities\Devices\DevicesRepository $devicesRepository,
		private readonly Models\Entities\Devices\Properties\PropertiesRepository $devicesPropertiesRepository,
		private readonly Models\Entities\Devices\Controls\ControlsRepository $devicesControlsRepository,
		private readonly Models\Entities\Channels\ChannelsRepository $channelsRepository,
		private readonly Models\Entities\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
		private readonly Models\Entities\Channels\Controls\ControlsRepository $channelsControlsRepository,
		private readonly DataSources\DefaultDataSource $dataSource,
		private readonly Caching\Cache $cache,
	)
	{
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function load(Types\ConfigurationType $type, bool $force = false): JSONPath\JSONPath
	{
		if (!array_key_exists($type->value, $this->configuration) || $force) {
			try {
				if ($force) {
					$this->cache->remove($type->value);
				}

				$data = $this->cache->load(
					$type->value,
					fn (): string => $this->build($type),
					[
						Caching\Cache::Tags => [$type->value],
					],
				);
				assert(is_string($data));

				$decoded = $this->dataSource->decode($data, 'json');
			} catch (Throwable $ex) {
				throw new Exceptions\InvalidState('Module configuration could not be read', $ex->getCode(), $ex);
			}

			$this->configuration[$type->value] = new JSONPath\JSONPath($decoded);
		}

		return $this->configuration[$type->value];
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function build(Types\ConfigurationType $type): string
	{
		$data = [];

		if ($type === Types\ConfigurationType::CONNECTORS) {
			foreach ($this->connectorsRepository->findAll() as $item) {
				$data[] = $item->toArray();
			}
		} elseif ($type === Types\ConfigurationType::CONNECTORS_PROPERTIES) {
			foreach ($this->connectorsPropertiesRepository->findAll() as $item) {
				$data[] = $item->toArray();
			}
		} elseif ($type === Types\ConfigurationType::CONNECTORS_CONTROLS) {
			foreach ($this->connectorsControlsRepository->findAll() as $item) {
				$data[] = $item->toArray();
			}
		} elseif ($type === Types\ConfigurationType::DEVICES) {
			foreach ($this->devicesRepository->findAll() as $item) {
				$data[] = $item->toArray();
			}
		} elseif ($type === Types\ConfigurationType::DEVICES_PROPERTIES) {
			foreach ($this->devicesPropertiesRepository->findAll() as $item) {
				$data[] = $item->toArray();
			}
		} elseif ($type === Types\ConfigurationType::DEVICES_CONTROLS) {
			foreach ($this->devicesControlsRepository->findAll() as $item) {
				$data[] = $item->toArray();
			}
		} elseif ($type === Types\ConfigurationType::CHANNELS) {
			foreach ($this->channelsRepository->findAll() as $item) {
				$data[] = $item->toArray();
			}
		} elseif ($type === Types\ConfigurationType::CHANNELS_PROPERTIES) {
			foreach ($this->channelsPropertiesRepository->findAll() as $item) {
				$data[] = $item->toArray();
			}
		} elseif ($type === Types\ConfigurationType::CHANNELS_CONTROLS) {
			foreach ($this->channelsControlsRepository->findAll() as $item) {
				$data[] = $item->toArray();
			}
		}

		try {
			return $this->dataSource->encode($data, 'json');
		} catch (DataSources\Exception\NotSupportedType | DataSources\Exception\EncodingFailure $ex) {
			throw new Exceptions\InvalidState(
				'Module configuration structure could not be create',
				$ex->getCode(),
				$ex,
			);
		}
	}

}
