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

namespace FastyBird\Module\Triggers\Entities\Conditions;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Triggers\Entities;
use IPub\DoctrineCrud\Mapping\Annotation as IPubDoctrine;
use Ramsey\Uuid;
use function array_merge;

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
class DevicePropertyCondition extends PropertyCondition
{

	/**
	 * @IPubDoctrine\Crud(is="required")
	 * @ORM\Column(type="uuid_binary", name="condition_device_property", nullable=true)
	 */
	private Uuid\UuidInterface $property;

	public function __construct(
		Uuid\UuidInterface $device,
		Uuid\UuidInterface $property,
		MetadataTypes\TriggerConditionOperator $operator,
		string $operand,
		Entities\Triggers\AutomaticTrigger $trigger,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($device, $operator, $operand, $trigger, $id);

		$this->property = $property;
	}

	public function getType(): MetadataTypes\TriggerConditionType
	{
		return MetadataTypes\TriggerConditionType::get(
			MetadataTypes\TriggerConditionType::TYPE_DEVICE_PROPERTY,
		);
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
			'property' => $this->getProperty()->toString(),
		]);
	}

}
