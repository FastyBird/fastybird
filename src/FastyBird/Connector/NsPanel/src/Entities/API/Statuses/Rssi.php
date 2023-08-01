<?php declare(strict_types = 1);

/**
 * Rssi.php
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

namespace FastyBird\Connector\NsPanel\Entities\API\Statuses;

use FastyBird\Connector\NsPanel\Types;
use Orisai\ObjectMapper;
use stdClass;

/**
 * Wireless signal strength detection capability state
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Rssi implements Status, ObjectMapper\MappedObject
{

	public function __construct(
		#[ObjectMapper\Rules\IntValue(min: -200, max: 0, unsigned: false)]
		private readonly int $rssi,
	)
	{
	}

	public function getType(): Types\Capability
	{
		return Types\Capability::get(Types\Capability::RSSI);
	}

	public function getName(): string|null
	{
		return null;
	}

	public function getValue(): int
	{
		return $this->rssi;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			$this->getType()->getValue() => $this->getValue(),
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->rssi = $this->getValue();

		return $json;
	}

}
