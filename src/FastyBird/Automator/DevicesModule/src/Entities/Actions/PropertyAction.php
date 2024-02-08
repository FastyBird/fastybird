<?php declare(strict_types = 1);

/**
 * PropertyAction.php
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

namespace FastyBird\Automator\DevicesModule\Entities\Actions;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Triggers\Entities as TriggersEntities;
use IPub\DoctrineCrud\Mapping\Attribute as IPubDoctrine;
use Ramsey\Uuid;
use function array_merge;

#[ORM\MappedSuperclass]
abstract class PropertyAction extends TriggersEntities\Actions\Action
{

	#[IPubDoctrine\Crud(required: true)]
	#[ORM\Column(name: 'action_device', type: Uuid\Doctrine\UuidBinaryType::NAME, nullable: true)]
	protected Uuid\UuidInterface $device;

	#[IPubDoctrine\Crud(required: true, writable: true)]
	#[ORM\Column(name: 'action_value', type: 'string', nullable: true, length: 100)]
	protected string $value;

	public function __construct(
		Uuid\UuidInterface $device,
		string $value,
		TriggersEntities\Triggers\Trigger $trigger,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($trigger, $id);

		$this->device = $device;
		$this->value = $value;
	}

	public function getValue(): string|MetadataTypes\Payloads\Payload
	{
		if (MetadataTypes\Payloads\Button::isValidValue($this->value)) {
			return MetadataTypes\Payloads\Button::get($this->value);
		}

		if (MetadataTypes\Payloads\Switcher::isValidValue($this->value)) {
			return MetadataTypes\Payloads\Switcher::get($this->value);
		}

		if (MetadataTypes\Payloads\Cover::isValidValue($this->value)) {
			return MetadataTypes\Payloads\Cover::get($this->value);
		}

		return $this->value;
	}

	public function validate(string $value): bool
	{
		return $this->value === $value;
	}

	public function getDevice(): Uuid\UuidInterface
	{
		return $this->device;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'device' => $this->getDevice()->toString(),
			'value' => (string) $this->getValue(),
		]);
	}

}
