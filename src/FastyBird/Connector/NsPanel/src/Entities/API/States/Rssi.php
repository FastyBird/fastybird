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

namespace FastyBird\Connector\NsPanel\Entities\API\States;

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
final class Rssi implements State
{

	public function __construct(
		#[ObjectMapper\Rules\IntValue(min: -200, max: 0, unsigned: false)]
		#[ObjectMapper\Modifiers\FieldName(Types\Protocol::RSSI)]
		private readonly int $rssi,
	)
	{
	}

	public function getType(): Types\Capability
	{
		return Types\Capability::get(Types\Capability::RSSI);
	}

	public function getProtocols(): array
	{
		return [
			Types\Protocol::RSSI => $this->rssi,
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'value' => $this->rssi,
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->{Types\Protocol::RSSI} = $this->rssi;

		return $json;
	}

}
