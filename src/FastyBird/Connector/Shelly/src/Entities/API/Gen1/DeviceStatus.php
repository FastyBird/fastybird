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
 * @date           20.12.22
 */

namespace FastyBird\Connector\Shelly\Entities\API\Gen1;

use FastyBird\Connector\Shelly\Entities;
use Nette;
use function array_map;

/**
 * Generation 1 device status entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceStatus implements Entities\API\Entity
{

	use Nette\SmartObject;

	/**
	 * @param array<int, DeviceRelayStatus> $relays
	 * @param array<int, DeviceRollerStatus> $rollers
	 * @param array<int, DeviceInputStatus> $inputs
	 * @param array<int, DeviceLightStatus> $lights
	 * @param array<int, DeviceMeterStatus> $meters
	 * @param array<int, DeviceEmeterStatus> $emeters
	 */
	public function __construct(
		private readonly array $relays = [],
		private readonly array $rollers = [],
		private readonly array $inputs = [],
		private readonly array $lights = [],
		private readonly array $meters = [],
		private readonly array $emeters = [],
	)
	{
	}

	/**
	 * @return array<int, DeviceRelayStatus>
	 */
	public function getRelays(): array
	{
		return $this->relays;
	}

	/**
	 * @return array<int, DeviceRollerStatus>
	 */
	public function getRollers(): array
	{
		return $this->rollers;
	}

	/**
	 * @return array<int, DeviceInputStatus>
	 */
	public function getInputs(): array
	{
		return $this->inputs;
	}

	/**
	 * @return array<int, DeviceLightStatus>
	 */
	public function getLights(): array
	{
		return $this->lights;
	}

	/**
	 * @return array<int, DeviceMeterStatus>
	 */
	public function getMeters(): array
	{
		return $this->meters;
	}

	/**
	 * @return array<int, DeviceEmeterStatus>
	 */
	public function getEmeters(): array
	{
		return $this->emeters;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'relays' => array_map(
				static fn (DeviceRelayStatus $status): array => $status->toArray(),
				$this->getRelays(),
			),
			'rollers' => array_map(
				static fn (DeviceRollerStatus $status): array => $status->toArray(),
				$this->getRollers(),
			),
			'inputs' => array_map(
				static fn (DeviceInputStatus $status): array => $status->toArray(),
				$this->getInputs(),
			),
			'lights' => array_map(
				static fn (DeviceLightStatus $status): array => $status->toArray(),
				$this->getLights(),
			),
			'meters' => array_map(
				static fn (DeviceMeterStatus $status): array => $status->toArray(),
				$this->getMeters(),
			),
			'emeters' => array_map(
				static fn (DeviceEmeterStatus $status): array => $status->toArray(),
				$this->getEmeters(),
			),
		];
	}

}
