<?php declare(strict_types = 1);

/**
 * DeviceInfo.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Properties
 * @since          0.37.0
 *
 * @date           19.07.22
 */

namespace FastyBird\ShellyConnector\Entities\Messages;

use FastyBird\ShellyConnector\Types;
use Ramsey\Uuid;
use function array_merge;

/**
 * Device info message entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Properties
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceInfo extends Device
{

	public function __construct(
		Types\MessageSource $source,
		Uuid\UuidInterface $connector,
		string $identifier,
		string $ipAddress,
		string $type,
		private readonly string $macAddress,
		private readonly bool $authEnabled,
		private readonly string $firmwareVersion,
	)
	{
		parent::__construct($source, $connector, $identifier, $type, $ipAddress);
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
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'mac_address' => $this->getMacAddress(),
			'auth_enabled' => $this->isAuthEnabled(),
			'firmware_version' => $this->getFirmwareVersion(),
		]);
	}

}
