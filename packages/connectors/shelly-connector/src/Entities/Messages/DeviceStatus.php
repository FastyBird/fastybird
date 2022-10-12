<?php declare(strict_types = 1);

/**
 * DeviceStatus.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Properties
 * @since          0.37.0
 *
 * @date           18.07.22
 */

namespace FastyBird\ShellyConnector\Entities\Messages;

use FastyBird\ShellyConnector\Types;
use Ramsey\Uuid;
use function array_map;
use function array_merge;

/**
 * Device status message entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Properties
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceStatus extends Device
{

	/**
	 * @param Array<ChannelStatus> $channels
	 */
	public function __construct(
		Types\MessageSource $source,
		Uuid\UuidInterface $connector,
		string $identifier,
		string $type,
		string $ipAddress,
		private array $channels,
	)
	{
		parent::__construct($source, $connector, $identifier, $type, $ipAddress);
	}

	/**
	 * @return Array<ChannelStatus>
	 */
	public function getChannels(): array
	{
		return $this->channels;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'channels' => array_map(
				static fn (ChannelStatus $channel): array => $channel->toArray(),
				$this->getChannels(),
			),
		]);
	}

}
