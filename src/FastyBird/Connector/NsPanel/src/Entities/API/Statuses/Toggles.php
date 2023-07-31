<?php declare(strict_types = 1);

/**
 * Toggles.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           09.07.23
 */

namespace FastyBird\Connector\NsPanel\Entities\API\Statuses;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Types;
use Nette;
use stdClass;
use function array_map;

/**
 * Toggle control capability state
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Toggles implements Aggregate
{

	use Nette\SmartObject;

	/**
	 * @param array<Entities\API\Statuses\Toggle> $aggregates
	 */
	public function __construct(private readonly array $aggregates)
	{
	}

	/**
	 * @return array<Entities\API\Statuses\Toggle>
	 */
	public function getAggregates(): array
	{
		return $this->aggregates;
	}

	public function getType(): Types\Capability
	{
		return Types\Capability::get(Types\Capability::TOGGLE);
	}

	/**
	 * @return array<Types\TogglePayload>
	 */
	public function getValue(): array
	{
		return array_map(
			static fn (Entities\API\Statuses\Toggle $toggle): Types\TogglePayload => $toggle->getValue(),
			$this->getAggregates(),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_map(
			static fn (Entities\API\Statuses\Toggle $toggle): array => $toggle->toArray(),
			$this->getAggregates(),
		);
	}

	public function toJson(): object
	{
		$json = new stdClass();

		foreach ($this->getAggregates() as $aggregate) {
			$json->{$aggregate->getName()} = new stdClass();
			$json->{$aggregate->getName()}->toggleState = $aggregate->getValue()->getValue();

			if ($aggregate->getStartup() !== null) {
				$json->{$aggregate->getName()}->startup = $aggregate->getStartup()->getValue();
			}
		}

		return $json;
	}

}
