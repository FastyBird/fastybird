<?php declare(strict_types = 1);

/**
 * DeviceFactoryInfos.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           29.04.22
 */

namespace FastyBird\Connector\Tuya\Entities\API;

use Orisai\ObjectMapper;

/**
 * OpenAPI device factory information entity
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceFactoryInfos implements Entity
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $id,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly string|null $uuid,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly string|null $sn,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly string|null $mac,
	)
	{
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function getUuid(): string|null
	{
		return $this->uuid !== '' ? $this->uuid : null;
	}

	public function getMac(): string|null
	{
		return $this->mac !== '' ? $this->mac : null;
	}

	public function getSn(): string|null
	{
		return $this->sn !== '' ? $this->sn : null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getId(),
			'uuid' => $this->getUuid(),
			'sn' => $this->getSn(),
			'mac' => $this->getMac(),
		];
	}

}
