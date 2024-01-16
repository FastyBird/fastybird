<?php declare(strict_types = 1);

/**
 * Mapped.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           02.04.22
 */

namespace FastyBird\Module\Devices\Entities\Devices\Properties;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Metadata\Utilities as MetadataUtilities;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Utilities;
use Ramsey\Uuid;
use function array_merge;
use function assert;
use function sprintf;
use function strval;

/**
 * @ORM\Entity
 */
class Mapped extends Property
{

	public function __construct(
		Entities\Devices\Device $device,
		Entities\Devices\Properties\Property $parent,
		string $identifier,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($device, $identifier, $id);

		$this->parent = $parent;
	}

	public function getType(): MetadataTypes\PropertyType
	{
		return MetadataTypes\PropertyType::get(MetadataTypes\PropertyType::TYPE_MAPPED);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getParent(): Dynamic|Variable
	{
		if ($this->parent === null) {
			throw new Exceptions\InvalidState('Mapped property can\'t be without parent property');
		}

		assert($this->parent instanceof Dynamic || $this->parent instanceof Variable);

		return $this->parent;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getChildren(): array
	{
		throw new Exceptions\InvalidState(
			sprintf('Reading children is not allowed for property type: %s', strval($this->getType()->getValue())),
		);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function setChildren(array $children): void
	{
		throw new Exceptions\InvalidState(
			sprintf('Assigning children is not allowed for property type: %s', strval($this->getType()->getValue())),
		);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function addChild(Property $child): void
	{
		throw new Exceptions\InvalidState(
			sprintf('Adding child is not allowed for property type: %s', strval($this->getType()->getValue())),
		);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function removeChild(Property $child): void
	{
		throw new Exceptions\InvalidState(
			sprintf('Removing child is not allowed for property type: %s', strval($this->getType()->getValue())),
		);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function setSettable(bool $settable): void
	{
		if (!$this->getParent() instanceof Dynamic) {
			throw new Exceptions\InvalidState('Settable flag is allowed only for dynamic parent properties');
		}

		parent::setSettable($settable);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function isSettable(): bool
	{
		if (!$this->getParent() instanceof Dynamic) {
			throw new Exceptions\InvalidState('Settable flag is allowed only for dynamic parent properties');
		}

		return parent::isSettable();
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function setQueryable(bool $queryable): void
	{
		if (!$this->getParent() instanceof Dynamic) {
			throw new Exceptions\InvalidState('Queryable flag is allowed only for dynamic parent properties');
		}

		parent::setQueryable($queryable);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function isQueryable(): bool
	{
		if (!$this->getParent() instanceof Dynamic) {
			throw new Exceptions\InvalidState('Queryable flag is allowed only for dynamic parent properties');
		}

		return parent::isQueryable();
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	public function getDefault(): bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null
	{
		if (!$this->getParent() instanceof Variable) {
			throw new Exceptions\InvalidState('Reading default value is allowed only for variable parent properties');
		}

		if (!Utilities\Value::compareDataTypes($this->getDataType(), $this->getParent()->getDataType())) {
			return null;
		}

		try {
			return MetadataUtilities\Value::normalizeValue(
				MetadataUtilities\Value::transformDataType(
					MetadataUtilities\Value::flattenValue($this->getParent()->getDefault()),
					$this->getDataType(),
				),
				$this->getDataType(),
				$this->getFormat(),
			);
		} catch (MetadataExceptions\InvalidValue) {
			return null;
		}
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function setDefault(
		bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $default,
	): void
	{
		if (!$this->getParent() instanceof Variable) {
			throw new Exceptions\InvalidState('Setting default value is allowed only for variable parent properties');
		}

		throw new Exceptions\InvalidState('Default value setter is allowed only for parent');
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	public function getValue(): bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null
	{
		if (!$this->getParent() instanceof Variable) {
			throw new Exceptions\InvalidState('Reading value is allowed only for variable parent properties');
		}

		if (!Utilities\Value::compareDataTypes($this->getDataType(), $this->getParent()->getDataType())) {
			return null;
		}

		try {
			return MetadataUtilities\Value::normalizeValue(
				MetadataUtilities\Value::transformDataType(
					MetadataUtilities\Value::flattenValue($this->getParent()->getValue()),
					$this->getDataType(),
				),
				$this->getDataType(),
				$this->getFormat(),
			);
		} catch (MetadataExceptions\InvalidValue) {
			return null;
		}
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function setValue(
		bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $value,
	): void
	{
		if (!$this->getParent() instanceof Variable) {
			throw new Exceptions\InvalidState('Setting value is allowed only for variable parent properties');
		}

		throw new Exceptions\InvalidState('Value setter is allowed only for parent');
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function toArray(): array
	{
		if ($this->getParent() instanceof Entities\Devices\Properties\Variable) {
			return array_merge(parent::toArray(), [
				'parent' => $this->getParent()->getId()->toString(),

				'default' => MetadataUtilities\Value::flattenValue($this->getDefault()),
				'value' => MetadataUtilities\Value::flattenValue($this->getValue()),
			]);
		}

		return array_merge(parent::toArray(), [
			'parent' => $this->getParent()->getId()->toString(),

			'settable' => $this->isSettable(),
			'queryable' => $this->isQueryable(),
		]);
	}

}
