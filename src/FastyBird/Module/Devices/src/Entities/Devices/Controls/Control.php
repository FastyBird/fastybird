<?php declare(strict_types = 1);

/**
 * Control.php
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

namespace FastyBird\Module\Devices\Entities\Devices\Controls;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities;
use IPub\DoctrineCrud\Mapping\Annotation as IPubDoctrine;
use IPub\DoctrineTimestampable;
use Nette\Utils;
use Ramsey\Uuid;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="fb_devices_module_devices_controls",
 *     options={
 *       "collate"="utf8mb4_general_ci",
 *       "charset"="utf8mb4",
 *       "comment"="Devices controls"
 *     },
 *     uniqueConstraints={
 *       @ORM\UniqueConstraint(name="control_name_unique", columns={"control_name", "device_id"})
 *     },
 *     indexes={
 *       @ORM\Index(name="control_name_idx", columns={"control_name"})
 *     }
 * )
 */
class Control implements Entities\Entity,
	DoctrineTimestampable\Entities\IEntityCreated, DoctrineTimestampable\Entities\IEntityUpdated
{

	use Entities\TEntity;
	use DoctrineTimestampable\Entities\TEntityCreated;
	use DoctrineTimestampable\Entities\TEntityUpdated;

	/**
	 * @ORM\Id
	 * @ORM\Column(type="uuid_binary", name="control_id")
	 * @ORM\CustomIdGenerator(class="Ramsey\Uuid\Doctrine\UuidGenerator")
	 */
	protected Uuid\UuidInterface $id;

	/**
	 * @IPubDoctrine\Crud(is="required")
	 * @ORM\Column(type="string", name="control_name", length=100, nullable=false)
	 */
	private string $name;

	/**
	 * @IPubDoctrine\Crud(is="required")
	 * @ORM\ManyToOne(targetEntity="FastyBird\Module\Devices\Entities\Devices\Device", inversedBy="controls", cascade={"persist"})
	 * @ORM\JoinColumn(name="device_id", referencedColumnName="device_id", onDelete="CASCADE", nullable=false)
	 */
	private Entities\Devices\Device $device;

	public function __construct(string $name, Entities\Devices\Device $device)
	{
		// @phpstan-ignore-next-line
		$this->id = Uuid\Uuid::uuid4();

		$this->name = $name;
		$this->device = $device;

		$device->addControl($this);
	}

	public function getName(): string
	{
		return $this->name;
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
		return [
			'id' => $this->getId()->toString(),
			'name' => $this->getName(),

			'device' => $this->getDevice()->getId()->toString(),

			'owner' => $this->getDevice()->getOwnerId(),
			'created_at' => $this->getCreatedAt()?->format(DateTimeInterface::ATOM),
			'updated_at' => $this->getUpdatedAt()?->format(DateTimeInterface::ATOM),
		];
	}

	public function getSource(): MetadataTypes\Sources\Module
	{
		return MetadataTypes\Sources\Module::get(MetadataTypes\Sources\Module::DEVICES);
	}

	/**
	 * @throws Utils\JsonException
	 */
	public function __toString(): string
	{
		return Utils\Json::encode($this->toArray());
	}

}
