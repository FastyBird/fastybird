<?php declare(strict_types = 1);

/**
 * ColorTemperature.php
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
 * Color temperature control capability state
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ColorTemperature implements State
{

	public function __construct(
		#[ObjectMapper\Rules\IntValue(min: 0, max: 100, unsigned: true)]
		#[ObjectMapper\Modifiers\FieldName(Types\Protocol::COLOR_TEMPERATURE)]
		private readonly int $colorTemperature,
	)
	{
	}

	public function getType(): Types\Capability
	{
		return Types\Capability::get(Types\Capability::COLOR_TEMPERATURE);
	}

	public function getProtocols(): array
	{
		return [
			Types\Protocol::COLOR_TEMPERATURE => $this->colorTemperature,
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'value' => $this->colorTemperature,
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->{Types\Protocol::COLOR_TEMPERATURE} = $this->colorTemperature;

		return $json;
	}

}
