<?php declare(strict_types = 1);

/**
 * Property.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     States
 * @since          1.0.0
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

	public const CREATED_AT_KEY = 'createdAt';

	public const UPDATED_AT_KEY = '$updatedAt';

	public function getId(): Uuid\UuidInterface;

	public function setActualValue(
		bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $actual,
	): void;
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	public function getActualValue(): bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null;

	public function setExpectedValue(
		bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $expected,
	): void;
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	public function getExpectedValue(): bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null;

	public function setPending(bool|string $pending): void;

	public function getPending(): bool|DateTimeInterface;

	public function isPending(): bool;

	public function setValid(bool $valid): void;

	public function isValid(): bool;

	public function getCreatedAt(): DateTimeInterface|null;

	public function setCreatedAt(string|null $createdAt = null): void;

	public function getUpdatedAt(): DateTimeInterface|null;

	public function setUpdatedAt(string|null $updatedAt = null): void;

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array;

}
