<?php declare(strict_types = 1);

/**
 * Action.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           01.06.22
 */

namespace FastyBird\Library\Metadata\Entities\Actions;

use FastyBird\Library\Metadata\Entities;
use FastyBird\Library\Metadata\Types;
use Ramsey\Uuid;

/**
 * Action entity
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class Action implements Entities\Entity
{

	protected Types\ControlAction $action;

	protected Uuid\UuidInterface $control;

	protected string|int|bool|float|null $expectedValue;

	public function __construct(
		string $action,
		string $control,
		float|bool|int|string|null $expectedValue = null,
	)
	{
		$this->action = Types\ControlAction::get($action);

		$this->control = Uuid\Uuid::fromString($control);

		$this->expectedValue = $expectedValue;
	}

	public function getAction(): Types\ControlAction
	{
		return $this->action;
	}

	public function getControl(): Uuid\UuidInterface
	{
		return $this->control;
	}

	public function getExpectedValue(): float|bool|int|string|null
	{
		return $this->expectedValue;
	}

	public function toArray(): array
	{
		return [
			'action' => $this->getAction()->getValue(),
			'control' => $this->getControl()->toString(),
			'expected_value' => $this->getExpectedValue(),
		];
	}

}
