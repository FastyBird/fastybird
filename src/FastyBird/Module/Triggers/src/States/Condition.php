<?php declare(strict_types = 1);

/**
 * ICondition.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     States
 * @since          0.6.0
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
interface ICondition
{

	/**
	 * @return Uuid\UuidInterface
	 */
	public function getId(): Uuid\UuidInterface;

	/**
	 * @param bool $result
	 *
	 * @return void
	 */
	public function setFulfilled(bool $result): void;

	/**
	 * @return bool
	 */
	public function isFulfilled(): bool;

}
