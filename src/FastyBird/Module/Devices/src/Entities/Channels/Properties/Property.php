<?php declare(strict_types = 1);

/**
 * Property.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           26.10.18
 */

namespace FastyBird\Module\Devices\Entities\Channels\Properties;

use DateTimeInterface;
use Doctrine\Common;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Metadata\ValueObjects as MetadataValueObjects;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Exceptions;
use IPub\DoctrineCrud\Mapping\Annotation as IPubDoctrine;
use Ramsey\Uuid;
use function array_merge;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="fb_devices_module_channels_properties",
 *     options={
 *       "collate"="utf8mb4_general_ci",
 *       "charset"="utf8mb4",
 *       "comment"="Device channels properties"
 *     },
 *     uniqueConstraints={
 *       @ORM\UniqueConstraint(name="property_identifier_unique", columns={"property_identifier", "channel_id"})
 *     },
 *     indexes={
 *       @ORM\Index(name="property_identifier_idx", columns={"property_identifier"}),
 *       @ORM\Index(name="property_settable_idx", columns={"property_settable"}),
 *       @ORM\Index(name="property_queryable_idx", columns={"property_queryable"})
 *     }
 * )
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="property_type", type="string", length=40)
 * @ORM\DiscriminatorMap({
 *    "variable" = "FastyBird\Module\Devices\Entities\Channels\Properties\Variable",
 *    "dynamic"  = "FastyBird\Module\Devices\Entities\Channels\Properties\Dynamic",
 *    "mapped"   = "FastyBird\Module\Devices\Entities\Channels\Properties\Mapped"
 * })
 * @ORM\MappedSuperclass
 */
abstract class Property extends Entities\Property
{

	/**
	 * @IPubDoctrine\Crud(is="required")
	 * @ORM\ManyToOne(targetEntity="FastyBird\Module\Devices\Entities\Channels\Channel", inversedBy="properties")
	 * @ORM\JoinColumn(name="channel_id", referencedColumnName="channel_id", onDelete="CASCADE", nullable=false)
	 */
	protected Entities\Channels\Channel $channel;

	/**
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\ManyToOne(targetEntity="FastyBird\Module\Devices\Entities\Channels\Properties\Property", inversedBy="children")
	 * @ORM\JoinColumn(name="parent_id", referencedColumnName="property_id", nullable=true, onDelete="CASCADE")
	 */
	protected self|null $parent = null;

	/**
	 * @var Common\Collections\Collection<int, Property>
	 *
	 * @ORM\OneToMany(targetEntity="FastyBird\Module\Devices\Entities\Channels\Properties\Property", mappedBy="parent", cascade={"remove"}, orphanRemoval=true)
	 */
	protected Common\Collections\Collection $children;

	public function __construct(
		Entities\Channels\Channel $channel,
		string $identifier,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($identifier, $id);

		$this->channel = $channel;

		$channel->addProperty($this);

		$this->children = new Common\Collections\ArrayCollection();
	}

	public function getParent(): self|null
	{
		return $this->parent;
	}

	public function setParent(self $device): void
	{
		$this->parent = $device;
	}

	public function removeParent(): void
	{
		$this->parent = null;
	}

	/**
	 * @return array<Property>
	 */
	public function getChildren(): array
	{
		return $this->children->toArray();
	}

	/**
	 * @param array<Property> $children
	 */
	public function setChildren(array $children): void
	{
		$this->children = new Common\Collections\ArrayCollection();

		foreach ($children as $entity) {
			// ...and assign them to collection
			$this->children->add($entity);
		}
	}

	public function addChild(self $child): void
	{
		// Check if collection does not contain inserting entity
		if (!$this->children->contains($child)) {
			// ...and assign it to collection
			$this->children->add($child);
		}
	}

	public function removeChild(self $child): void
	{
		// Check if collection contain removing entity...
		if ($this->children->contains($child)) {
			// ...and remove it from collection
			$this->children->removeElement($child);
		}
	}

	public function getChannel(): Entities\Channels\Channel
	{
		return $this->channel;
	}

