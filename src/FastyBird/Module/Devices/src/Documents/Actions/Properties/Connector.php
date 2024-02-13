<?php declare(strict_types = 1);

/**
 * Connector.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           01.06.22
 */

namespace FastyBird\Module\Devices\Documents\Actions\Properties;

use FastyBird\Library\Application\ObjectMapper as ApplicationObjectMapper;
use FastyBird\Library\Exchange\Documents\Mapping as EXCHANGE;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Documents\Mapping as DOC;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Exceptions;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_merge;
use function sprintf;

/**
 * Connector property action document
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
#[DOC\Document]
#[EXCHANGE\RoutingMap([
	Devices\Constants::MESSAGE_BUS_CONNECTOR_PROPERTY_ACTION_ROUTING_KEY,
])]
final class Connector implements MetadataDocuments\Document
{

	public function __construct(
		#[ObjectMapper\Rules\BackedEnumValue(class: MetadataTypes\PropertyAction::class)]
		private readonly MetadataTypes\PropertyAction $action,
		#[ApplicationObjectMapper\Rules\UuidValue()]
		private readonly Uuid\UuidInterface $connector,
		#[ApplicationObjectMapper\Rules\UuidValue()]
		private readonly Uuid\UuidInterface $property,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(class: Values::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly Values|null $set = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(class: Values::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly Values|null $write = null,
	)
	{
	}

	public function getAction(): MetadataTypes\PropertyAction
	{
		return $this->action;
	}

	public function getConnector(): Uuid\UuidInterface
	{
		return $this->connector;
	}

	public function getProperty(): Uuid\UuidInterface
	{
		return $this->property;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getSet(): Values|null
	{
		if ($this->getAction() !== MetadataTypes\PropertyAction::SET) {
			throw new Exceptions\InvalidState(
				sprintf('Write values are available only for action: %s', MetadataTypes\PropertyAction::SET->value),
			);
		}

		return $this->set;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getWrite(): Values|null
	{
		if ($this->getAction() !== MetadataTypes\PropertyAction::SET) {
			throw new Exceptions\InvalidState(
				sprintf('Write values are available only for action: %s', MetadataTypes\PropertyAction::SET->value),
			);
		}

		return $this->write;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function toArray(): array
	{
		$data = [
			'action' => $this->getAction()->value,
			'connector' => $this->getConnector()->toString(),
			'property' => $this->getProperty()->toString(),
		];

		if ($this->getAction() === MetadataTypes\PropertyAction::SET) {
			if ($this->getSet() !== null) {
				$data = array_merge($data, [
					'set' => $this->getSet()->toArray(),
				]);
			}

			if ($this->getWrite() !== null) {
				$data = array_merge($data, [
					'write' => $this->getWrite()->toArray(),
				]);
			}
		}

		return $data;
	}

}
