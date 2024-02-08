<?php declare(strict_types = 1);

/**
 * DevicePropertyAction.php
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

namespace FastyBird\Library\Metadata\Documents\TriggersModule;

use FastyBird\Library\Application\ObjectMapper as ApplicationObjectMapper;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_merge;

/**
 * Device property action document
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DevicePropertyAction extends Action
{

	public function __construct(
		Uuid\UuidInterface $id,
		Uuid\UuidInterface $trigger,
		string $type,
		bool $enabled,
		#[ApplicationObjectMapper\Rules\UuidValue()]
		private readonly Uuid\UuidInterface $device,
		#[ApplicationObjectMapper\Rules\UuidValue()]
		private readonly Uuid\UuidInterface $property,
		#[ObjectMapper\Rules\BoolValue()]
		private readonly string $value,
		bool|null $isTriggered = null,
		Uuid\UuidInterface|null $owner = null,
	)
	{
		parent::__construct($id, $trigger, $type, $enabled, $isTriggered, $owner);
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

}
