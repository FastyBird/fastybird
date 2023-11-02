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

use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Helpers;
use FastyBird\Connector\NsPanel\Queries;
use FastyBird\Connector\NsPanel\Queue;
use FastyBird\Connector\NsPanel\Router;
use FastyBird\Connector\NsPanel\Servers;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Library\Exchange\Exceptions as ExchangeExceptions;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Schemas as MetadataSchemas;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette\Utils;
use Psr\Http\Message;
use Ramsey\Uuid;
use RuntimeException;
use function array_key_exists;
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

	private const SET_DEVICE_STATE_MESSAGE_SCHEMA_FILENAME = 'set_device_state.json';

	public function __construct(
		private readonly Queue\Queue $queue,
		private readonly Helpers\Entity $entityHelper,
		private readonly DevicesModels\Devices\DevicesRepository $devicesRepository,
		private readonly MetadataSchemas\Validator $schemaValidator,
	)
	{
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\ServerRequestError
	 * @throws ExchangeExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 * @throws RuntimeException
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
				'type' => 'directive-controller',
				'request' => [
					'method' => $request->getMethod(),
					'address' => $request->getServerParams()['REMOTE_ADDR'],
					'path' => $request->getUri()->getPath(),
					'query' => $request->getQueryParams(),
					'body' => $request->getBody()->getContents(),
				],
			],
		);

		$connectorId = strval($request->getAttribute(Servers\Http::REQUEST_ATTRIBUTE_CONNECTOR));

		if (!Uuid\Uuid::isValid($connectorId)) {
			throw new Exceptions\ServerRequestError(
				$request,
				Types\ServerStatus::get(Types\ServerStatus::INTERNAL_ERROR),
				'Connector id could not be determined',
			);
		}

		$connectorId = Uuid\Uuid::fromString($connectorId);

		$request->getBody()->rewind();

		$body = $request->getBody()->getContents();

		// At first, try to load gateway
		$gateway = $this->findGateway($request, $connectorId);

		// At first, try to load device
		$device = $this->findDevice($request, $connectorId, $gateway);

		try {
			$body = $this->schemaValidator->validate(
				$body,
				$this->getSchema(self::SET_DEVICE_STATE_MESSAGE_SCHEMA_FILENAME),
			);
		} catch (MetadataExceptions\Logic | MetadataExceptions\MalformedInput | MetadataExceptions\InvalidData $ex) {
			throw new Exceptions\ServerRequestError(
				$request,
				Types\ServerStatus::get(Types\ServerStatus::INVALID_DIRECTIVE),
				'Could not validate received response payload',
				$ex->getCode(),
				$ex,
			);
		} catch (Exceptions\InvalidArgument $ex) {
			throw new Exceptions\ServerRequestError(
				$request,
				Types\ServerStatus::get(Types\ServerStatus::INTERNAL_ERROR),
				'Could not validate received response payload',
				$ex->getCode(),
				$ex,
			);
		}

		try {
			$requestData = $this->entityHelper->create(
				Entities\API\Request\SetDeviceState::class,
				(array) Utils\Json::decode(Utils\Json::encode($body), Utils\Json::FORCE_ARRAY),
			);
		} catch (Exceptions\Runtime $ex) {
			throw new Exceptions\ServerRequestError(
				$request,
				Types\ServerStatus::get(Types\ServerStatus::INVALID_DIRECTIVE),
				'Could not map data to request entity',
				$ex->getCode(),
				$ex,
			);
		} catch (Utils\JsonException $ex) {
			throw new Exceptions\ServerRequestError(
				$request,
				Types\ServerStatus::get(Types\ServerStatus::INVALID_DIRECTIVE),
				'Request data are not valid JSON data',
				$ex->getCode(),
				$ex,
			);
		}

		$state = [];

		foreach ($requestData->getDirective()->getPayload()->getState() as $key => $item) {
			$identifier = null;

			if (
				is_string($key)
				&& preg_match(NsPanel\Constants::STATE_NAME_KEY, $key, $matches) === 1
				&& array_key_exists('identifier', $matches)
			) {
				$identifier = $matches['identifier'];
			}

			foreach ($item->getProtocols() as $protocol => $value) {
				$state[] = [
					'capability' => $item->getType()->getValue(),
					'protocol' => $protocol,
					'value' => DevicesUtilities\ValueHelper::flattenValue($value),
					'identifier' => $identifier,
				];
			}
		}

		$this->queue->append(
			$this->entityHelper->create(
				Entities\Messages\StoreDeviceState::class,
				[
					'connector' => $connectorId->toString(),
					'gateway' => $gateway->getId()->toString(),
					'identifier' => $device->getIdentifier(),
					'state' => $state,
				],
			),
		);

		try {
			$responseData = $this->entityHelper->create(
				Entities\API\Response\SetDeviceState::class,
				[
					'event' => [
						'header' => [
							'name' => Types\Header::UPDATE_DEVICE_STATES_RESPONSE,
							'message_id' => $requestData->getDirective()->getHeader()->getMessageId(),
							'version' => NsPanel\Constants::NS_PANEL_API_VERSION_V1,
						],
					],
				],
			);

			$response->getBody()->write(Utils\Json::encode($responseData->toJson()));
		} catch (Exceptions\Runtime $ex) {
			throw new Exceptions\ServerRequestError(
				$request,
				Types\ServerStatus::get(Types\ServerStatus::INTERNAL_ERROR),
				'Could not map data to response entity',
				$ex->getCode(),
				$ex,
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
	private function findGateway(
		Message\ServerRequestInterface $request,
		Uuid\UuidInterface $connectorId,
	): Entities\Devices\Gateway
	{
		$id = strval($request->getAttribute(Router\Router::URL_GATEWAY_ID));

		try {
			$findQuery = new Queries\FindGatewayDevices();
			$findQuery->byId(Uuid\Uuid::fromString($id));
			$findQuery->byConnectorId($connectorId);

			$gateway = $this->devicesRepository->findOneBy($findQuery, Entities\Devices\Gateway::class);

			if ($gateway === null) {
				throw new Exceptions\ServerRequestError(
					$request,
					Types\ServerStatus::get(Types\ServerStatus::ENDPOINT_UNREACHABLE),
					'Device gateway could could not be found',
				);
			}
		} catch (Uuid\Exception\InvalidUuidStringException) {
			throw new Exceptions\ServerRequestError(
				$request,
				Types\ServerStatus::get(Types\ServerStatus::ENDPOINT_UNREACHABLE),
				'Device gateway could could not be found',
			);
		}

		return $gateway;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\ServerRequestError
	 */
	private function findDevice(
		Message\ServerRequestInterface $request,
		Uuid\UuidInterface $connectorId,
		Entities\Devices\Gateway $gateway,
	): Entities\Devices\ThirdPartyDevice
	{
		$id = strval($request->getAttribute(Router\Router::URL_DEVICE_ID));

		try {
			$findQuery = new Queries\FindThirdPartyDevices();
			$findQuery->byId(Uuid\Uuid::fromString($id));
			$findQuery->byConnectorId($connectorId);
			$findQuery->forParent($gateway);

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

}
