<?php declare(strict_types = 1);

/**
 * ActionProperty.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Metadata!
 * @subpackage     Entities
 * @since          0.57.0
 *
 * @date           01.06.22
 */

namespace FastyBird\Library\Metadata\Entities\Actions;

use FastyBird\Library\Metadata\Entities;
use FastyBird\Library\Metadata\Exceptions;
use FastyBird\Library\Metadata\Types;
use Ramsey\Uuid;
use function array_merge;
use function sprintf;

/**
 * Property action entity
 *
 * @package        FastyBird:Metadata!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class ActionProperty implements Entities\Entity
{

	protected Types\PropertyAction $action;

	protected Uuid\UuidInterface $property;

	protected string|int|bool|float|null $expectedValue;

	public function __construct(
		string $action,
		string $property,
		float|bool|int|string|null $expectedValue = null,
	)
	{
		$this->action = Types\PropertyAction::get($action);

		$this->property = Uuid\Uuid::fromString($property);

		$this->expectedValue = $expectedValue;
	}

	public function getAction(): Types\PropertyAction
	{
		return $this->action;
	}

	public function getProperty(): Uuid\UuidInterface
	{
		return $this->property;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getExpectedValue(): float|bool|int|string|null
	{
		if (!$this->getAction()->equalsValue(Types\PropertyAction::ACTION_SET)) {
			throw new Exceptions\InvalidState(
				sprintf('Expected value is available only for action: %s', Types\PropertyAction::ACTION_SET),
			);
		}

		return $this->expectedValue;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function toArray(): array
	{
		$data = [
			'action' => $this->getAction()->getValue(),
			'property' => $this->getProperty()->toString(),
		];

		if ($this->getAction()->equalsValue(Types\PropertyAction::ACTION_SET)) {
			$data = array_merge($data, [
				'expected_value' => $this->getExpectedValue(),
			]);
		}

		return $data;
	}

}
