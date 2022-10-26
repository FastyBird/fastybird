<?php declare(strict_types = 1);

/**
 * Connector.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Entities
 * @since          0.1.0
 *
 * @date           17.01.20
 */

namespace FastyBird\Module\Devices\Entities\Connectors;

use Doctrine\Common;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities;
use FastyBird\SimpleAuth\Entities as SimpleAuthEntities;
use IPub\DoctrineCrud\Mapping\Annotation as IPubDoctrine;
use IPub\DoctrineDynamicDiscriminatorMap\Entities as DoctrineDynamicDiscriminatorMapEntities;
use IPub\DoctrineTimestampable;
use Ramsey\Uuid;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="fb_devices_module_connectors",
 *     options={
 *       "collate"="utf8mb4_general_ci",
 *       "charset"="utf8mb4",
 *       "comment"="Communication connectors"
 *     },
 *     uniqueConstraints={
 *       @ORM\UniqueConstraint(name="connector_identifier_unique", columns={"connector_identifier"})
 *     },
 *     indexes={
 *       @ORM\Index(name="connector_identifier_idx", columns={"connector_identifier"}),
 *       @ORM\Index(name="connector_name_idx", columns={"connector_name"}),
 *       @ORM\Index(name="connector_enabled_idx", columns={"connector_enabled"})
 *     }
 * )
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="connector_type", type="string", length=40)
 * @ORM\DiscriminatorMap({
 *    "connector" = "FastyBird\Module\Devices\Entities\Connectors\Connector"
 * })
 * @ORM\MappedSuperclass
 */
abstract class Connector implements Entities\Entity,
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
	 * @ORM\Column(type="uuid_binary", name="connector_id")
	 * @ORM\CustomIdGenerator(class="Ramsey\Uuid\Doctrine\UuidGenerator")
	 */
	protected Uuid\UuidInterface $id;

	/**
	 * @IPubDoctrine\Crud(is="required")
	 * @ORM\Column(type="string", name="connector_identifier", length=50, nullable=false)
	 */
	protected string $identifier;

	/**
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\Column(type="string", name="connector_name", nullable=true, options={"default": null})
	 */
	protected string|null $name = null;

	/**
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\Column(type="text", name="connector_comment", nullable=true, options={"default": null})
	 */
	protected string|null $comment = null;

	/**
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\Column(type="boolean", name="connector_enabled", length=1, nullable=false, options={"default": true})
	 */
	protected bool $enabled = true;

	/**
	 * @var Common\Collections\Collection<int, Entities\Devices\Device>
	 *
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\OneToMany(targetEntity="FastyBird\Module\Devices\Entities\Devices\Device", mappedBy="connector", cascade={"persist", "remove"}, orphanRemoval=true)
	 */
	protected Common\Collections\Collection $devices;

	/**
	 * @var Common\Collections\Collection<int, Entities\Connectors\Properties\Property>
	 *
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\OneToMany(targetEntity="FastyBird\Module\Devices\Entities\Connectors\Properties\Property", mappedBy="connector", cascade={"persist", "remove"}, orphanRemoval=true)
	 */
	private Common\Collections\Collection $properties;

	/**
	 * @var Common\Collections\Collection<int, Entities\Connectors\Controls\Control>
	 *
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\OneToMany(targetEntity="FastyBird\Module\Devices\Entities\Connectors\Controls\Control", mappedBy="connector", cascade={"persist", "remove"}, orphanRemoval=true)
	 */
	protected Common\Collections\Collection $controls;

	public function __construct(
		string $identifier,
		Uuid\UuidInterface|null $id = null,
	)
	{
		$this->id = $id ?? Uuid\Uuid::uuid4();

		$this->identifier = $identifier;

		$this->devices = new Common\Collections\ArrayCollection();
		$this->properties = new Common\Collections\ArrayCollection();
		$this->controls = new Common\Collections\ArrayCollection();
	}

	abstract public function getType(): string;

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

	public function isEnabled(): bool
	{
		return $this->enabled;
	}

	public function setEnabled(bool $enabled): void
	{
		$this->enabled = $enabled;
	}

	/**
	 * @return Array<Entities\Devices\Device>
	 */
	public function getDevices(): array
	{
		return $this->devices->toArray();
	}

	/**
	 * @return Array<Entities\Connectors\Properties\Property>
	 */
	public function getProperties(): array
	{
		return $this->properties->toArray();
	}

	/**
	 * @param Array<Entities\Connectors\Properties\Property> $properties
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

	public function addProperty(Entities\Connectors\Properties\Property $property): void
	{
		// Check if collection does not contain inserting entity
		if (!$this->properties->contains($property)) {
			// ...and assign it to collection
			$this->properties->add($property);
		}
	}

	public function getProperty(string $id): Entities\Connectors\Properties\Property|null
	{
		$found = $this->properties
			->filter(static fn (Entities\Connectors\Properties\Property $row): bool => $id === $row->getPlainId());

		return $found->isEmpty() === true ? null : $found->first();
	}

	public function removeProperty(Entities\Connectors\Properties\Property $property): void
	{
		// Check if collection contain removing entity...
		if ($this->properties->contains($property)) {
			// ...and remove it from collection
			$this->properties->removeElement($property);
		}
	}

	public function hasProperty(string $property): bool
	{
		return $this->findProperty($property) !== null;
	}

	public function findProperty(string $property): Entities\Connectors\Properties\Property|null
	{
		$found = $this->properties
			->filter(
				static fn (Entities\Connectors\Properties\Property $row): bool => $property === $row->getIdentifier()
			);

		return $found->isEmpty() === true ? null : $found->first();
	}

	/**
	 * @return Array<Entities\Connectors\Controls\Control>
	 */
	public function getControls(): array
	{
		return $this->controls->toArray();
	}

	/**
	 * @param Array<Entities\Connectors\Controls\Control> $controls
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

	public function addControl(Entities\Connectors\Controls\Control $control): void
	{
		// Check if collection does not contain inserting entity
		if (!$this->controls->contains($control)) {
			// ...and assign it to collection
			$this->controls->add($control);
		}
	}

	public function getControl(string $name): Entities\Connectors\Controls\Control|null
	{
		$found = $this->controls
			->filter(static fn (Entities\Connectors\Controls\Control $row): bool => $name === $row->getName());

		return $found->isEmpty() === true ? null : $found->first();
	}

	public function removeControl(Entities\Connectors\Controls\Control $control): void
	{
		// Check if collection contain removing entity...
		if ($this->controls->contains($control)) {
			// ...and remove it from collection
			$this->controls->removeElement($control);
		}
	}

	public function hasControl(string $name): bool
	{
		return $this->findControl($name) !== null;
	}

	public function findControl(string $name): Entities\Connectors\Controls\Control|null
	{
		$found = $this->controls
			->filter(static fn (Entities\Connectors\Controls\Control $row): bool => $name === $row->getName());

		return $found->isEmpty() === true ? null : $found->first();
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getPlainId(),
			'type' => $this->getType(),
			'identifier' => $this->getIdentifier(),
			'name' => $this->getName(),
			'comment' => $this->getComment(),
			'enabled' => $this->isEnabled(),

			'owner' => $this->getOwnerId(),
		];
	}

	public function getSource(): MetadataTypes\ModuleSource|MetadataTypes\PluginSource|MetadataTypes\ConnectorSource
	{
		return MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES);
	}

}
