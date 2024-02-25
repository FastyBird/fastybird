<?php declare(strict_types = 1);

/**
 * Temperature.php
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
 * Temperature detection capability state
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class Temperature implements State
{

	public function __construct(
		#[ObjectMapper\Rules\FloatValue(min: -40, max: 80)]
		#[ObjectMapper\Modifiers\FieldName(Types\Protocol::TEMPERATURE->value)]
		private float $temperature,
	)
	{
	}

	public function getType(): Types\Capability
	{
		return Types\Capability::TEMPERATURE;
	}

	public function getProtocols(): array
	{
		return [
			Types\Protocol::TEMPERATURE->value => $this->temperature,
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'value' => $this->temperature,
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->{Types\Protocol::TEMPERATURE->value} = $this->temperature;

		return $json;
	}

}
