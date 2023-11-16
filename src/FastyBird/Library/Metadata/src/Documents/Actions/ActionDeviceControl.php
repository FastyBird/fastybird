<?php declare(strict_types = 1);

/**
 * ActionDeviceControl.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           01.06.22
 */

namespace FastyBird\Library\Metadata\Documents\Actions;

use FastyBird\Library\Bootstrap\ObjectMapper as BootstrapObjectMapper;
use FastyBird\Library\Metadata\Documents;
use FastyBird\Library\Metadata\Types;
use Ramsey\Uuid;
use function array_merge;

/**
 * Device control action document
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ActionDeviceControl extends ActionControl
{

	public function __construct(
		Types\ControlAction $action,
		#[BootstrapObjectMapper\Rules\UuidValue()]
		private readonly Uuid\UuidInterface $device,
		Uuid\UuidInterface $control,
		bool|float|int|string|null $expectedValue = null,
	)
	{
		parent::__construct($action, $control, $expectedValue);
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
	 */
	public function __serialize(): array
	{
		return $this->toArray();
	}

}
