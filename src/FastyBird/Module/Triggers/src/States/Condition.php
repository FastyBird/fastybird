<?php declare(strict_types = 1);

/**
 * Condition.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     States
 * @since          1.0.0
 *
 * @date           09.01.22
 */

namespace FastyBird\Module\Triggers\States;

use Ramsey\Uuid;

/**
 * Condition state interface
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     States
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface Condition
{

	public function getId(): Uuid\UuidInterface;

	public function setFulfilled(bool $result): void;

	public function isFulfilled(): bool;

}
