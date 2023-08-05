<?php declare(strict_types = 1);

/**
 * DirectiveController.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Controllers
 * @since          1.0.0
 *
 * @date           11.07.23
 */

namespace FastyBird\Connector\NsPanel\Controllers;

use DateTimeInterface;
use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Helpers;
use FastyBird\Connector\NsPanel\Queries;
use FastyBird\Connector\NsPanel\Router;
use FastyBird\Connector\NsPanel\Servers;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Library\Exchange\Entities as ExchangeEntities;
use FastyBird\Library\Exchange\Exceptions as ExchangeExceptions;
use FastyBird\Library\Exchange\Publisher as ExchangePublisher;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\States as DevicesStates;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use IPub\DoctrineCrud\Exceptions as DoctrineCrudExceptions;
use IPub\Phone\Exceptions as PhoneExceptions;
use Nette\Utils;
use Orisai\ObjectMapper;
use Psr\Http\Message;
use Ramsey\Uuid;
use RuntimeException;
use function array_key_exists;
use function assert;
use function is_string;
use function preg_match;
use function strval;

/**
 * Gateway directive controller
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Controllers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DirectiveController extends BaseController
{

	public function __construct(
		private readonly bool $useExchange,
		private readonly DevicesModels\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Channels\ChannelsRepository $channelsRepository,
		private readonly DevicesModels\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
		private readonly DevicesModels\Channels\Properties\PropertiesManager $channelsPropertiesManager,
		private readonly DevicesUtilities\ChannelPropertiesStates $channelPropertiesStateManager,
		private readonly ExchangeEntities\EntityFactory $entityFactory,
		private readonly ExchangePublisher\Publisher $publisher,
	)
	{
	}

	/**
	 * @throws DoctrineCrudExceptions\InvalidArgumentException
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\ServerRequestError
	 * @throws ExchangeExceptions\InvalidState
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 * @throws PhoneExceptions\NoValidCountryException
	 * @throws PhoneExceptions\NoValidPhoneException
	 * @throws RuntimeException
	 * @throws Utils\JsonException
	 */
	public function process(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		$this->logger->debug(
			'Requested updating of characteristics of selected accessories',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
				'type' => 'characteristics-controller',
				'request' => [
					'address' => $request->getServerParams()['REMOTE_ADDR'],
					'path' => $request->getUri()->getPath(),
					'query' => $request->getQueryParams(),
					'body' => $request->getBody()->getContents(),
				],
			],
		);

		$request->getBody()->rewind();

		// At first, try to load device
		$device = $this->findDevice($request);

		try {
			$options = new ObjectMapper\Processing\Options();
			$options->setAllowUnknownFields();

			$requestData = $this->entityMapper->process(
				Utils\Json::decode($request->getBody()->getContents(), Utils\Json::FORCE_ARRAY),
				Entities\API\Request\SetDeviceStatus::class,
				$options,
			);
		} catch (ObjectMapper\Exception\InvalidData $ex) {
			$errorPrinter = new ObjectMapper\Printers\ErrorVisualPrinter(
				new ObjectMapper\Printers\TypeToStringConverter(),
			);

			throw new Exceptions\ServerRequestError(
				$request,
				Types\ServerStatus::get(Types\ServerStatus::INVALID_DIRECTIVE),
				'Could not map data to request entity: ' . $errorPrinter->printError($ex),
			);
		} catch (Utils\JsonException $ex) {
			throw new Exceptions\ServerRequestError(
				$request,
				Types\ServerStatus::get(Types\ServerStatus::INVALID_DIRECTIVE),
				'Request data are not valid JSON data',
				$ex->getCode(),
				$ex,
			);
		} catch (RuntimeException $ex) {
			throw new Exceptions\ServerRequestError(
				$request,
				Types\ServerStatus::get(Types\ServerStatus::INVALID_DIRECTIVE),
				'Could not read data from request',
				$ex->getCode(),
				$ex,
			);
		}

		foreach ($requestData->getDirective()->getPayload()->getStatuses() as $key => $status) {
			$stateIdentifier = null;

			if (
				is_string($key)
				&& preg_match(NsPanel\Constants::STATE_NAME_KEY, $key, $matches) === 1
				&& array_key_exists('identifier', $matches)
			) {
				$stateIdentifier = $matches['identifier'];
			}

			$findChannelQuery = new Queries\FindChannels();
			$findChannelQuery->forDevice($device);
			$findChannelQuery->byIdentifier(
				Helpers\Name::convertCapabilityToChannel($status->getType(), $stateIdentifier),
			);

			$channel = $this->channelsRepository->findOneBy(
				$findChannelQuery,
				Entities\NsPanelChannel::class,
			);

			if ($channel !== null) {
				foreach ($status->getProtocols() as $protocol => $value) {
					$protocol = Types\Protocol::get($protocol);

					$findChannelPropertiesQuery = new DevicesQueries\FindChannelProperties();
					$findChannelPropertiesQuery->forChannel($channel);
					$findChannelPropertiesQuery->byIdentifier(Helpers\Name::convertProtocolToProperty($protocol));

					$property = $this->channelsPropertiesRepository->findOneBy($findChannelPropertiesQuery);

					if ($property === null) {
						continue;
					}

					assert(
						$property instanceof DevicesEntities\Channels\Properties\Dynamic
						|| $property instanceof DevicesEntities\Channels\Properties\Mapped
						|| $property instanceof DevicesEntities\Channels\Properties\Variable,
					);

					$value = Helpers\Transformer::transformValueFromDevice(
						$property->getDataType(),
						$property->getFormat(),
						$value,
					);

					$this->writeProperty($device, $channel, $property, $value);
				}
			}
		}

		try {
			$options = new ObjectMapper\Processing\Options();
			$options->setAllowUnknownFields();

			$responseData = $this->entityMapper->process(
				[
					'event' => [
						'header' => [
							'name' => Types\Header::UPDATE_DEVICE_STATES_RESPONSE,
							'message_id' => $requestData->getDirective()->getHeader()->getMessageId(),
							'version' => NsPanel\Constants::NS_PANEL_API_VERSION_V1,
						],
					],
				],
				Entities\API\Response\SetDeviceStatus::class,
				$options,
			);

			$response->getBody()->write(Utils\Json::encode($responseData->toJson()));
		} catch (ObjectMapper\Exception\InvalidData $ex) {
			$errorPrinter = new ObjectMapper\Printers\ErrorVisualPrinter(
				new ObjectMapper\Printers\TypeToStringConverter(),
			);

			throw new Exceptions\ServerRequestError(
				$request,
				Types\ServerStatus::get(Types\ServerStatus::INTERNAL_ERROR),
				'Could not map data to response entity: ' . $errorPrinter->printError($ex),
			);
		} catch (Utils\JsonException $ex) {
			throw new Exceptions\ServerRequestError(
				$request,
				Types\ServerStatus::get(Types\ServerStatus::INTERNAL_ERROR),
				'Response data are not valid JSON data',
				$ex->getCode(),
				$ex,
			);
		} catch (RuntimeException $ex) {
			throw new Exceptions\ServerRequestError(
				$request,
				Types\ServerStatus::get(Types\ServerStatus::INTERNAL_ERROR),
				'Could not write data to response',
				$ex->getCode(),
				$ex,
			);
		}

		return $response;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\ServerRequestError
	 */
	private function findDevice(Message\ServerRequestInterface $request): Entities\Devices\ThirdPartyDevice
	{
		$id = strval($request->getAttribute(Router\Router::URL_DEVICE_ID));

		$connectorId = strval($request->getAttribute(Servers\Http::REQUEST_ATTRIBUTE_CONNECTOR));

		if (!Uuid\Uuid::isValid($connectorId)) {
			throw new Exceptions\ServerRequestError(
				$request,
				Types\ServerStatus::get(Types\ServerStatus::ENDPOINT_UNREACHABLE),
				'Connector id could not be determined',
			);
		}

		try {
			$findQuery = new Queries\FindThirdPartyDevices();
			$findQuery->byId(Uuid\Uuid::fromString($id));
			$findQuery->byConnectorId(Uuid\Uuid::fromString($connectorId));

			$device = $this->devicesRepository->findOneBy($findQuery, Entities\Devices\ThirdPartyDevice::class);

			if ($device === null) {
				throw new Exceptions\ServerRequestError(
					$request,
					Types\ServerStatus::get(Types\ServerStatus::ENDPOINT_UNREACHABLE),
					'Device could could not be found',
				);
			}
		} catch (Uuid\Exception\InvalidUuidStringException) {
			throw new Exceptions\ServerRequestError(
				$request,
				Types\ServerStatus::get(Types\ServerStatus::ENDPOINT_UNREACHABLE),
				'Device could could not be found',
			);
		}

		return $device;
	}

	/**
	 * @throws DoctrineCrudExceptions\InvalidArgumentException
	 * @throws DevicesExceptions\InvalidState
	 * @throws ExchangeExceptions\InvalidState
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 * @throws PhoneExceptions\NoValidCountryException
	 * @throws PhoneExceptions\NoValidPhoneException
	 * @throws Utils\JsonException
	 */
	private function writeProperty(
		Entities\Devices\ThirdPartyDevice $device,
		Entities\NsPanelChannel $channel,
		DevicesEntities\Channels\Properties\Dynamic|DevicesEntities\Channels\Properties\Mapped|DevicesEntities\Channels\Properties\Variable $property,
		float|int|string|bool|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|DateTimeInterface|null $value,
	): void
	{
		if ($property instanceof DevicesEntities\Channels\Properties\Variable) {
			$this->channelsPropertiesManager->update(
				$property,
				Utils\ArrayHash::from([
					'value' => $value,
				]),
			);

			return;
		}

		if ($this->useExchange) {
			$this->publisher->publish(
				MetadataTypes\ModuleSource::get(
					MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
				),
				MetadataTypes\RoutingKey::get(
					MetadataTypes\RoutingKey::ROUTE_CHANNEL_PROPERTY_ACTION,
				),
				$this->entityFactory->create(
					Utils\Json::encode([
						'action' => MetadataTypes\PropertyAction::ACTION_SET,
						'device' => $device->getId()->toString(),
						'channel' => $channel->getId()->toString(),
						'property' => $property->getId()->toString(),
						'expected_value' => DevicesUtilities\ValueHelper::flattenValue($value),
					]),
					MetadataTypes\RoutingKey::get(
						MetadataTypes\RoutingKey::ROUTE_CHANNEL_PROPERTY_ACTION,
					),
				),
			);
		} else {
			$this->channelPropertiesStateManager->writeValue(
				$property,
				Utils\ArrayHash::from([
					DevicesStates\Property::EXPECTED_VALUE_KEY => $value,
					DevicesStates\Property::PENDING_KEY => true,
				]),
			);
		}
	}

}
