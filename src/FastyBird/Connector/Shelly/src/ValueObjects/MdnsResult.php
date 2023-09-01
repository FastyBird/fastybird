<?php declare(strict_types = 1);

/**
 * MdnsResult.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     ValueObjects
 * @since          1.0.0
 *
 * @date           14.07.22
 */

namespace FastyBird\Connector\Shelly\ValueObjects;

use Orisai\ObjectMapper;

/**
 * mDNS search result
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     ValueObjects
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class MdnsResult implements ObjectMapper\MappedObject
{

	/**
	 * @param array<string, string|int|float|null> $data
	 */
	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $address,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $name,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\AnyOf([
				new ObjectMapper\Rules\StringValue(notEmpty: true),
				new ObjectMapper\Rules\IntValue(),
				new ObjectMapper\Rules\FloatValue(),
				new ObjectMapper\Rules\NullValue(),
			]),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
		)]
		private readonly array $data,
	)
	{
	}

	public function getAddress(): string
	{
		return $this->address;
	}

	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @return array<string, string|int|float|null>
	 */
	public function getData(): array
	{
		return $this->data;
	}

	/**
	 * @return array<string, string|array<string, string|int|float|null>>
	 */
	public function __serialize(): array
	{
		return [
			'address' => $this->getAddress(),
			'name' => $this->getName(),
			'data' => $this->getData(),
		];
	}

}
