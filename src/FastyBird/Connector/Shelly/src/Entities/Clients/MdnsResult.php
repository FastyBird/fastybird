<?php declare(strict_types = 1);

/**
 * MdnsResult.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Properties
 * @since          1.0.0
 *
 * @date           14.07.22
 */

namespace FastyBird\Connector\Shelly\Entities\Clients;

use Nette;

/**
 * mDNS search result
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Properties
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class MdnsResult
{

	use Nette\SmartObject;

	/**
	 * @param array<string, string|int|float|null> $data
	 */
	public function __construct(
		private readonly string $address,
		private readonly string $name,
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
