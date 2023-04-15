<?php declare(strict_types = 1);

/**
 * ActionDeviceProperty.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           01.06.22
 */

namespace FastyBird\Library\Metadata\Entities\Actions;

use FastyBird\Library\Metadata\Exceptions;
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
final class ActionDeviceProperty extends ActionProperty
{

	private Uuid\UuidInterface $device;

	public function __construct(
		string $action,
		string $device,
		string $property,
		float|bool|int|string|null $expectedValue = null,
	)
	{
		parent::__construct($action, $property, $expectedValue);

		$this->device = Uuid\Uuid::fromString($device);
	}

	public function getDevice(): Uuid\UuidInterface
	{
		return $this->device;
	}

	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'device' => $this->getDevice()->toString(),
		]);
	}

	/**
	 * @return array<string, mixed>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function __serialize(): array
	{
		return $this->toArray();
	}

}
