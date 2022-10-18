<?php declare(strict_types = 1);

/**
 * TDeviceProperty.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 * @since          0.57.0
 *
 * @date           02.06.22
 */

namespace FastyBird\Library\Metadata\Entities\DevicesModule;

use Ramsey\Uuid;

/**
 * Device property trait
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
trait TDeviceProperty
{

	protected Uuid\UuidInterface $device;

	protected Uuid\UuidInterface|null $parent;

	/** @var Array<Uuid\UuidInterface> */
	protected array $children;

	public function getDevice(): Uuid\UuidInterface
	{
		return $this->device;
	}

	public function getParent(): Uuid\UuidInterface|null
	{
		return $this->parent;
	}

	/**
	 * @return Array<Uuid\UuidInterface>
	 */
	public function getChildren(): array
	{
		return $this->children;
	}

}
