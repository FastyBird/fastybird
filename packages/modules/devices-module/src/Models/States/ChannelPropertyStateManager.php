<?php declare(strict_types = 1);

/**
 * ChannelPropertyStateManager.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     DynamicProperties
 * @since          0.73.0
 *
 * @date           23.08.22
 */

namespace FastyBird\DevicesModule\Models\States;

use FastyBird\DevicesModule\Exceptions as DevicesModuleExceptions;
use FastyBird\DevicesModule\Models as DevicesModuleModels;
use FastyBird\Metadata;
use Nette;
use Nette\Utils;
use Psr\Log;
use function is_array;

/**
 * Useful channel dynamic property state helpers
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Helpers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ChannelPropertyStateManager
{

	use Nette\SmartObject;

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly DevicesModuleModels\DataStorage\ChannelsRepository $channelsRepository,
		private readonly DevicesModuleModels\States\ChannelPropertiesRepository $channelPropertyStateRepository,
		private readonly DevicesModuleModels\States\ChannelPropertiesManager $channelPropertiesStatesManager,
		Log\LoggerInterface|null $logger,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * @throws Metadata\Exceptions\FileNotFound
	 */
	public function setValue(
		Metadata\Entities\DevicesModule\ChannelDynamicProperty $property,
		Utils\ArrayHash $data,
	): void
	{
		try {
			$propertyState = $this->channelPropertyStateRepository->findOne($property);
		} catch (DevicesModuleExceptions\NotImplemented) {
			$this->logger->warning(
				'DynamicProperties repository is not configured. State could not be fetched',
				[
					'source' => Metadata\Constants::MODULE_DEVICES_SOURCE,
					'type' => 'channel-property-state-manager',
				],
			);

			return;
		}

		try {
			// In case synchronization failed...
			if ($propertyState === null) {
				// ...create state in storage
				$propertyState = $this->channelPropertiesStatesManager->create(
					$property,
					$data,
				);

				$channel = $this->channelsRepository->findById($property->getChannel());

				$this->logger->debug(
					'Channel property state was created',
					[
						'source' => Metadata\Constants::MODULE_DEVICES_SOURCE,
						'type' => 'channel-property-state-manager',
						'device' => [
							'id' => $channel?->getDevice()->toString(),
						],
						'channel' => [
							'id' => $property->getChannel()->toString(),
						],
						'property' => [
							'id' => $property->getId()->toString(),
							'state' => $propertyState->toArray(),
						],
					],
				);
			} else {
				$propertyState = $this->channelPropertiesStatesManager->update(
					$property,
					$propertyState,
					$data,
				);

				$channel = $this->channelsRepository->findById($property->getChannel());

				$this->logger->debug(
					'Channel property state was updated',
					[
						'source' => Metadata\Constants::MODULE_DEVICES_SOURCE,
						'type' => 'channel-property-state-manager',
						'device' => [
							'id' => $channel?->getDevice()->toString(),
						],
						'channel' => [
							'id' => $property->getChannel()->toString(),
						],
						'property' => [
							'id' => $property->getId()->toString(),
							'state' => $propertyState->toArray(),
						],
					],
				);
			}
		} catch (DevicesModuleExceptions\NotImplemented) {
			$this->logger->warning(
				'DynamicProperties manager is not configured. State could not be saved',
				[
					'source' => Metadata\Constants::MODULE_DEVICES_SOURCE,
					'type' => 'channel-property-state-manager',
				],
			);
		}
	}

	/**
	 * @param Metadata\Entities\DevicesModule\ChannelDynamicProperty|Array<Metadata\Entities\DevicesModule\ChannelDynamicProperty> $property
	 *
	 * @throws Metadata\Exceptions\FileNotFound
	 */
	public function setValidState(
		Metadata\Entities\DevicesModule\ChannelDynamicProperty|array $property,
		bool $state,
	): void
	{
		if (is_array($property)) {
			foreach ($property as $item) {
				$this->setValue($item, Utils\ArrayHash::from([
					'valid' => $state,
				]));
			}
		} else {
			$this->setValue($property, Utils\ArrayHash::from([
				'valid' => $state,
			]));
		}
	}

}
