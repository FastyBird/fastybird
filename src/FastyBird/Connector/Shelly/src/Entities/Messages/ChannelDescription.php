<?php declare(strict_types = 1);

/**
 * ChannelDescription.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           27.12.22
 */

namespace FastyBird\Connector\Shelly\Entities\Messages;

use Nette;
use function array_map;
use function array_unique;
use const SORT_REGULAR;

/**
 * Device channel description entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ChannelDescription implements Entity
{

	use Nette\SmartObject;

	/** @var array<PropertyDescription> */
	private array $properties;

	/**
	 * @param array<PropertyDescription> $properties
	 */
	public function __construct(
		private readonly string $identifier,
		private readonly string|null $name,
		array $properties = [],
	)
	{
		$this->properties = array_unique($properties, SORT_REGULAR);
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	public function getName(): string|null
	{
		return $this->name;
	}

	/**
	 * @return array<PropertyDescription>
	 */
	public function getProperties(): array
	{
		return $this->properties;
	}

	public function addProperty(PropertyDescription $property): void
	{
		$this->properties[] = $property;

		$this->properties = array_unique($this->properties, SORT_REGULAR);
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'identifier' => $this->getIdentifier(),
			'name' => $this->getName(),
			'properties' => array_map(
				static fn (PropertyDescription $property): array => $property->toArray(),
				$this->getProperties(),
			),
		];
	}

}
