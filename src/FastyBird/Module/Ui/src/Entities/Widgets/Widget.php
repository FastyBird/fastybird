<?php declare(strict_types = 1);

/**
 * Widget.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           25.05.20
 */

namespace FastyBird\Module\Ui\Entities\Widgets;

use DateTimeInterface;
use Doctrine\Common;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Ui\Entities;
use FastyBird\Module\Ui\Exceptions;
use IPub\DoctrineCrud\Mapping\Attribute as IPubDoctrine;
use IPub\DoctrineTimestampable;
use Nette\Utils;
use Ramsey\Uuid;
use function array_map;

#[ORM\Entity]
#[ORM\Table(
	name: 'fb_ui_module_widgets',
	options: [
		'collate' => 'utf8mb4_general_ci',
		'charset' => 'utf8mb4',
		'comment' => 'User interface widgets',
	],
)]
#[ORM\Index(columns: ['widget_type'], name: 'widget_type_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'widget_type', type: 'string', length: 100)]
#[ORM\MappedSuperclass]
abstract class Widget implements Entities\Entity,
	Entities\EntityParams,
	DoctrineTimestampable\Entities\IEntityCreated, DoctrineTimestampable\Entities\IEntityUpdated
{

	use Entities\TEntity;
	use Entities\TEntityParams;
	use DoctrineTimestampable\Entities\TEntityCreated;
	use DoctrineTimestampable\Entities\TEntityUpdated;

	#[ORM\Id]
	#[ORM\Column(name: 'widget_id', type: Uuid\Doctrine\UuidBinaryType::NAME)]
	#[ORM\CustomIdGenerator(class: Uuid\Doctrine\UuidGenerator::class)]
	protected Uuid\UuidInterface $id;

	#[IPubDoctrine\Crud(required: true, writable: true)]
	#[ORM\Column(name: 'widget_identifier', type: 'string', nullable: false)]
	protected string $identifier;

	#[IPubDoctrine\Crud(required: true, writable: true)]
	#[ORM\Column(name: 'widget_name', type: 'string', nullable: false)]
	protected string $name;

	#[IPubDoctrine\Crud(required: true, writable: true)]
	#[ORM\OneToOne(
		mappedBy: 'widget',
		targetEntity: Entities\Widgets\Display\Display::class,
		cascade: ['persist', 'remove'],
	)]
	protected Display\Display $display;

	/** @var Common\Collections\Collection<int, Entities\Dashboards\Dashboard> */
	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\ManyToMany(
		targetEntity: Entities\Dashboards\Dashboard::class,
		mappedBy: 'widgets',
		cascade: ['persist', 'remove'],
		orphanRemoval: true,
	)]
	protected Common\Collections\Collection $dashboards;

	/** @var Common\Collections\Collection<int, Entities\Groups\Group> */
	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\ManyToMany(
		targetEntity: Entities\Groups\Group::class,
		mappedBy: 'widgets',
		cascade: ['persist', 'remove'],
		orphanRemoval: true,
	)]
	protected Common\Collections\Collection $groups;

	/** @var Common\Collections\Collection<int, Entities\Widgets\DataSources\DataSource> */
	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\OneToMany(
		mappedBy: 'widget',
		targetEntity: Entities\Widgets\DataSources\DataSource::class,
		cascade: ['persist', 'remove'],
		orphanRemoval: true,
	)]
	protected Common\Collections\Collection $dataSources;

	public function __construct(
		string $identifier,
		string $name,
		Uuid\UuidInterface|null $id = null,
	)
	{
		$this->id = $id ?? Uuid\Uuid::uuid4();

		$this->identifier = $identifier;
		$this->name = $name;

		$this->dashboards = new Common\Collections\ArrayCollection();
		$this->groups = new Common\Collections\ArrayCollection();
		$this->dataSources = new Common\Collections\ArrayCollection();
	}

	abstract public static function getType(): string;

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function setName(string $name): void
	{
		$this->name = $name;
	}

	public function getDisplay(): Entities\Widgets\Display\Display
	{
		return $this->display;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function setDisplay(Entities\Widgets\Display\Display $display): void
	{
		$isAllowed = false;

		foreach ($this->getAllowedDisplayTypes() as $displayType) {
			if ($display instanceof $displayType) {
				$isAllowed = true;
			}
		}

		if (!$isAllowed) {
			throw new Exceptions\InvalidArgument('Provided display entity is not valid for this widget type');
		}

		$this->display = $display;
	}

	public function addDataSource(Entities\Widgets\DataSources\DataSource $dataSource): void
	{
		// Check if collection does not contain inserting entity
		if (!$this->dataSources->contains($dataSource)) {
			// ...and assign it to collection
			$this->dataSources->add($dataSource);
		}
	}

	/**
	 * @return array<Entities\Widgets\DataSources\DataSource>
	 */
	public function getDataSources(): array
	{
		return $this->dataSources->toArray();
	}

	/**
	 * @param array<Entities\Widgets\DataSources\DataSource> $dataSources
	 */
	public function setDataSources(array $dataSources = []): void
	{
		$this->dataSources = new Common\Collections\ArrayCollection();

		foreach ($dataSources as $entity) {
			$this->dataSources->add($entity);
		}
	}

	public function removeDataSource(Entities\Widgets\DataSources\DataSource $dataSource): void
	{
		// Check if collection contain removing entity...
		if ($this->dataSources->contains($dataSource)) {
			// ...and remove it from collection
			$this->dataSources->removeElement($dataSource);
		}
	}

	public function addDashboard(Entities\Dashboards\Dashboard $dashboard): void
	{
		$this->dashboards = new Common\Collections\ArrayCollection();

		$dashboard->addWidget($this);

		// ...and assign it to collection
		$this->dashboards->add($dashboard);
	}

	/**
	 * @return array<Entities\Dashboards\Dashboard>
	 */
	public function getDashboards(): array
	{
		return $this->dashboards->toArray();
	}

	/**
	 * @param array<Entities\Dashboards\Dashboard> $dashboards
	 */
	public function setDashboards(array $dashboards = []): void
	{
		$this->dashboards = new Common\Collections\ArrayCollection();

		foreach ($dashboards as $entity) {
			if (!$this->dashboards->contains($entity)) {
				$entity->addWidget($this);

				// ...and assign them to collection
				$this->dashboards->add($entity);
			}
		}
	}

	public function getDashboard(string $id): Entities\Dashboards\Dashboard|null
	{
		$found = $this->dashboards
			->filter(static fn (Entities\Dashboards\Dashboard $row): bool => $id === $row->getId()->toString());

		return $found->isEmpty() ? null : $found->first();
	}

	public function removeDashboard(Entities\Dashboards\Dashboard $dashboard): void
	{
		// Check if collection contain removing entity...
		if ($this->dashboards->contains($dashboard)) {
			// ...and remove it from collection
			$this->dashboards->removeElement($dashboard);
		}
	}

	public function addGroup(Entities\Groups\Group $group): void
	{
		$this->groups = new Common\Collections\ArrayCollection();

		$group->addWidget($this);

		// ...and assign it to collection
		$this->groups->add($group);
	}

	/**
	 * @return array<Entities\Groups\Group>
	 */
	public function getGroups(): array
	{
		return $this->groups->toArray();
	}

	/**
	 * @param array<Entities\Groups\Group> $groups
	 */
	public function setGroups(array $groups = []): void
	{
		$this->groups = new Common\Collections\ArrayCollection();

		foreach ($groups as $entity) {
			if (!$this->groups->contains($entity)) {
				$entity->addWidget($this);

				// ...and assign them to collection
				$this->groups->add($entity);
			}
		}
	}

	public function getGroup(string $id): Entities\Groups\Group|null
	{
		$found = $this->groups
			->filter(static fn (Entities\Groups\Group $row): bool => $id === $row->getId()->toString());

		return $found->isEmpty() ? null : $found->first();
	}

	public function removeGroup(Entities\Groups\Group $group): void
	{
		// Check if collection contain removing entity...
		if ($this->groups->contains($group)) {
			// ...and remove it from collection
			$this->groups->removeElement($group);
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getId()->toString(),
			'identifier' => $this->getIdentifier(),
			'name' => $this->getName(),
			'type' => static::getType(),

			'dashboards' => array_map(
				static fn (Entities\Dashboards\Dashboard $dashboard): string => $dashboard->getId()->toString(),
				$this->getDashboards(),
			),
			'groups' => array_map(
				static fn (Entities\Groups\Group $group): string => $group->getId()->toString(),
				$this->getGroups(),
			),
			'data_sources' => array_map(
				static fn (Entities\Widgets\DataSources\DataSource $dataSource): string => $dataSource->getId()->toString(),
				$this->getDataSources(),
			),
			'display' => $this->getDisplay()->getId()->toString(),

			'created_at' => $this->getCreatedAt()?->format(DateTimeInterface::ATOM),
			'updated_at' => $this->getUpdatedAt()?->format(DateTimeInterface::ATOM),
		];
	}

	/**
	 * @return array<class-string>
	 */
	public function getAllowedDisplayTypes(): array
	{
		return [];
	}

	public function getSource(): MetadataTypes\Sources\Source
	{
		return MetadataTypes\Sources\Module::UI;
	}

	/**
	 * @throws Utils\JsonException
	 */
	public function __toString(): string
	{
		return Utils\Json::encode($this->toArray());
	}

}
