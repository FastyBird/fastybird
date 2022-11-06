<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Tests\Fixtures\Dummy;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Module\Triggers\Entities;
use IPub\DoctrineCrud\Mapping\Annotation as IPubDoctrine;
use Ramsey\Uuid;
use function array_merge;

/**
 * @ORM\Entity
 */
class DummyActionEntity extends Entities\Actions\Action
{

	public const ACTION_TYPE = 'dummy';

	/**
	 * @IPubDoctrine\Crud(is="required")
	 * @ORM\Column(type="uuid_binary", name="action_do_item", nullable=true)
	 */
	private Uuid\UuidInterface $doItem;

	/**
	 * @IPubDoctrine\Crud(is={"required", "writable"})
	 * @ORM\Column(type="string", name="action_value", length=100, nullable=true)
	 */
	private string $value;

	public function getType(): string
	{
		return 'dummy';
	}

	public function setDoItem(Uuid\UuidInterface $doItem): void
	{
		$this->doItem = $doItem;
	}

	public function getDoItem(): Uuid\UuidInterface
	{
		return $this->doItem;
	}

	public function setValue(string $value): void
	{
		$this->value = $value;
	}

	public function getValue(): string
	{
		return $this->value;
	}

	public function getDiscriminatorName(): string
	{
		return 'dummy';
	}

	public function validate(string $value): bool
	{
		return $this->value === $value;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'do_item' => $this->getDoItem()->toString(),
			'value' => $this->getValue(),
		]);
	}

}
