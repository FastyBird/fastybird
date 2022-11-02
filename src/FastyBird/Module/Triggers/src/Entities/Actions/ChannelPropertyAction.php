<?php declare(strict_types = 1);

/**
 * ChannelPropertyAction.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Entities
 * @since          0.1.0
 *
 * @date           04.04.20
 */

namespace FastyBird\Module\Triggers\Entities\Actions;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Triggers\Entities;
use IPub\DoctrineCrud\Mapping\Annotation as IPubDoctrine;
use Ramsey\Uuid;
use function array_merge;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="fb_triggers_module_actions_channel_property",
 *     options={
 *       "collate"="utf8mb4_general_ci",
 *       "charset"="utf8mb4",
 *       "comment"="Channels actions"
 *     },
 *     indexes={
 *       @ORM\Index(name="action_device_idx", columns={"action_device"}),
 *       @ORM\Index(name="action_channel_idx", columns={"action_channel"}),
 *       @ORM\Index(name="action_property_idx", columns={"action_channel_property"})
 *     }
 * )
 */
class ChannelPropertyAction extends PropertyAction
{

	/**
	 * @IPubDoctrine\Crud(is="required")
	 * @ORM\Column(type="uuid_binary", name="action_channel", nullable=true)
	 */
	private Uuid\UuidInterface $channel;

	/**
	 * @IPubDoctrine\Crud(is="required")
	 * @ORM\Column(type="uuid_binary", name="action_channel_property", nullable=true)
	 */
	private Uuid\UuidInterface $property;

	public function __construct(
		Uuid\UuidInterface $device,
		Uuid\UuidInterface $channel,
		Uuid\UuidInterface $property,
		string $value,
		Entities\Triggers\Trigger $trigger,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($device, $value, $trigger, $id);

		$this->channel = $channel;
		$this->property = $property;
	}

	public function getType(): MetadataTypes\TriggerActionType
	{
		return MetadataTypes\TriggerActionType::get(MetadataTypes\TriggerActionType::TYPE_CHANNEL_PROPERTY);
	}

	public function getChannel(): Uuid\UuidInterface
	{
		return $this->channel;
	}

	public function getProperty(): Uuid\UuidInterface
	{
		return $this->property;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'channel' => $this->getChannel()->toString(),
			'property' => $this->getProperty()->toString(),
		]);
	}

}
