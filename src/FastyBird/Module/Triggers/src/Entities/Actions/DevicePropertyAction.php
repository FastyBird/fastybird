<?php declare(strict_types = 1);

/**
 * DevicePropertyAction.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Entities
 * @since          0.4.1
 *
 * @date           06.10.21
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
 *     name="fb_triggers_module_actions_device_property",
 *     options={
 *       "collate"="utf8mb4_general_ci",
 *       "charset"="utf8mb4",
 *       "comment"="Devices actions"
 *     },
 *     indexes={
 *       @ORM\Index(name="action_device_idx", columns={"action_device"}),
 *       @ORM\Index(name="action_property_idx", columns={"action_device_property"})
 *     }
 * )
 */
class DevicePropertyAction extends PropertyAction
{

	/**
	 * @IPubDoctrine\Crud(is="required")
	 * @ORM\Column(type="uuid_binary", name="action_device_property", nullable=true)
	 */
	private Uuid\UuidInterface $property;

	public function __construct(
		Uuid\UuidInterface $device,
		Uuid\UuidInterface $property,
		string $value,
		Entities\Triggers\Trigger $trigger,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($device, $value, $trigger, $id);

		$this->property = $property;
	}

	public function getType(): MetadataTypes\TriggerActionType
	{
		return MetadataTypes\TriggerActionType::get(MetadataTypes\TriggerActionType::TYPE_DEVICE_PROPERTY);
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
