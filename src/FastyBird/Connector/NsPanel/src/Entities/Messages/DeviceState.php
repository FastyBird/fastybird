<?php declare(strict_types = 1);

/**
 * DeviceState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           15.07.23
 */

namespace FastyBird\Connector\NsPanel\Entities\Messages;

use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_map;
use function array_merge;

/**
 * Device state message entity
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceState extends Device implements Entity
{

	/**
	 * @param array<CapabilityState> $state
	 */
	public function __construct(
		Uuid\UuidInterface $connector,
		string $identifier,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(CapabilityState::class),
		)]
		private readonly array $state,
	)
	{
		parent::__construct($connector, $identifier);
	}

	/**
	 * @return array<CapabilityState>
	 */
	public function getState(): array
	{
		return $this->state;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'state' => array_map(
				static fn (CapabilityState $state): array => $state->toArray(),
				$this->getState(),
			),
		]);
	}

}
