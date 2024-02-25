<?php declare(strict_types = 1);

/**
 * Toggle.php
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
 * Toggle control capability state
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class ToggleState implements State
{

	public function __construct(
		#[ObjectMapper\Rules\BackedEnumValue(class: Types\Payloads\TogglePayload::class)]
		#[ObjectMapper\Modifiers\FieldName(Types\Protocol::TOGGLE_STATE->value)]
		private Types\Payloads\TogglePayload $toggleState,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BackedEnumValue(class: Types\Payloads\StartupPayload::class),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName(Types\Protocol::STARTUP->value)]
		private Types\Payloads\StartupPayload|null $startup = null,
	)
	{
	}

	public function getType(): Types\Capability
	{
		return Types\Capability::TOGGLE;
	}

	public function getStartup(): Types\Payloads\StartupPayload|null
	{
		return $this->startup;
	}

	public function getProtocols(): array
	{
		return [
			Types\Protocol::TOGGLE_STATE->value => $this->toggleState,
			Types\Protocol::STARTUP->value => $this->getStartup(),
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'value' => $this->toggleState->value,
			'startup' => $this->getStartup()?->value,
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->{Types\Protocol::TOGGLE_STATE->value} = $this->toggleState->value;

		if ($this->getStartup() !== null) {
			$json->{Types\Protocol::STARTUP->value} = $this->getStartup()->value;
		}

		return $json;
	}

}
