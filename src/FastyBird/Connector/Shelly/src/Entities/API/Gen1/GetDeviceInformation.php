<?php declare(strict_types = 1);

/**
 * GetDeviceInformation.php
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
use Orisai\ObjectMapper;

/**
 * Generation 1 device information entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class GetDeviceInformation implements Entities\API\Entity
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $type,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $mac,
		#[ObjectMapper\Rules\BoolValue(castBoolLike: true)]
		private readonly bool $auth,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $fw,
		#[ObjectMapper\Rules\IntValue(unsigned: true)]
		private readonly int $longid,
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

	public function getLongIdentifier(): int
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
			'long_identifier' => $this->getLongIdentifier(),
		];
	}

}
