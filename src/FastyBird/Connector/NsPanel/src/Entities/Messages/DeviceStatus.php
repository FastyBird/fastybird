<?php declare(strict_types = 1);

/**
 * DeviceStatus.php
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

/**
 * Device status message entity
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceStatus implements Entity
{

	/**
	 * @param array<CapabilityStatus> $statuses
	 */
	public function __construct(
		#[BootstrapObjectMapper\Rules\UuidValue()]
		private readonly Uuid\UuidInterface $connector,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $identifier,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(CapabilityStatus::class),
		)]
		private readonly array $statuses,
	)
	{
	}

	public function getConnector(): Uuid\UuidInterface
	{
		return $this->connector;
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	/**
	 * @return array<CapabilityStatus>
	 */
	public function getStatuses(): array
	{
		return $this->statuses;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'connector' => $this->getConnector()->toString(),
			'identifier' => $this->getIdentifier(),
			'capabilities' => array_map(
				static fn (CapabilityStatus $status): array => $status->toArray(),
				$this->getStatuses(),
			),
		];
	}

}
