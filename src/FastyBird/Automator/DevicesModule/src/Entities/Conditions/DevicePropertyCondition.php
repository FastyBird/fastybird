<?php declare(strict_types = 1);

/**
 * ChannelPropertyCondition.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModuleAutomator!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           04.04.20
 */

namespace FastyBird\Automator\DevicesModule\Entities\Conditions;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Library\Application\Doctrine\Mapping as ApplicationMapping;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Triggers\Entities;
use IPub\DoctrineCrud\Mapping\Attribute as IPubDoctrine;
use Ramsey\Uuid;
use function array_merge;

#[ORM\Entity]
#[ApplicationMapping\DiscriminatorEntry(name: self::TYPE)]
class DevicePropertyCondition extends PropertyCondition
{

	public const TYPE = 'device-property';

	#[IPubDoctrine\Crud(required: true)]
	#[ORM\Column(name: 'condition_device_property', type: Uuid\Doctrine\UuidBinaryType::NAME, nullable: true)]
	private Uuid\UuidInterface $property;

	public function __construct(
		Uuid\UuidInterface $device,
		Uuid\UuidInterface $property,
		MetadataTypes\TriggerConditionOperator $operator,
		string $operand,
		Entities\Triggers\Automatic $trigger,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($device, $operator, $operand, $trigger, $id);

		$this->property = $property;
	}

	public static function getType(): string
	{
		return self::TYPE;
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
