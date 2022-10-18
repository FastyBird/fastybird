<?php declare(strict_types = 1);

/**
 * DevicePropertyAction.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 * @since          0.57.0
 *
 * @date           01.06.22
 */

namespace FastyBird\Library\Metadata\Entities\TriggersModule;

use Ramsey\Uuid;
use function array_merge;

/**
 * Device property action entity
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DevicePropertyAction extends Action
{

	private Uuid\UuidInterface $device;

	private Uuid\UuidInterface $property;

	public function __construct(
		string $id,
		string $trigger,
		string $type,
		bool $enabled,
		string $device,
		string $property,
		private readonly string $value,
		bool|null $isTriggered = null,
		string|null $owner = null,
	)
	{
		parent::__construct($id, $trigger, $type, $enabled, $isTriggered, $owner);

		$this->device = Uuid\Uuid::fromString($device);
		$this->property = Uuid\Uuid::fromString($property);
	}

	public function getDevice(): Uuid\UuidInterface
	{
		return $this->device;
	}

	public function getProperty(): Uuid\UuidInterface
	{
		return $this->property;
	}

	public function getValue(): string
	{
		return $this->value;
	}

	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'device' => $this->getDevice()->toString(),
			'property' => $this->getProperty()->toString(),
			'value' => $this->getValue(),
		]);
	}

	/**
	 * @return Array<string, mixed>
	 */
	public function __serialize(): array
	{
		return $this->toArray();
	}

}
