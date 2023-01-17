<?php declare(strict_types = 1);

/**
 * Tuya.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           25.04.22
 */

namespace FastyBird\Connector\Tuya\Entities;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Connector\Tuya\Exceptions;
use FastyBird\Connector\Tuya\Types;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use function is_string;

/**
 * @ORM\Entity
 */
class TuyaConnector extends DevicesEntities\Connectors\Connector
{

	public const CONNECTOR_TYPE = 'tuya';

	public function getType(): string
	{
		return self::CONNECTOR_TYPE;
	}

	public function getDiscriminatorName(): string
	{
		return self::CONNECTOR_TYPE;
	}

	public function getSource(): MetadataTypes\ConnectorSource
	{
		return MetadataTypes\ConnectorSource::get(MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getClientMode(): Types\ClientMode
	{
		$property = $this->properties
			->filter(
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Connectors\Properties\Property $property): bool => $property->getIdentifier() === Types\ConnectorPropertyIdentifier::IDENTIFIER_CLIENT_MODE
			)
			->first();

		if (
			$property instanceof DevicesEntities\Connectors\Properties\Variable
			&& Types\ClientMode::isValidValue($property->getValue())
		) {
			return Types\ClientMode::get($property->getValue());
		}

		throw new Exceptions\InvalidState('Connector mode is not configured');
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getOpenApiEndpoint(): Types\OpenApiEndpoint
	{
		$property = $this->properties
			->filter(
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Connectors\Properties\Property $property): bool => $property->getIdentifier() === Types\ConnectorPropertyIdentifier::IDENTIFIER_OPENAPI_ENDPOINT
			)
			->first();

		if (
			$property instanceof DevicesEntities\Connectors\Properties\Variable
			&& is_string($property->getValue())
			&& Types\OpenApiEndpoint::isValidValue($property->getValue())
		) {
			return Types\OpenApiEndpoint::get($property->getValue());
		}

		return Types\OpenApiEndpoint::get(Types\OpenApiEndpoint::ENDPOINT_EUROPE);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getOpenPulsarEndpoint(): Types\OpenPulsarEndpoint
	{
		$property = $this->properties
			->filter(
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Connectors\Properties\Property $property): bool => $property->getIdentifier() === Types\ConnectorPropertyIdentifier::IDENTIFIER_OPENPULSAR_ENDPOINT
			)
			->first();

		if (
			$property instanceof DevicesEntities\Connectors\Properties\Variable
			&& is_string($property->getValue())
			&& Types\OpenPulsarEndpoint::isValidValue($property->getValue())
		) {
			return Types\OpenPulsarEndpoint::get($property->getValue());
		}

		if (
			$this->getOpenApiEndpoint()->equalsValue(Types\OpenApiEndpoint::ENDPOINT_EUROPE)
			|| $this->getOpenApiEndpoint()->equalsValue(Types\OpenApiEndpoint::ENDPOINT_EUROPE_MS)
		) {
			return Types\OpenPulsarEndpoint::get(Types\OpenPulsarEndpoint::ENDPOINT_EUROPE);
		} elseif (
			$this->getOpenApiEndpoint()->equalsValue(Types\OpenApiEndpoint::ENDPOINT_AMERICA)
			|| $this->getOpenApiEndpoint()->equalsValue(Types\OpenApiEndpoint::ENDPOINT_AMERICA_AZURE)
		) {
			return Types\OpenPulsarEndpoint::get(Types\OpenPulsarEndpoint::ENDPOINT_AMERICA);
		} elseif ($this->getOpenApiEndpoint()->equalsValue(Types\OpenApiEndpoint::ENDPOINT_CHINA)) {
			return Types\OpenPulsarEndpoint::get(Types\OpenPulsarEndpoint::ENDPOINT_CHINA);
		} elseif ($this->getOpenApiEndpoint()->equalsValue(Types\OpenApiEndpoint::ENDPOINT_INDIA)) {
			return Types\OpenPulsarEndpoint::get(Types\OpenPulsarEndpoint::ENDPOINT_INDIA);
		}

		return Types\OpenPulsarEndpoint::get(Types\OpenPulsarEndpoint::ENDPOINT_EUROPE);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getOpenPulsarTopic(): Types\OpenPulsarTopic
	{
		$property = $this->properties
			->filter(
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Connectors\Properties\Property $property): bool => $property->getIdentifier() === Types\ConnectorPropertyIdentifier::IDENTIFIER_OPENPULSAR_TOPIC
			)
			->first();

		if (
			$property instanceof DevicesEntities\Connectors\Properties\Variable
			&& is_string($property->getValue())
			&& Types\OpenPulsarTopic::isValidValue($property->getValue())
		) {
			return Types\OpenPulsarTopic::get($property->getValue());
		}

		return Types\OpenPulsarTopic::get(Types\OpenPulsarTopic::TOPIC_PROD);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getAccessId(): string|null
	{
		$property = $this->properties
			->filter(
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Connectors\Properties\Property $property): bool => $property->getIdentifier() === Types\ConnectorPropertyIdentifier::IDENTIFIER_ACCESS_ID
			)
			->first();

		if (
			$property instanceof DevicesEntities\Connectors\Properties\Variable
			&& is_string($property->getValue())
		) {
			return $property->getValue();
		}

		return null;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getAccessSecret(): string|null
	{
		$property = $this->properties
			->filter(
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Connectors\Properties\Property $property): bool => $property->getIdentifier() === Types\ConnectorPropertyIdentifier::IDENTIFIER_ACCESS_SECRET
			)
			->first();

		if (
			$property instanceof DevicesEntities\Connectors\Properties\Variable
			&& is_string($property->getValue())
		) {
			return $property->getValue();
		}

		return null;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getUid(): string|null
	{
		$property = $this->properties
			->filter(
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Connectors\Properties\Property $property): bool => $property->getIdentifier() === Types\ConnectorPropertyIdentifier::IDENTIFIER_UID
			)
			->first();

		if (
			$property instanceof DevicesEntities\Connectors\Properties\Variable
			&& is_string($property->getValue())
		) {
			return $property->getValue();
		}

		return null;
	}

}
