<?php declare(strict_types = 1);

/**
 * PowerState.php
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
 * Power state control capability state
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class PowerState implements State
{

	public function __construct(
		#[ObjectMapper\Rules\BackedEnumValue(class: Types\Payloads\Power::class)]
		#[ObjectMapper\Modifiers\FieldName(Types\Attribute::POWER_STATE->value)]
		private Types\Payloads\Power $powerState,
	)
	{
	}

	public function getType(): Types\Capability
	{
		return Types\Capability::POWER;
	}

	public function getState(): array
	{
		return [
			Types\Attribute::POWER_STATE->value => $this->powerState,
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'value' => $this->powerState->value,
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->{Types\Attribute::POWER_STATE->value} = $this->powerState->value;

		return $json;
	}

}
