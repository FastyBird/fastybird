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
use FastyBird\Core\Application\Entities\Mapping as ApplicationMapping;
use FastyBird\Module\Triggers\Entities as TriggersEntities;
use FastyBird\Module\Triggers\Types as TriggersTypes;
use IPub\DoctrineCrud\Mapping\Attribute as IPubDoctrine;
use Ramsey\Uuid;
use function array_merge;

#[ORM\Entity]
#[ApplicationMapping\DiscriminatorEntry(name: self::TYPE)]
class ChannelPropertyCondition extends PropertyCondition
{

	public const TYPE = 'channel-property';

	#[IPubDoctrine\Crud(required: true)]
	#[ORM\Column(name: 'condition_channel', type: Uuid\Doctrine\UuidBinaryType::NAME, nullable: true)]
	private Uuid\UuidInterface $channel;

	#[IPubDoctrine\Crud(required: true)]
	#[ORM\Column(name: 'condition_channel_property', type: Uuid\Doctrine\UuidBinaryType::NAME, nullable: true)]
	private Uuid\UuidInterface $property;

	public function __construct(
		Uuid\UuidInterface $device,
		Uuid\UuidInterface $channel,
		Uuid\UuidInterface $property,
		TriggersTypes\ConditionOperator $operator,
		string $operand,
		TriggersEntities\Triggers\Automatic $trigger,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($device, $operator, $operand, $trigger, $id);

		$this->channel = $channel;
		$this->property = $property;
	}

	public static function getType(): string
	{
		return self::TYPE;
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
