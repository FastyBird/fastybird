<?php declare(strict_types = 1);

/**
 * ReportDeviceState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           16.11.23
 */

namespace FastyBird\Connector\Tuya\Entities\API;

use Orisai\ObjectMapper;
use function array_map;

/**
 * Report device state entity
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ReportDeviceState implements Entity
{

	/**
	 * @param array<DataPointState> $dataPoints
	 */
	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $identifier,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DataPointState::class),
		)]
		#[ObjectMapper\Modifiers\FieldName('data_points')]
		private readonly array $dataPoints,
	)
	{
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	/**
	 * @return array<DataPointState>
	 */
	public function getDataPoints(): array
	{
		return $this->dataPoints;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'identifier' => $this->getIdentifier(),
			'data_points' => array_map(
				static fn (DataPointState $channel): array => $channel->toArray(),
				$this->getDataPoints(),
			),
		];
	}

}
