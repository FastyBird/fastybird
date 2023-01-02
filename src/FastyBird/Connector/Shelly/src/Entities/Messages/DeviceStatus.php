<?php declare(strict_types = 1);

/**
 * DeviceStatus.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           18.07.22
 */

namespace FastyBird\Connector\Shelly\Entities\Messages;

use FastyBird\Connector\Shelly\Types;
use Ramsey\Uuid;
use function array_map;
use function array_merge;

/**
 * Device status message entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceStatus extends Device
{

	/**
	 * @param array<PropertyStatus|ChannelStatus> $statuses
	 */
	public function __construct(
		Types\MessageSource $source,
		Uuid\UuidInterface $connector,
		string $identifier,
		private readonly array $statuses,
	)
	{
		parent::__construct($source, $connector, $identifier);
	}

	/**
	 * @return array<PropertyStatus|ChannelStatus>
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
			'statuses' => array_map(
				static fn (PropertyStatus|ChannelStatus $status): array => $status->toArray(),
				$this->getStatuses(),
			),
		]);
	}

}
