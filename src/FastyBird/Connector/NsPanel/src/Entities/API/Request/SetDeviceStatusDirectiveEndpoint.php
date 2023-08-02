<?php declare(strict_types = 1);

/**
 * SetDeviceStatusDirectiveEndpoint.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           09.07.23
 */

namespace FastyBird\Connector\NsPanel\Entities\API\Request;

use FastyBird\Connector\NsPanel\Entities;
use Orisai\ObjectMapper;
use stdClass;

/**
 * NS Panel requested set device status directive endpoint request definition
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class SetDeviceStatusDirectiveEndpoint implements Entities\API\Entity
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('third_serial_number')]
		private readonly string $thirdSerialNumber,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('serial_number')]
		private readonly string $serialNumber,
	)
	{
	}

	public function getThirdSerialNumber(): string
	{
		return $this->thirdSerialNumber;
	}

	public function getSerialNumber(): string
	{
		return $this->serialNumber;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'third_serial_number' => $this->getThirdSerialNumber(),
			'serial_number' => $this->getSerialNumber(),
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->third_serial_number = $this->getThirdSerialNumber();
		$json->serial_number = $this->getSerialNumber();

		return $json;
	}

}
