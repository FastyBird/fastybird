<?php declare(strict_types = 1);

/**
 * DeviceDataPointStatus.php
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

use Ramsey\Uuid;
use function array_map;
use function array_merge;

/**
 * Device status message entity
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceStatus extends Device
{

	/**
	 * @param array<CapabilityStatus> $statuses
	 */
	public function __construct(
		Uuid\UuidInterface $connector,
		string $identifier,
		private readonly array $statuses,
	)
	{
		parent::__construct($connector, $identifier);
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
		return array_merge(parent::toArray(), [
			'capabilities' => array_map(
				static fn (CapabilityStatus $status): array => $status->toArray(),
				$this->getStatuses(),
			),
		]);
	}

}
