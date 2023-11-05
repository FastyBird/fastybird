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
 * @date           02.11.18
 */

namespace FastyBird\Module\Devices\Entities\Devices\Properties;

use Doctrine\Common;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Module\Devices\Entities;
use IPub\DoctrineCrud\Mapping\Annotation as IPubDoctrine;
use Nette\Utils;
use Ramsey\Uuid;
use function array_merge;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="fb_devices_module_devices_properties",
 *     options={
 *       "collate"="utf8mb4_general_ci",
 *       "charset"="utf8mb4",
 *       "comment"="Devices properties"
 *     },
 *     uniqueConstraints={
 *       @ORM\UniqueConstraint(name="property_identifier_unique", columns={"property_identifier", "device_id"})
 *     },
 *     indexes={
 *       @ORM\Index(name="property_identifier_idx", columns={"property_identifier"}),
 *       @ORM\Index(name="property_settable_idx", columns={"property_settable"}),
 *       @ORM\Index(name="property_queryable_idx", columns={"property_queryable"})
 *     }
 * )
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="property_type", type="string", length=20)
 * @ORM\DiscriminatorMap({
 *    "variable" = "FastyBird\Module\Devices\Entities\Devices\Properties\Variable",
 *    "dynamic"  = "FastyBird\Module\Devices\Entities\Devices\Properties\Dynamic",
 *    "mapped"   = "FastyBird\Module\Devices\Entities\Devices\Properties\Mapped"
 * })
 * @ORM\MappedSuperclass
 */
abstract class Property extends Entities\Property
{

	/**
	 * @IPubDoctrine\Crud(is="required")
	 * @ORM\ManyToOne(targetEntity="FastyBird\Module\Devices\Entities\Devices\Device", inversedBy="properties")
	 * @ORM\JoinColumn(name="device_id", referencedColumnName="device_id", onDelete="CASCADE", nullable=false)
	 */
	protected Entities\Devices\Device $device;

	/**
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\ManyToOne(targetEntity="FastyBird\Module\Devices\Entities\Devices\Properties\Property", inversedBy="children")
	 * @ORM\JoinColumn(name="parent_id", referencedColumnName="property_id", nullable=true, onDelete="CASCADE")
	 */
	protected self|null $parent = null;

	/**
	 * @var Common\Collections\Collection<int, Property>
	 *
	 * @ORM\OneToMany(targetEntity="FastyBird\Module\Devices\Entities\Devices\Properties\Property", mappedBy="parent", cascade={"remove"}, orphanRemoval=true)
	 */
	protected Common\Collections\Collection $children;

	public function __construct(
		Entities\Devices\Device $device,
		string $identifier,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($identifier, $id);

		$this->device = $device;

		$device->addProperty($this);

		$this->children = new Common\Collections\ArrayCollection();
	}

	public function getParent(): self|null
	{
		return $this->parent;
	}

	public function setParent(self $property): void
	{
		$this->parent = $property;
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

		// Process all passed entities...
		foreach ($children as $entity) {
			// ...and assign them to collection
			$this->addChild($entity);
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

	public function getDevice(): Entities\Devices\Device
	{
		return $this->device;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'device' => $this->getDevice()->getId()->toString(),
			'connector' => $this->getDevice()->getConnector()->getId()->toString(),

			'owner' => $this->getDevice()->getOwnerId(),
		]);
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Utils\JsonException
	 */
	public function __toString(): string
	{
		return Utils\Json::encode($this->toArray());
	}

}
