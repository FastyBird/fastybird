<?php declare(strict_types = 1);

/**
 * Control.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Entities
 * @since          0.1.0
 *
 * @date           19.07.19
 */

namespace FastyBird\Module\Devices\Entities\Channels\Controls;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities;
use IPub\DoctrineCrud\Mapping\Annotation as IPubDoctrine;
use IPub\DoctrineTimestampable;
use Ramsey\Uuid;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="fb_devices_module_channels_controls",
 *     options={
 *       "collate"="utf8mb4_general_ci",
 *       "charset"="utf8mb4",
 *       "comment"="Device channels controls"
 *     },
 *     uniqueConstraints={
 *       @ORM\UniqueConstraint(name="control_name_unique", columns={"control_name", "channel_id"})
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
	 * @ORM\ManyToOne(targetEntity="FastyBird\Module\Devices\Entities\Channels\Channel", inversedBy="controls")
	 * @ORM\JoinColumn(name="channel_id", referencedColumnName="channel_id", onDelete="CASCADE", nullable=false)
	 */
	private Entities\Channels\Channel $channel;

	public function __construct(string $name, Entities\Channels\Channel $channel)
	{
		$this->id = Uuid\Uuid::uuid4();

		$this->name = $name;
		$this->channel = $channel;

		$channel->addControl($this);
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getChannel(): Entities\Channels\Channel
	{
		return $this->channel;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getPlainId(),
			'name' => $this->getName(),

			'channel' => $this->getChannel()->getPlainId(),

			'owner' => $this->getChannel()->getDevice()->getOwnerId(),
		];
	}

	public function getSource(): MetadataTypes\ModuleSource
	{
		return MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES);
	}

}
