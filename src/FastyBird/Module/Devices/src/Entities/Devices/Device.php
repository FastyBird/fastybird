<?php declare(strict_types = 1);

/**
 * Device.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           29.01.17
 */

namespace FastyBird\Module\Devices\Entities\Devices;

use Consistence\Doctrine\Enum\EnumAnnotation as Enum;
use Doctrine\Common;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities;
use FastyBird\SimpleAuth\Entities as SimpleAuthEntities;
use IPub\DoctrineCrud\Mapping\Annotation as IPubDoctrine;
use IPub\DoctrineDynamicDiscriminatorMap\Entities as DoctrineDynamicDiscriminatorMapEntities;
use IPub\DoctrineTimestampable;
use Nette\Utils;
use Ramsey\Uuid;
use function array_map;
use function strval;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="fb_devices_module_devices",
 *     options={
 *       "collate"="utf8mb4_general_ci",
 *       "charset"="utf8mb4",
 *       "comment"="Devices"
 *     },
 *     uniqueConstraints={
 *       @ORM\UniqueConstraint(name="device_identifier_unique", columns={"device_identifier", "connector_id"})
 *     },
 *     indexes={
 *       @ORM\Index(name="device_identifier_idx", columns={"device_identifier"}),
 *       @ORM\Index(name="device_name_idx", columns={"device_name"})
 *     }
 * )
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="device_type", type="string", length=40)
 * @ORM\DiscriminatorMap({
 *    "device" = "FastyBird\Module\Devices\Entities\Devices\Device"
 * })
 * @ORM\MappedSuperclass
 */
