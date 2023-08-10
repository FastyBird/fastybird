<?php declare(strict_types = 1);

/**
 * StoreDeviceState.php
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

use FastyBird\Library\Bootstrap\ObjectMapper as BootstrapObjectMapper;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_map;
use function array_merge;

/**
 * Store device state message entity
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StoreDeviceState extends Device implements Entity
{

	/**
	 * @param array<CapabilityState> $state
	 */
	public function __construct(
		Uuid\UuidInterface $connector,
		#[BootstrapObjectMapper\Rules\UuidValue()]
		private readonly Uuid\UuidInterface $gateway,
		string $identifier,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(CapabilityState::class),
		)]
		private readonly array $state,
	)
	{
		parent::__construct($connector, $identifier);
	}

	public function getGateway(): Uuid\UuidInterface
	{
		return $this->gateway;
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
			'gateway' => $this->getGateway()->toString(),
			'state' => array_map(
				static fn (CapabilityState $state): array => $state->toArray(),
				$this->getState(),
			),
		]);
	}

}
