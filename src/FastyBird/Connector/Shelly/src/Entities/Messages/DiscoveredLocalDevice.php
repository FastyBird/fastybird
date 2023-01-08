<?php declare(strict_types = 1);

/**
 * DiscoveredLocalDevice.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           17.07.22
 */

namespace FastyBird\Connector\Shelly\Entities\Messages;

use FastyBird\Connector\Shelly\Types;
use Ramsey\Uuid;
use function array_map;
use function array_merge;
use function array_unique;
use const SORT_REGULAR;

/**
 * Device was found via mDNS discovery entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DiscoveredLocalDevice extends Device
{

	/** @var array<ChannelDescription> */
	private array $channels;

	/**
	 * @param array<ChannelDescription> $channels
	 */
	public function __construct(
		Types\MessageSource $source,
		Uuid\UuidInterface $connector,
		string $identifier,
		private readonly Types\DeviceGeneration $generation,
		private readonly string $ipAddress,
		private readonly string|null $domain,
		private readonly string $model,
		private readonly string $macAddress,
		private readonly bool $authEnabled,
		private readonly string $firmwareVersion,
		array $channels,
	)
	{
		parent::__construct($source, $connector, $identifier);

		$this->channels = array_unique($channels, SORT_REGULAR);
	}

	public function getGeneration(): Types\DeviceGeneration
	{
		return $this->generation;
	}

	public function getIpAddress(): string
	{
		return $this->ipAddress;
	}

	public function getDomain(): string|null
	{
		return $this->domain;
	}

	public function getModel(): string
	{
		return $this->model;
	}

	public function getMacAddress(): string
	{
		return $this->macAddress;
	}

	public function isAuthEnabled(): bool
	{
		return $this->authEnabled;
	}

	public function getFirmwareVersion(): string
	{
		return $this->firmwareVersion;
	}

	/**
	 * @return array<ChannelDescription>
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
			'generation' => $this->getGeneration()->getValue(),
			'ip_address' => $this->getIpAddress(),
			'domain' => $this->getDomain(),
			'model' => $this->getModel(),
			'mac_address' => $this->getMacAddress(),
			'auth_enabled' => $this->isAuthEnabled(),
			'firmware_version' => $this->getFirmwareVersion(),
			'channels' => array_map(
				static fn (ChannelDescription $channel): array => $channel->toArray(),
				$this->getChannels(),
			),
		]);
	}

}
