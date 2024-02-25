<?php declare(strict_types = 1);

/**
 * StoreChannelPropertyState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           04.09.22
 */

namespace FastyBird\Connector\Tuya\Queue\Messages;

use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_map;
use function array_merge;

/**
 * Device status message
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StoreChannelPropertyState extends Device
{

	/**
	 * @param array<DataPointState> $dataPoints
	 */
	public function __construct(
		Uuid\UuidInterface $connector,
		string $identifier,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DataPointState::class),
		)]
		#[ObjectMapper\Modifiers\FieldName('data_points')]
		private readonly array $dataPoints,
	)
	{
		parent::__construct($connector, $identifier);
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
		return array_merge(parent::toArray(), [
			'data_points' => array_map(
				static fn (DataPointState $channel): array => $channel->toArray(),
				$this->getDataPoints(),
			),
		]);
	}

}
