<?php declare(strict_types = 1);

/**
 * Http.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           18.07.22
 */

namespace FastyBird\Connector\Shelly\Clients\Local;

use FastyBird\Connector\Shelly\API;
use FastyBird\Connector\Shelly\Consumers;
use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Exceptions;
use FastyBird\Connector\Shelly\Types;
use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use Fig\Http\Message\StatusCodeInterface;
use Nette;
use Psr\Log;
use React\Http as ReactHttp;
use React\Promise;
use Throwable;
use function assert;
use function gethostbyname;

/**
 * HTTP api client
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Http
{

	use Nette\SmartObject;

	private API\Gen1HttpApi|null $gen1httpApi = null;

	private API\Gen2HttpApi|null $gen2httpApi = null;

	public function __construct(
		private readonly API\Gen1HttpApiFactory $gen1HttpApiFactory,
		private readonly API\Gen2HttpApiFactory $gen2HttpApiFactory,
		private readonly Consumers\Messages $consumer,
		protected readonly DevicesModels\Devices\Properties\PropertiesRepository $devicePropertiesRepository,
		protected readonly DevicesModels\Channels\ChannelsRepository $channelsRepository,
		protected readonly DevicesModels\Channels\Properties\PropertiesRepository $channelPropertiesRepository,
		private readonly Log\LoggerInterface $logger = new Log\NullLogger(),
	)
	{
	}

	public function connect(): void
	{
		$this->gen1httpApi = $this->gen1HttpApiFactory->create();
		$this->gen2httpApi = $this->gen2HttpApiFactory->create();
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\HttpApiCall
	 * @throws Exceptions\HttpApiError
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function writeChannelProperty(
		Entities\ShellyDevice $device,
		DevicesEntities\Channels\Channel $channel,
		DevicesEntities\Channels\Properties\Dynamic|MetadataEntities\DevicesModule\ChannelDynamicProperty $property,
		bool|float|int|string $value,
	): Promise\PromiseInterface
	{
		$address = $this->getDeviceAddress($device);

		if ($address === null) {
			$this->consumer->append(
				new Entities\Messages\DeviceState(
					$device->getConnector()->getId(),
					$device->getIdentifier(),
					MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_STOPPED),
				),
			);

			return Promise\reject(new Exceptions\InvalidState('Device ip address or domain is not configured'));
		}

		$generation = $device->getGeneration();

		if ($generation->equalsValue(Types\DeviceGeneration::GENERATION_1)) {
			$result = $this->gen1httpApi?->setDeviceState(
				$address,
				$device->getUsername(),
				$device->getPassword(),
				$channel->getIdentifier(),
				$property->getIdentifier(),
				$value,
			);
			assert($result instanceof Promise\ExtendedPromiseInterface);

		} elseif ($generation->equalsValue(Types\DeviceGeneration::GENERATION_2)) {
			$result = $this->gen2httpApi?->setDeviceStatus(
				$address,
				$device->getUsername(),
				$device->getPassword(),
				$property->getIdentifier(),
				$value,
			);
			assert($result instanceof Promise\ExtendedPromiseInterface);

		} else {
			return Promise\reject(new Exceptions\InvalidState('Unsupported device generation'));
		}

		$result
			->otherwise(function (Throwable $ex) use ($device): void {
				if ($ex instanceof ReactHttp\Message\ResponseException) {
					if (
						$ex->getCode() >= StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR
						&& $ex->getCode() < StatusCodeInterface::STATUS_NETWORK_AUTHENTICATION_REQUIRED
					) {
						$this->consumer->append(
							new Entities\Messages\DeviceState(
								$device->getConnector()->getId(),
								$device->getIdentifier(),
								MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_LOST),
							),
						);
					}
				}

				if ($ex instanceof Exceptions\Runtime) {
					$this->consumer->append(
						new Entities\Messages\DeviceState(
							$device->getConnector()->getId(),
							$device->getIdentifier(),
							MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_LOST),
						),
					);
				}
			});

		return $result;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function getDeviceAddress(Entities\ShellyDevice $device): string|null
	{
		$domain = $device->getDomain();

		if ($domain !== null) {
			return gethostbyname($domain);
		}

		$ipAddress = $device->getIpAddress();

		if ($ipAddress !== null) {
			return $ipAddress;
		}

		$this->logger->error(
			'Device ip address or domain is not configured',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
				'type' => 'http-client',
				'device' => [
					'id' => $device->getPlainId(),
				],
			],
		);

		return null;
	}

}