abstract class Device implements Entities\Entity,
	Entities\EntityParams,
	SimpleAuthEntities\Owner,
	DoctrineTimestampable\Entities\IEntityCreated, DoctrineTimestampable\Entities\IEntityUpdated,
	DoctrineDynamicDiscriminatorMapEntities\IDiscriminatorProvider
{

	use Entities\TEntity;
	use Entities\TEntityParams;
	use SimpleAuthEntities\TOwner;
	use DoctrineTimestampable\Entities\TEntityCreated;
	use DoctrineTimestampable\Entities\TEntityUpdated;

	/**
	 * @ORM\Id
	 * @ORM\Column(type="uuid_binary", name="device_id")
	 * @ORM\CustomIdGenerator(class="Ramsey\Uuid\Doctrine\UuidGenerator")
	 */
	protected Uuid\UuidInterface $id;

	/**
	 * @var MetadataTypes\DeviceCategory
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
	 *
	 * @Enum(class=MetadataTypes\DeviceCategory::class)
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\Column(type="string_enum", name="device_category", length=100, nullable=true, options={"default": "generic"})
	 */
	protected $category;

	/**
	 * @IPubDoctrine\Crud(is="required")
	 * @ORM\Column(type="string", name="device_identifier", length=50, nullable=false)
	 */
	protected string $identifier;

	/**
	 * @var Common\Collections\Collection<int, Device>
	 *
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\ManyToMany(targetEntity="FastyBird\Module\Devices\Entities\Devices\Device", inversedBy="children")
	 * @ORM\JoinTable(
	 *     name="fb_devices_module_devices_children",
	 *     joinColumns={@ORM\JoinColumn(name="child_device", referencedColumnName="device_id", onDelete="CASCADE")},
	 *     inverseJoinColumns={@ORM\JoinColumn(name="parent_device", referencedColumnName="device_id", onDelete="CASCADE")}
	 * )
	 */
	protected Common\Collections\Collection $parents;

	/**
	 * @var Common\Collections\Collection<int, Device>
	 *
	 * @ORM\ManyToMany(targetEntity="FastyBird\Module\Devices\Entities\Devices\Device", mappedBy="parents", cascade={"remove"}, orphanRemoval=true)
	 */
	protected Common\Collections\Collection $children;

	/**
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\Column(type="string", name="device_name", nullable=true, options={"default": null})
	 */
	protected string|null $name = null;

	/**
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\Column(type="text", name="device_comment", nullable=true, options={"default": null})
	 */
	protected string|null $comment = null;

	/**
	 * @var Common\Collections\Collection<int, Entities\Channels\Channel>
	 *
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\OneToMany(targetEntity="FastyBird\Module\Devices\Entities\Channels\Channel", mappedBy="device", cascade={"persist", "remove"}, orphanRemoval=true)
	 */
	protected Common\Collections\Collection $channels;

	/**
	 * @var Common\Collections\Collection<int, Entities\Devices\Controls\Control>
	 *
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\OneToMany(targetEntity="FastyBird\Module\Devices\Entities\Devices\Controls\Control", mappedBy="device", cascade={"persist", "remove"}, orphanRemoval=true)
	 */
	protected Common\Collections\Collection $controls;

	/**
	 * @var Common\Collections\Collection<int, Entities\Devices\Properties\Property>
	 *
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\OneToMany(targetEntity="FastyBird\Module\Devices\Entities\Devices\Properties\Property", mappedBy="device", cascade={"persist", "remove"}, orphanRemoval=true)
	 */
	protected Common\Collections\Collection $properties;

	/**
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\ManyToOne(targetEntity="FastyBird\Module\Devices\Entities\Connectors\Connector", inversedBy="devices")
	 * @ORM\JoinColumn(name="connector_id", referencedColumnName="connector_id", onDelete="CASCADE", nullable=false)
	 */
	protected Entities\Connectors\Connector $connector;

	public function __construct(
		string $identifier,
		Entities\Connectors\Connector $connector,
		string|null $name = null,
		Uuid\UuidInterface|null $id = null,
	)
	{
		// @phpstan-ignore-next-line
		$this->id = $id ?? Uuid\Uuid::uuid4();

		$this->identifier = $identifier;
		$this->name = $name;

		$this->category = MetadataTypes\DeviceCategory::get(MetadataTypes\DeviceCategory::CATEGORY_GENERIC);

		$this->connector = $connector;

		$this->parents = new Common\Collections\ArrayCollection();
		$this->children = new Common\Collections\ArrayCollection();
		$this->channels = new Common\Collections\ArrayCollection();
		$this->controls = new Common\Collections\ArrayCollection();
		$this->properties = new Common\Collections\ArrayCollection();
	}

	abstract public function getType(): string;

	public function getCategory(): MetadataTypes\DeviceCategory
	{
		return $this->category;
	}

	public function setCategory(MetadataTypes\DeviceCategory $category): void
	{
		$this->category = $category;
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	public function getName(): string|null
	{
		return $this->name;
	}

	public function setName(string|null $name): void
	{
		$this->name = $name;
	}

	public function getComment(): string|null
	{
		return $this->comment;
	}

	public function setComment(string|null $comment = null): void
	{
		$this->comment = $comment;
	}

	public function getConnector(): Entities\Connectors\Connector
	{
		return $this->connector;
	}

	public function setConnector(Entities\Connectors\Connector $connector): void
	{
		$this->connector = $connector;
	}

	public function getOwnerId(): string|null
	{
		return $this->owner !== null ? strval($this->owner) : null;
	}

	/**
	 * @return array<Device>
	 */
	public function getParents(): array
	{
		return $this->parents->toArray();
	}

	/**
	 * @param array<Device> $parents
	 */
	public function setParents(array $parents): void
	{
		$this->parents = new Common\Collections\ArrayCollection();

		// Process all passed entities...
		foreach ($parents as $entity) {
			// ...and assign them to collection
			$this->addParent($entity);
		}
	}

	public function addParent(self $device): void
	{
		// Check if collection does not contain inserting entity
		if (!$this->parents->contains($device)) {
			// ...and assign it to collection
			$this->parents->add($device);
		}

		$device->addChild($this);
	}

	public function hasParent(self $device): bool
	{
		return $this->parents->contains($device);
	}

	/**
	 * @return array<Device>
	 */
	public function getChildren(): array
	{
		return $this->children->toArray();
	}

	/**
	 * @param array<Device> $children
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

	/**
	 * @return array<Entities\Channels\Channel>
	 */
	public function getChannels(): array
	{
		return $this->channels->toArray();
	}

	/**
	 * @param array<Entities\Channels\Channel> $channels
	 */
	public function setChannels(array $channels = []): void
	{
		$this->channels = new Common\Collections\ArrayCollection();

		// Process all passed entities...
		foreach ($channels as $entity) {
			// ...and assign them to collection
			$this->addChannel($entity);
		}
	}

	public function addChannel(Entities\Channels\Channel $channel): void
	{
		// Check if collection does not contain inserting entity
		if (!$this->channels->contains($channel)) {
			// ...and assign it to collection
			$this->channels->add($channel);
		}
	}

	/**
	 * @return array<Entities\Devices\Controls\Control>
	 */
	public function getControls(): array
	{
		return $this->controls->toArray();
	}

	/**
	 * @param array<Entities\Devices\Controls\Control> $controls
	 */
	public function setControls(array $controls = []): void
	{
		$this->controls = new Common\Collections\ArrayCollection();

		// Process all passed entities...
		foreach ($controls as $entity) {
			// ...and assign them to collection
			$this->addControl($entity);
		}
	}

	public function addControl(Entities\Devices\Controls\Control $control): void
	{
		// Check if collection does not contain inserting entity
		if (!$this->controls->contains($control)) {
			// ...and assign it to collection
			$this->controls->add($control);
		}
	}

	/**
	 * @return array<Entities\Devices\Properties\Property>
	 */
	public function getProperties(): array
	{
		return $this->properties->toArray();
	}

	/**
	 * @param array<Entities\Devices\Properties\Property> $properties
	 */
	public function setProperties(array $properties = []): void
	{
		$this->properties = new Common\Collections\ArrayCollection();

		// Process all passed entities...
		foreach ($properties as $entity) {
			// ...and assign them to collection
			$this->addProperty($entity);
		}
	}

	public function addProperty(Entities\Devices\Properties\Property $property): void
	{
		// Check if collection does not contain inserting entity
		if (!$this->properties->contains($property)) {
			// ...and assign it to collection
			$this->properties->add($property);
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getId()->toString(),
			'type' => $this->getType(),
			'category' => $this->getCategory()->getValue(),
			'identifier' => $this->getIdentifier(),
			'name' => $this->getName(),
			'comment' => $this->getComment(),

			'connector' => $this->getConnector()->getId()->toString(),

			'parents' => array_map(
				static fn (self $parent): string => $parent->getId()->toString(),
				$this->getParents(),
			),
			'children' => array_map(
				static fn (self $child): string => $child->getId()->toString(),
				$this->getChildren(),
			),

			'properties' => array_map(
				static fn (Entities\Devices\Properties\Property $property): string => $property->getId()->toString(),
				$this->getProperties(),
			),
			'controls' => array_map(
				static fn (Entities\Devices\Controls\Control $control): string => $control->getId()->toString(),
				$this->getControls(),
			),
			'channels' => array_map(
				static fn (Entities\Channels\Channel $channel): string => $channel->getId()->toString(),
				$this->getChannels(),
			),

			'owner' => $this->getOwnerId(),
		];
	}

	public function getSource(): MetadataTypes\ModuleSource|MetadataTypes\PluginSource|MetadataTypes\ConnectorSource
	{
		return MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES);
	}

	/**
	 * @throws Utils\JsonException
	 */
	public function __toString(): string
	{
		return Utils\Json::encode($this->toArray());
	}

}
