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
 *     name="fb_triggers_module_conditions_device_property",
 *     options={
 *       "collate"="utf8mb4_general_ci",
 *       "charset"="utf8mb4",
 *       "comment"="Devices conditions"
 *     },
 *     indexes={
 *       @ORM\Index(name="condition_device_idx", columns={"condition_device"}),
 *       @ORM\Index(name="condition_property_idx", columns={"condition_device_property"})
 *     }
 * )
 */
class DevicePropertyCondition extends PropertyCondition implements IDevicePropertyCondition
{

	/**
	 * @var Uuid\UuidInterface
	 *
	 * @IPubDoctrine\Crud(is="required")
	 * @ORM\Column(type="uuid_binary", name="condition_device_property", nullable=true)
	 */
	private Uuid\UuidInterface $property;

	/**
	 * @param Uuid\UuidInterface $device
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
		Uuid\UuidInterface $property,
		MetadataTypes\TriggerConditionOperatorType $operator,
		string $operand,
		Entities\Triggers\IAutomaticTrigger $trigger,
		?Uuid\UuidInterface $id = null
	) {
		parent::__construct($device, $operator, $operand, $trigger, $id);

		$this->property = $property;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getType(): MetadataTypes\TriggerConditionTypeType
	{
		return MetadataTypes\TriggerConditionTypeType::get(MetadataTypes\TriggerConditionTypeType::TYPE_DEVICE_PROPERTY);
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'property' => $this->getProperty()->toString(),
		]);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getProperty(): Uuid\UuidInterface
	{
		return $this->property;
	}

}
