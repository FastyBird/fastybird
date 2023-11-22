<?php declare(strict_types = 1);

/**
 * ConnectionManager.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Devices
 * @since          1.0.0
 *
 * @date           18.10.23
 */

namespace FastyBird\Connector\Virtual\Drivers;

use FastyBird\Connector\Virtual\Exceptions;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use Nette;
use Throwable;
use function array_key_exists;
use function sprintf;

/**
 * API connections manager
 *
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Devices
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DriversManager
{

	use Nette\SmartObject;

	/** @var array<string, Driver> */
	private array $drivers = [];

	/**
	 * @param array<DriverFactory> $driversFactories
	 */
	public function __construct(private readonly array $driversFactories)
	{
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getDriver(MetadataDocuments\DevicesModule\Device $device): Driver
	{
		if (!array_key_exists($device->getId()->toString(), $this->drivers)) {
			foreach ($this->driversFactories as $factory) {
				if ($device->getType() === $factory::DEVICE_TYPE) {
					$driver = $factory->create($device);

					$this->drivers[$device->getId()->toString()] = $driver;
				}
			}
		}

		if (!array_key_exists($device->getId()->toString(), $this->drivers)) {
			throw new Exceptions\InvalidState(sprintf('Driver for device: %s could not be created', $device::class));
		}

		return $this->drivers[$device->getId()->toString()];
	}

	public function __destruct()
	{
		foreach ($this->drivers as $key => $driver) {
			try {
				$driver->disconnect();
			} catch (Throwable) {
				// Just ignore
			}

			unset($this->drivers[$key]);
		}
	}

}
