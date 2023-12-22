<?php declare(strict_types = 1);

/**
 * DeviceScriptState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           21.12.23
 */

namespace FastyBird\Connector\Shelly\Entities\API\Gen2;

use FastyBird\Connector\Shelly;
use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Types;
use Orisai\ObjectMapper;
use function array_filter;
use function array_merge;

/**
 * Generation 2 device script state entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceScriptState extends DeviceState implements Entities\API\Entity
{

	/**
	 * @param array<string> $errors
	 */
	public function __construct(
		int $id,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\ArrayEnumValue(cases: [Shelly\Constants::VALUE_NOT_AVAILABLE]),
		])]
		private readonly bool|string $running,
		array $errors = [],
	)
	{
		parent::__construct($id, $errors);
	}

	public function getType(): Types\ComponentType
	{
		return Types\ComponentType::get(Types\ComponentType::SCRIPT);
	}

	public function getRunning(): bool|string
	{
		return $this->running;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_merge(
			parent::toArray(),
			[
				'running' => $this->getRunning(),
			],
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function toState(): array
	{
		return array_filter(
			array_merge(
				parent::toState(),
				[
					'running' => $this->getRunning(),
				],
			),
			static fn ($value): bool => $value !== Shelly\Constants::VALUE_NOT_AVAILABLE,
		);
	}

}
