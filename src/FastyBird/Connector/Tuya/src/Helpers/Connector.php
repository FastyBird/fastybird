<?php declare(strict_types = 1);

/**
 * Device.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Helpers
 * @since          1.0.0
 *
 * @date           20.11.23
 */

namespace FastyBird\Connector\Tuya\Helpers;

use FastyBird\Connector\Tuya\Documents;
use FastyBird\Connector\Tuya\Exceptions;
use FastyBird\Connector\Tuya\Types;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use function assert;
use function is_string;

/**
 * Connector helper
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Helpers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Connector
{

	public function __construct(
		private readonly DevicesModels\Configuration\Connectors\Properties\Repository $connectorsPropertiesConfigurationRepository,
	)
	{
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getClientMode(Documents\Connectors\Connector $connector): Types\ClientMode
	{
		$findPropertyQuery = new DevicesQueries\Configuration\FindConnectorVariableProperties();
		$findPropertyQuery->forConnector($connector);
		$findPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::CLIENT_MODE);

		$property = $this->connectorsPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			DevicesDocuments\Connectors\Properties\Variable::class,
		);

		$value = $property?->getValue();

		if (is_string($value) && Types\ClientMode::isValidValue($value)) {
			return Types\ClientMode::get($value);
		}

		throw new Exceptions\InvalidState('Connector mode is not configured');
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getOpenApiEndpoint(
		DevicesDocuments\Connectors\Connector $connector,
	): Types\OpenApiEndpoint
	{
		$findPropertyQuery = new DevicesQueries\Configuration\FindConnectorVariableProperties();
		$findPropertyQuery->forConnector($connector);
		$findPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::OPENAPI_ENDPOINT);

		$property = $this->connectorsPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			DevicesDocuments\Connectors\Properties\Variable::class,
		);

		$value = $property?->getValue();

		if (is_string($value) && Types\OpenApiEndpoint::isValidValue($value)) {
			return Types\OpenApiEndpoint::get($value);
		}

		return Types\OpenApiEndpoint::get(Types\OpenApiEndpoint::EUROPE);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getOpenPulsarEndpoint(
		DevicesDocuments\Connectors\Connector $connector,
	): Types\OpenPulsarEndpoint
	{
		$findPropertyQuery = new DevicesQueries\Configuration\FindConnectorVariableProperties();
		$findPropertyQuery->forConnector($connector);
		$findPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::OPENPULSAR_ENDPOINT);

		$property = $this->connectorsPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			DevicesDocuments\Connectors\Properties\Variable::class,
		);

		$value = $property?->getValue();

		if (is_string($value) && Types\OpenPulsarEndpoint::isValidValue($value)) {
			return Types\OpenPulsarEndpoint::get($value);
		}

		if (
			$this->getOpenApiEndpoint($connector)->equalsValue(Types\OpenApiEndpoint::EUROPE)
			|| $this->getOpenApiEndpoint($connector)->equalsValue(Types\OpenApiEndpoint::EUROPE_MS)
		) {
			return Types\OpenPulsarEndpoint::get(Types\OpenPulsarEndpoint::EUROPE);
		} elseif (
			$this->getOpenApiEndpoint($connector)->equalsValue(Types\OpenApiEndpoint::AMERICA)
			|| $this->getOpenApiEndpoint($connector)->equalsValue(Types\OpenApiEndpoint::AMERICA_AZURE)
		) {
			return Types\OpenPulsarEndpoint::get(Types\OpenPulsarEndpoint::AMERICA);
		} elseif ($this->getOpenApiEndpoint($connector)->equalsValue(Types\OpenApiEndpoint::CHINA)) {
			return Types\OpenPulsarEndpoint::get(Types\OpenPulsarEndpoint::CHINA);
		} elseif ($this->getOpenApiEndpoint($connector)->equalsValue(Types\OpenApiEndpoint::INDIA)) {
			return Types\OpenPulsarEndpoint::get(Types\OpenPulsarEndpoint::INDIA);
		}

		return Types\OpenPulsarEndpoint::get(Types\OpenPulsarEndpoint::EUROPE);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getOpenPulsarTopic(
		DevicesDocuments\Connectors\Connector $connector,
	): Types\OpenPulsarTopic
	{
		$findPropertyQuery = new DevicesQueries\Configuration\FindConnectorVariableProperties();
		$findPropertyQuery->forConnector($connector);
		$findPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::OPENPULSAR_TOPIC);

		$property = $this->connectorsPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			DevicesDocuments\Connectors\Properties\Variable::class,
		);

		$value = $property?->getValue();

		if (is_string($value) && Types\OpenPulsarTopic::isValidValue($value)) {
			return Types\OpenPulsarTopic::get($value);
		}

		return Types\OpenPulsarTopic::get(Types\OpenPulsarTopic::PROD);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getAccessId(Documents\Connectors\Connector $connector): string|null
	{
		$findPropertyQuery = new DevicesQueries\Configuration\FindConnectorVariableProperties();
		$findPropertyQuery->forConnector($connector);
		$findPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::ACCESS_ID);

		$property = $this->connectorsPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			DevicesDocuments\Connectors\Properties\Variable::class,
		);

		if ($property?->getValue() === null) {
			return null;
		}

		$value = $property->getValue();
		assert(is_string($value));

		return $value;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getAccessSecret(Documents\Connectors\Connector $connector): string|null
	{
		$findPropertyQuery = new DevicesQueries\Configuration\FindConnectorVariableProperties();
		$findPropertyQuery->forConnector($connector);
		$findPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::ACCESS_SECRET);

		$property = $this->connectorsPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			DevicesDocuments\Connectors\Properties\Variable::class,
		);

		if ($property?->getValue() === null) {
			return null;
		}

		$value = $property->getValue();
		assert(is_string($value));

		return $value;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getUid(Documents\Connectors\Connector $connector): string|null
	{
		$findPropertyQuery = new DevicesQueries\Configuration\FindConnectorVariableProperties();
		$findPropertyQuery->forConnector($connector);
		$findPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::UID);

		$property = $this->connectorsPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			DevicesDocuments\Connectors\Properties\Variable::class,
		);

		if ($property?->getValue() === null) {
			return null;
		}

		$value = $property->getValue();
		assert(is_string($value));

		return $value;
	}

}