	public function isSettable(): bool
	{
		if (
			$this->getParent() !== null && !$this->getType()->equalsValue(MetadataTypes\PropertyType::TYPE_MAPPED)
		) {
			return $this->getParent()->isSettable();
		}

		return parent::isSettable();
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 */
	public function setSettable(bool $settable): void
	{
		if (
			$this->getParent() !== null && !$this->getType()->equalsValue(MetadataTypes\PropertyType::TYPE_MAPPED)
		) {
			throw new Exceptions\InvalidState('Settable setter is allowed only for parent');
		}

		parent::setSettable($settable);
	}

	public function isQueryable(): bool
	{
		if (
			$this->getParent() !== null && !$this->getType()->equalsValue(MetadataTypes\PropertyType::TYPE_MAPPED)
		) {
			return $this->getParent()->isQueryable();
		}

		return parent::isQueryable();
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 */
	public function setQueryable(bool $queryable): void
	{
		if (
			$this->getParent() !== null && !$this->getType()->equalsValue(MetadataTypes\PropertyType::TYPE_MAPPED)
		) {
			throw new Exceptions\InvalidState('Queryable setter is allowed only for parent');
		}

		parent::setQueryable($queryable);
	}

	public function getDataType(): MetadataTypes\DataType
	{
		if (
			$this->getParent() !== null && !$this->getType()->equalsValue(MetadataTypes\PropertyType::TYPE_MAPPED)
		) {
			return $this->getParent()->getDataType();
		}

		return parent::getDataType();
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function setDataType(MetadataTypes\DataType $dataType): void
	{
		if (
			$this->getParent() !== null && !$this->getType()->equalsValue(MetadataTypes\PropertyType::TYPE_MAPPED)
		) {
			throw new Exceptions\InvalidState('Data type setter is allowed only for parent');
		}

		parent::setDataType($dataType);
	}

	public function getUnit(): string|null
	{
		if (
			$this->getParent() !== null && !$this->getType()->equalsValue(MetadataTypes\PropertyType::TYPE_MAPPED)
		) {
			return $this->getParent()->getUnit();
		}

		return parent::getUnit();
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function setUnit(string|null $unit): void
	{
		if (
			$this->getParent() !== null && !$this->getType()->equalsValue(MetadataTypes\PropertyType::TYPE_MAPPED)
		) {
			throw new Exceptions\InvalidState('Value unit setter is allowed only for parent');
		}

		parent::setUnit($unit);
	}

	public function getFormat(): MetadataValueObjects\StringEnumFormat|MetadataValueObjects\NumberRangeFormat|MetadataValueObjects\CombinedEnumFormat|MetadataValueObjects\EquationFormat|null
	{
		if (
			$this->getParent() !== null && !$this->getType()->equalsValue(MetadataTypes\PropertyType::TYPE_MAPPED)
		) {
			return $this->getParent()->getFormat();
		}

		return parent::getFormat();
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 */
	public function setFormat(array|string|null $format): void
	{
		if (
			$this->getParent() !== null && !$this->getType()->equalsValue(MetadataTypes\PropertyType::TYPE_MAPPED)
		) {
			throw new Exceptions\InvalidState('Value format setter is allowed only for parent');
		}

		parent::setFormat($format);
	}

	public function getInvalid(): float|int|string|null
	{
		if (
			$this->getParent() !== null && !$this->getType()->equalsValue(MetadataTypes\PropertyType::TYPE_MAPPED)
		) {
			return $this->getParent()->getInvalid();
		}

		return parent::getInvalid();
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function setInvalid(string|null $invalid): void
	{
		if (
			$this->getParent() !== null && !$this->getType()->equalsValue(MetadataTypes\PropertyType::TYPE_MAPPED)
		) {
			throw new Exceptions\InvalidState('Invalid value setter is allowed only for parent');
		}

		parent::setInvalid($invalid);
	}

	public function getScale(): int|null
	{
		if (
			$this->getParent() !== null && !$this->getType()->equalsValue(MetadataTypes\PropertyType::TYPE_MAPPED)
		) {
			return $this->getParent()->getScale();
		}

		return parent::getScale();
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function setScale(int|null $scale): void
	{
		if (
			$this->getParent() !== null && !$this->getType()->equalsValue(MetadataTypes\PropertyType::TYPE_MAPPED)
		) {
			throw new Exceptions\InvalidState('Number of decimals setter is allowed only for parent');
		}

		parent::setScale($scale);
	}

	public function getDefault(): bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null
	{
		if (
			$this->getParent() !== null && !$this->getType()->equalsValue(MetadataTypes\PropertyType::TYPE_MAPPED)
		) {
			return $this->getParent()->getDefault();
		}

		return parent::getDefault();
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function setDefault(string|null $default): void
	{
		if (
			$this->getParent() !== null && !$this->getType()->equalsValue(MetadataTypes\PropertyType::TYPE_MAPPED)
		) {
			throw new Exceptions\InvalidState('Default value setter is allowed only for parent');
		}

		parent::setDefault($default);
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		$children = [];

		foreach ($this->getChildren() as $child) {
			$children[] = $child->getPlainId();
		}

		return array_merge(parent::toArray(), [
			'channel' => $this->getChannel()->getPlainId(),
			'parent' => $this->getParent()?->getPlainId(),
			'children' => $children,

			'owner' => $this->getChannel()->getDevice()->getOwnerId(),
		]);
	}

}
