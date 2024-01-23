<?php declare(strict_types = 1);

/**
 * ActionDeviceProperty.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           01.06.22
 */

namespace FastyBird\Library\Metadata\Documents\Actions;

use FastyBird\Library\Application\ObjectMapper as ApplicationObjectMapper;
use FastyBird\Library\Metadata\Documents;
use FastyBird\Library\Metadata\Exceptions;
use FastyBird\Library\Metadata\Types;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_merge;
use function sprintf;

/**
 * Device property action document
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ActionDeviceProperty implements Documents\Document
{

	public function __construct(
		#[ApplicationObjectMapper\Rules\ConsistenceEnumValue(class: Types\PropertyAction::class)]
		private readonly Types\PropertyAction $action,
		#[ApplicationObjectMapper\Rules\UuidValue()]
		private readonly Uuid\UuidInterface $device,
		#[ApplicationObjectMapper\Rules\UuidValue()]
		private readonly Uuid\UuidInterface $property,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(class: PropertyValues::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly PropertyValues|null $set = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(class: PropertyValues::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly PropertyValues|null $write = null,
	)
	{
	}

	public function getAction(): Types\PropertyAction
	{
		return $this->action;
	}

	public function getDevice(): Uuid\UuidInterface
	{
		return $this->device;
	}

	public function getProperty(): Uuid\UuidInterface
	{
		return $this->property;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getSet(): PropertyValues|null
	{
		if (!$this->getAction()->equalsValue(Types\PropertyAction::SET)) {
			throw new Exceptions\InvalidState(
				sprintf('Write values are available only for action: %s', Types\PropertyAction::SET),
			);
		}

		return $this->set;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getWrite(): PropertyValues|null
	{
		if (!$this->getAction()->equalsValue(Types\PropertyAction::SET)) {
			throw new Exceptions\InvalidState(
				sprintf('Write values are available only for action: %s', Types\PropertyAction::SET),
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
			'action' => $this->getAction()->getValue(),
			'device' => $this->getDevice()->toString(),
			'property' => $this->getProperty()->toString(),
		];

		if ($this->getAction()->equalsValue(Types\PropertyAction::SET)) {
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
