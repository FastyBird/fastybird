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

namespace FastyBird\DevicesModule\States;

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

	public function getId(): Uuid\UuidInterface;

	public function setActualValue(float|bool|int|string|null $actual): void;

	public function getActualValue(): float|bool|int|string|null;

	public function setExpectedValue(float|bool|int|string|null $expected): void;

	public function getExpectedValue(): float|bool|int|string|null;

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
