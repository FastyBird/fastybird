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

namespace FastyBird\Connector\Shelly\Entities\API\Gen1;

use FastyBird\Connector\Shelly\Entities\API\Entity;
use Nette;

/**
 * Generation 1 device information entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceInformation implements Entity
{

	use Nette\SmartObject;

	public function __construct(
		private readonly string $type,
		private readonly string $mac,
		private readonly bool $auth,
		private readonly string $fw,
		private readonly bool $longid,
	)
	{
	}

	public function getModel(): string
	{
		return $this->type;
	}

	public function getMacAddress(): string
	{
		return $this->mac;
	}

	public function hasAuthentication(): bool
	{
		return $this->auth;
	}

	public function getFirmware(): string
	{
		return $this->fw;
	}

	public function hasLongIdentifier(): bool
	{
		return $this->longid;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'model' => $this->getModel(),
			'mac_address' => $this->getMacAddress(),
			'has_authentication' => $this->hasAuthentication(),
			'firmware' => $this->getFirmware(),
			'long_identifier' => $this->hasLongIdentifier(),
		];
	}

}
