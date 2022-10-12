<?php declare(strict_types = 1);

/**
 * CloudFactory.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     API
 * @since          0.13.0
 *
 * @date           27.08.22
 */

namespace FastyBird\TuyaConnector\API;

use FastyBird\DateTimeFactory;
use FastyBird\Metadata\Entities as MetadataEntities;
use FastyBird\Metadata\Schemas as MetadataSchemas;
use FastyBird\TuyaConnector\Exceptions;
use FastyBird\TuyaConnector\Helpers;
use FastyBird\TuyaConnector\Types;
use Psr\Log;
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
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	public function create(MetadataEntities\DevicesModule\Connector $connector): OpenApi
	{
		$endpoint = $this->connectorHelper->getConfiguration(
			$connector->getId(),
			Types\ConnectorPropertyIdentifier::get(Types\ConnectorPropertyIdentifier::IDENTIFIER_OPENAPI_ENDPOINT),
		);

		if (!Types\OpenApiEndpoint::isValidValue($endpoint)) {
			throw new Exceptions\InvalidState('Configured OpenAPI endpoint is not valid');
		}

		$accessId = $this->connectorHelper->getConfiguration(
			$connector->getId(),
			Types\ConnectorPropertyIdentifier::get(Types\ConnectorPropertyIdentifier::IDENTIFIER_ACCESS_ID),
		);

		$accessSecret = $this->connectorHelper->getConfiguration(
			$connector->getId(),
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
			$this->logger,
		);
	}

}
