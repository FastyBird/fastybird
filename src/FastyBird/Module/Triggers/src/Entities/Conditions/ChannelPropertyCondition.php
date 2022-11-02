<?php declare(strict_types = 1);

/**
 * ChannelPropertyCondition.php
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

namespace FastyBird\TriggersModule\Entities\Conditions;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Metadata\Types as MetadataTypes;
use FastyBird\TriggersModule\Entities;
use IPub\DoctrineCrud\Mapping\Annotation as IPubDoctrine;
use Ramsey\Uuid;
use Throwable;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="fb_triggers_module_conditions_channel_property",
 *     options={
 *       "collate"="utf8mb4_general_ci",
 *       "charset"="utf8mb4",
 *       "comment"="Channels properties conditions"
 *     },
 *     indexes={
 *       @ORM\Index(name="condition_device_idx", columns={"condition_device"}),
 *       @ORM\Index(name="condition_channel_idx", columns={"condition_channel"}),
 *       @ORM\Index(name="condition_property_idx", columns={"condition_channel_property"})
 *     }
 * )
 */
class ChannelPropertyCondition extends PropertyCondition implements IChannelPropertyCondition
{

	/**
	 * @var Uuid\UuidInterface
	 *
	 * @IPubDoctrine\Crud(is="required")
	 * @ORM\Column(type="uuid_binary", name="condition_channel", nullable=true)
	 */
	private Uuid\UuidInterface $channel;

	/**
	 * @var Uuid\UuidInterface
	 *
	 * @IPubDoctrine\Crud(is="required")
	 * @ORM\Column(type="uuid_binary", name="condition_channel_property", nullable=true)
	 */
	private Uuid\UuidInterface $property;

	/**
	 * @param Uuid\UuidInterface $device
	 * @param Uuid\UuidInterface $channel
	 * @param Uuid\UuidInterface $property
	 * @param MetadataTypes\TriggerConditionOperatorType $operator
	 * @param string $operand
	 * @param Entities\Triggers\IAutomaticTrigger $trigger
	 * @param Uuid\UuidInterface|null $id
	 *
	 * @throws Throwable
	 */
	public function __construct(
		Uuid\UuidInterface $device,
		Uuid\UuidInterface $channel,
		Uuid\UuidInterface $property,
		MetadataTypes\TriggerConditionOperatorType $operator,
		string $operand,
		Entities\Triggers\IAutomaticTrigger $trigger,
		?Uuid\UuidInterface $id = null
	) {
		parent::__construct($device, $operator, $operand, $trigger, $id);

		$this->channel = $channel;
		$this->property = $property;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getType(): MetadataTypes\TriggerConditionTypeType
	{
		return MetadataTypes\TriggerConditionTypeType::get(MetadataTypes\TriggerConditionTypeType::TYPE_CHANNEL_PROPERTY);
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'channel'  => $this->getChannel()->toString(),
			'property' => $this->getProperty()->toString(),
		]);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getChannel(): Uuid\UuidInterface
	{
		return $this->channel;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getProperty(): Uuid\UuidInterface
	{
		return $this->property;
	}

}
