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
use function strtolower;

/**
 * mDNS search result
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     ValueObjects
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class MdnsResult implements ObjectMapper\MappedObject
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $address,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $name,
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
	 * @return array<string, string|array<string, string|int|float|null>>
	 */
	public function __serialize(): array
	{
		return [
			'address' => $this->getAddress(),
			'name' => strtolower($this->getName()),
		];
	}

}
