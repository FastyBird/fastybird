<?php declare(strict_types = 1);

/**
 * Channel.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           28.07.18
 */

namespace FastyBird\Module\Devices\Entities\Channels;

use Consistence\Doctrine\Enum\EnumAnnotation as Enum;
use Doctrine\Common;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities;
use IPub\DoctrineCrud\Mapping\Annotation as IPubDoctrine;
use IPub\DoctrineDynamicDiscriminatorMap\Entities as DoctrineDynamicDiscriminatorMapEntities;
use IPub\DoctrineTimestampable;
use Nette\Utils;
use Ramsey\Uuid;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="fb_devices_module_channels",
 *     options={
 *       "collate"="utf8mb4_general_ci",
 *       "charset"="utf8mb4",
 *       "comment"="Device channels"
 *     },
 *     uniqueConstraints={
 *       @ORM\UniqueConstraint(name="channel_identifier_unique", columns={"channel_identifier", "device_id"})
 *     },
 *     indexes={
 *       @ORM\Index(name="channel_identifier_idx", columns={"channel_identifier"})
 *     }
 * )
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="channel_type", type="string", length=40)
 * @ORM\DiscriminatorMap({
 *    "channel" = "FastyBird\Module\Devices\Entities\Channels\Channel"
 * })
 * @ORM\MappedSuperclass
 */
class Channel implements Entities\Entity,
	Entities\EntityParams,
	DoctrineTimestampable\Entities\IEntityCreated, DoctrineTimestampable\Entities\IEntityUpdated,
	DoctrineDynamicDiscriminatorMapEntities\IDiscriminatorProvider
{

	use Entities\TEntity;
	use Entities\TEntityParams;
	use DoctrineTimestampable\Entities\TEntityCreated;
	use DoctrineTimestampable\Entities\TEntityUpdated;

	public const CHANNEL_TYPE = 'general';

	/**
	 * @ORM\Id
	 * @ORM\Column(type="uuid_binary", name="channel_id")
	 * @ORM\CustomIdGenerator(class="Ramsey\Uuid\Doctrine\UuidGenerator")
	 */
	protected Uuid\UuidInterface $id;

	/**
	 * @var MetadataTypes\ChannelCategory
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
	 *
	 * @Enum(class=MetadataTypes\ChannelCategory::class)
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\Column(type="string_enum", name="channel_category", length=100, nullable=true, options={"default": "generic"})
	 */
	protected $category;

	/**
	 * @IPubDoctrine\Crud(is="required")
	 * @ORM\Column(type="string", name="channel_identifier", length=50, nullable=false)
	 */
	protected string $identifier;

	/**
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\Column(type="string", name="channel_name", nullable=true, options={"default": null})
	 */
	protected string|null $name;

	/**
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\Column(type="text", name="channel_comment", nullable=true, options={"default": null})
	 */
	protected string|null $comment = null;

	/**
	 * @var Common\Collections\Collection<int, Entities\Channels\Properties\Property>
	 *
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\OneToMany(targetEntity="FastyBird\Module\Devices\Entities\Channels\Properties\Property", mappedBy="channel", cascade={"persist", "remove"}, orphanRemoval=true)
	 */
	protected Common\Collections\Collection $properties;

	/**
	 * @var Common\Collections\Collection<int, Entities\Channels\Controls\Control>
	 *
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\OneToMany(targetEntity="FastyBird\Module\Devices\Entities\Channels\Controls\Control", mappedBy="channel", cascade={"persist", "remove"}, orphanRemoval=true)
	 */
	protected Common\Collections\Collection $controls;

	/**
	 * @IPubDoctrine\Crud(is="required")
	 * @ORM\ManyToOne(targetEntity="FastyBird\Module\Devices\Entities\Devices\Device", inversedBy="channels")
	 * @ORM\JoinColumn(name="device_id", referencedColumnName="device_id", onDelete="CASCADE", nullable=false)
	 */
	protected Entities\Devices\Device $device;

	public function __construct(
		Entities\Devices\Device $device,
		string $identifier,
		string|null $name = null,
		Uuid\UuidInterface|null $id = null,
	)
	{
		// @phpstan-ignore-next-line
		$this->id = $id ?? Uuid\Uuid::uuid4();

		$this->device = $device;
		$this->identifier = $identifier;

		$this->name = $name;

		$this->category = MetadataTypes\ChannelCategory::get(MetadataTypes\ChannelCategory::CATEGORY_GENERIC);

		$this->properties = new Common\Collections\ArrayCollection();
		$this->controls = new Common\Collections\ArrayCollection();
	}

	public function getType(): string
	{
		return self::CHANNEL_TYPE;
	}

	public function getCategory(): MetadataTypes\ChannelCategory
	{
		return $this->category;
	}

	public function setCategory(MetadataTypes\ChannelCategory $category): void
	{
		$this->category = $category;
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	public function setIdentifier(string $identifier): void
	{
		$this->identifier = $identifier;
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

	public function getDevice(): Entities\Devices\Device
	{
		return $this->device;
	}

	/**
	 * @return array<Entities\Channels\Properties\Property>
	 */
	public function getProperties(): array
	{
		return $this->properties->toArray();
	}

	/**
	 * @param array<Entities\Channels\Properties\Property> $properties
	 */
	public function setProperties(array $properties = []): void
	{
		$this->properties = new Common\Collections\ArrayCollection();

		// Process all passed entities...
		foreach ($properties as $entity) {
			// ...and assign them to collection
			$this->properties->add($entity);
		}
	}

	public function addProperty(Entities\Channels\Properties\Property $property): void
	{
		// Check if collection does not contain inserting entity
		if (!$this->properties->contains($property)) {
			// ...and assign it to collection
			$this->properties->add($property);
		}
	}

	/**
	 * @return array<Entities\Channels\Controls\Control>
	 */
	public function getControls(): array
	{
		return $this->controls->toArray();
	}

	/**
	 * @param array<Entities\Channels\Controls\Control> $controls
	 */
	public function setControls(array $controls = []): void
	{
		$this->controls = new Common\Collections\ArrayCollection();

		// Process all passed entities...
		foreach ($controls as $entity) {
			// ...and assign them to collection
			$this->controls->add($entity);
		}
	}

	public function addControl(Entities\Channels\Controls\Control $control): void
	{
		// Check if collection does not contain inserting entity
		if (!$this->controls->contains($control)) {
			// ...and assign it to collection
			$this->controls->add($control);
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getPlainId(),
			'type' => $this->getType(),
			'category' => $this->getCategory()->getValue(),
			'identifier' => $this->getIdentifier(),
			'name' => $this->getName(),
			'comment' => $this->getComment(),

			'device' => $this->getDevice()->getPlainId(),

			'owner' => $this->getDevice()->getOwnerId(),
		];
	}

	// @phpstan-ignore-next-line
	public function getSource(): MetadataTypes\ModuleSource|MetadataTypes\PluginSource|MetadataTypes\ConnectorSource
	{
		return MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES);
	}

	public function getDiscriminatorName(): string
	{
		return 'channel';
	}

	/**
	 * @throws Utils\JsonException
	 */
	public function __toString(): string
	{
		return Utils\Json::encode($this->toArray());
	}

}
