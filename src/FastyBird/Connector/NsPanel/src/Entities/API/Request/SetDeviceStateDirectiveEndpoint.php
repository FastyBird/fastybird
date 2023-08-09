<?php declare(strict_types = 1);

/**
 * SetDeviceStateDirectiveEndpoint.php
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
use FastyBird\Library\Bootstrap\ObjectMapper as BootstrapObjectMapper;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use stdClass;
use function is_array;

/**
 * NS Panel requested set device state directive endpoint request definition
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class SetDeviceStateDirectiveEndpoint implements Entities\API\Entity
{

	/**
	 * @param array<string, string|array<string, string>> $tags
	 */
	public function __construct(
		#[BootstrapObjectMapper\Rules\UuidValue()]
		#[ObjectMapper\Modifiers\FieldName('third_serial_number')]
		private readonly Uuid\UuidInterface $thirdSerialNumber,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('serial_number')]
		private readonly string $serialNumber,
		#[ObjectMapper\Rules\ArrayOf(
			item: new ObjectMapper\Rules\AnyOf([
				new ObjectMapper\Rules\StringValue(),
				new ObjectMapper\Rules\ArrayOf(
					item: new ObjectMapper\Rules\StringValue(),
					key: new ObjectMapper\Rules\AnyOf([
						new ObjectMapper\Rules\StringValue(),
						new ObjectMapper\Rules\IntValue(),
					]),
				),
			]),
			key: new ObjectMapper\Rules\StringValue(),
		)]
		private readonly array $tags = [],
	)
	{
	}

	public function getThirdSerialNumber(): Uuid\UuidInterface
	{
		return $this->thirdSerialNumber;
	}

	public function getSerialNumber(): string
	{
		return $this->serialNumber;
	}

	/**
	 * @return array<string, string|array<string, string>>
	 */
	public function getTags(): array
	{
		return $this->tags;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'third_serial_number' => $this->getThirdSerialNumber()->toString(),
			'serial_number' => $this->getSerialNumber(),
			'tags' => $this->getTags(),
		];
	}

	public function toJson(): object
	{
		$tags = new stdClass();

		foreach ($this->getTags() as $name => $value) {
			if (is_array($value)) {
				$tags->{$name} = new stdClass();

				foreach ($value as $subName => $subValue) {
					$tags->{$name}->{$subName} = $subValue;
				}
			} else {
				$tags->{$name} = $value;
			}
		}

		$json = new stdClass();
		$json->third_serial_number = $this->getThirdSerialNumber()->toString();
		$json->serial_number = $this->getSerialNumber();
		$json->tags = $tags;

		return $json;
	}

}
