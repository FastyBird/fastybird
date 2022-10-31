<?php declare(strict_types = 1);

/**
 * Property.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     States
 * @since          0.1.0
 *
 * @date           03.03.20
 */

namespace FastyBird\Module\Devices\States;

use DateTimeInterface;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use Ramsey\Uuid;

/**
 * Property interface
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     States
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface Property
{

	public const ACTUAL_VALUE_KEY = 'actualValue';

	public const EXPECTED_VALUE_KEY = 'expectedValue';

	public const PENDING_KEY = 'pending';

	public const VALID_KEY = 'valid';

	public function getId(): Uuid\UuidInterface;

	public function setActualValue(
		bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|null $actual,
	): void;

	public function getActualValue(): bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|null;

	public function setExpectedValue(
		bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|null $expected,
	): void;

	public function getExpectedValue(): bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|null;

	public function setPending(bool|string|null $pending): void;

	public function getPending(): bool|string|null;

	public function isPending(): bool;

	public function setValid(bool $valid): void;

	public function isValid(): bool;

	/**
	 * @return Array<string, mixed>
	 */
	public function toArray(): array;

}
