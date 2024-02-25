<?php declare(strict_types = 1);

/**
 * ColorTemperature.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           09.07.23
 */

namespace FastyBird\Connector\NsPanel\API\Messages\States;

use FastyBird\Connector\NsPanel\Types;
use Orisai\ObjectMapper;
use stdClass;

/**
 * Color temperature control capability state
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class ColorTemperature implements State
{

	public function __construct(
		#[ObjectMapper\Rules\IntValue(min: 0, max: 100, unsigned: true)]
		#[ObjectMapper\Modifiers\FieldName(Types\Protocol::COLOR_TEMPERATURE->value)]
		private int $colorTemperature,
	)
	{
	}

	public function getType(): Types\Capability
	{
		return Types\Capability::COLOR_TEMPERATURE;
	}

	public function getProtocols(): array
	{
		return [
			Types\Protocol::COLOR_TEMPERATURE->value => $this->colorTemperature,
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
		$json->{Types\Protocol::COLOR_TEMPERATURE->value} = $this->colorTemperature;

		return $json;
	}

}
