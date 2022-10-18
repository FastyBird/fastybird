<?php declare(strict_types = 1);

/**
 * DeviceControl.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Metadata!
 * @subpackage     Entities
 * @since          0.57.0
 *
 * @date           02.06.22
 */

namespace FastyBird\Library\Metadata\Entities\DevicesModule;

use Ramsey\Uuid;
use function array_merge;

/**
 * Device control entity
 *
 * @package        FastyBird:Metadata!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceControl extends Control
{

	private Uuid\UuidInterface $device;

	public function __construct(
		string $id,
		string $device,
		string $name,
		string|null $owner = null,
	)
	{
		parent::__construct($id, $name, $owner);

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
	 * @return Array<string, mixed>
	 */
	public function __serialize(): array
	{
		return $this->toArray();
	}

}
