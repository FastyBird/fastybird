<?php declare(strict_types = 1);

/**
 * OpenApiFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     API
 * @since          0.13.0
 *
 * @date           27.08.22
 */

namespace FastyBird\Connector\Tuya\API;

use FastyBird\Connector\Tuya\Entities;
use FastyBird\Connector\Tuya\Exceptions;
use FastyBird\Connector\Tuya\Helpers;
use FastyBird\Connector\Tuya\Types;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Schemas as MetadataSchemas;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use Psr\Log;
use React\EventLoop;
use function strval;

/**
 * OpenAPI API factory
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class OpenApiFactory
{

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly EntityFactory $entityFactory,
		private readonly Helpers\Connector $connectorHelper,
		private readonly MetadataSchemas\Validator $schemaValidator,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		private readonly EventLoop\LoopInterface $eventLoop,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function create(Entities\TuyaConnector $connector): OpenApi
	{
		$endpoint = $this->connectorHelper->getConfiguration(
			$connector,
			Types\ConnectorPropertyIdentifier::get(Types\ConnectorPropertyIdentifier::IDENTIFIER_OPENAPI_ENDPOINT),
		);

		if (!Types\OpenApiEndpoint::isValidValue($endpoint)) {
			throw new Exceptions\InvalidState('Configured OpenAPI endpoint is not valid');
		}

		$accessId = $this->connectorHelper->getConfiguration(
			$connector,
			Types\ConnectorPropertyIdentifier::get(Types\ConnectorPropertyIdentifier::IDENTIFIER_ACCESS_ID),
		);

		$accessSecret = $this->connectorHelper->getConfiguration(
			$connector,
			Types\ConnectorPropertyIdentifier::get(Types\ConnectorPropertyIdentifier::IDENTIFIER_ACCESS_SECRET),
		);

		return new OpenApi(
			strval($accessId),
			strval($accessSecret),
			'en',
			Types\OpenApiEndpoint::get($endpoint),
			$this->entityFactory,
			$this->schemaValidator,
			$this->dateTimeFactory,
			$this->eventLoop,
			$this->logger,
		);
	}

}
