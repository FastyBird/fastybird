<?php declare(strict_types = 1);

/**
 * Dashboard.php
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

namespace FastyBird\Module\Ui\Entities\Dashboards;

use DateTimeInterface;
use Doctrine\Common;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Ui\Entities;
use IPub\DoctrineCrud\Mapping\Attribute as IPubDoctrine;
use IPub\DoctrineTimestampable;
use Nette\Utils;
use Ramsey\Uuid;
use function array_map;

#[ORM\Entity]
#[ORM\Table(
	name: 'fb_ui_module_dashboards',
	options: [
		'collate' => 'utf8mb4_general_ci',
		'charset' => 'utf8mb4',
		'comment' => 'User interface widgets dashboards',
	],
)]
#[ORM\Index(columns: ['dashboard_name'], name: 'dashboard_name_idx')]
class Dashboard implements Entities\Entity,
	Entities\EntityParams,
	DoctrineTimestampable\Entities\IEntityCreated, DoctrineTimestampable\Entities\IEntityUpdated
{

	use Entities\TEntity;
	use Entities\TEntityParams;
	use DoctrineTimestampable\Entities\TEntityCreated;
	use DoctrineTimestampable\Entities\TEntityUpdated;

	#[ORM\Id]
	#[ORM\Column(name: 'dashboard_id', type: Uuid\Doctrine\UuidBinaryType::NAME)]
	#[ORM\CustomIdGenerator(class: Uuid\Doctrine\UuidGenerator::class)]
	private Uuid\UuidInterface $id;

	#[IPubDoctrine\Crud(required: true)]
	#[ORM\Column(name: 'dashboard_name', type: 'string', nullable: false)]
	private string $name;

	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\Column(name: 'dashboard_comment', type: 'text', nullable: true, options: ['default' => null])]
	private string|null $comment = null;

	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\Column(name: 'dashboard_priority', type: 'integer', nullable: false, options: ['default' => 0])]
	private int $priority = 0;

	/** @var Common\Collections\Collection<int, Entities\Groups\Group> */
	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\OneToMany(
		mappedBy: 'dashboard',
		targetEntity: Entities\Groups\Group::class,
		cascade: ['persist', 'remove'],
		orphanRemoval: true,
	)]
	#[ORM\OrderBy(['priority' => 'ASC'])]
	private Common\Collections\Collection $groups;

	public function __construct(string $name, Uuid\UuidInterface|null $id = null)
	{
		$this->id = $id ?? Uuid\Uuid::uuid4();

		$this->name = $name;

		$this->groups = new Common\Collections\ArrayCollection();
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function setName(string $name): void
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

	public function getPriority(): int
	{
		return $this->priority;
	}

	public function setPriority(int $priority): void
	{
		$this->priority = $priority;
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
	public function setDevices(array $groups = []): void
	{
		$this->groups = new Common\Collections\ArrayCollection();

		// Process all passed entities...
		foreach ($groups as $entity) {
			// ...and assign them to collection
			$this->addGroup($entity);
		}
	}

	public function addGroup(Entities\Groups\Group $group): void
	{
		// Check if collection does not contain inserting entity
		if (!$this->groups->contains($group)) {
			// ...and assign it to collection
			$this->groups->add($group);
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
			'name' => $this->getName(),
			'comment' => $this->getComment(),
			'priority' => $this->getPriority(),

			'groups' => array_map(
				static fn (Entities\Groups\Group $group): string => $group->getId()->toString(),
				$this->getGroups(),
			),

			'created_at' => $this->getCreatedAt()?->format(DateTimeInterface::ATOM),
			'updated_at' => $this->getUpdatedAt()?->format(DateTimeInterface::ATOM),
		];
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
