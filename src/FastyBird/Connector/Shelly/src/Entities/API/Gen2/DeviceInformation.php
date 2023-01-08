<?php declare(strict_types = 1);

/**
 * DeviceInformation.php
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

namespace FastyBird\Connector\Shelly\Entities\API\Gen2;

use FastyBird\Connector\Shelly\Entities;
use Nette;

/**
 * Generation 2 device information entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceInformation implements Entities\API\Entity
{

	use Nette\SmartObject;

	public function __construct(
		private readonly string $id,
		private readonly string $mac,
		private readonly string $model,
		private readonly int $gen,
		private readonly string $fwId,
		private readonly string $ver,
		private readonly string $app,
		private readonly bool $authEn,
		private readonly string|null $authDomain,
	)
	{
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function getMacAddress(): string
	{
		return $this->mac;
	}

	public function getModel(): string
	{
		return $this->model;
	}

	public function getGeneration(): int
	{
		return $this->gen;
	}

	public function getFirmware(): string
	{
		return $this->fwId;
	}

	public function getVersion(): string
	{
		return $this->ver;
	}

	public function getApplication(): string
	{
		return $this->app;
	}

	public function hasAuthentication(): bool
	{
		return $this->authEn;
	}

	public function getAuthenticationDomain(): string|null
	{
		return $this->authDomain;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getId(),
			'mac_address' => $this->getMacAddress(),
			'model' => $this->getModel(),
			'generation' => $this->getGeneration(),
			'firmware' => $this->getFirmware(),
			'version' => $this->getVersion(),
			'application' => $this->getApplication(),
			'authentication' => $this->hasAuthentication(),
			'authentication_domain' => $this->getAuthenticationDomain(),
		];
	}

}
