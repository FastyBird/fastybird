<?php declare(strict_types = 1);

/**
 * Toggle.php
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
use FastyBird\Library\Bootstrap\ObjectMapper as BootstrapObjectMapper;
use Orisai\ObjectMapper;
use stdClass;

/**
 * Toggle control capability state
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ToggleState implements State
{

	public function __construct(
		#[BootstrapObjectMapper\Rules\ConsistenceEnumValue(class: Types\TogglePayload::class)]
		#[ObjectMapper\Modifiers\FieldName(Types\Protocol::TOGGLE_STATE)]
		private readonly Types\TogglePayload $toggleState,
		#[ObjectMapper\Rules\AnyOf([
			new BootstrapObjectMapper\Rules\ConsistenceEnumValue(class: Types\StartupPayload::class),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName(Types\Protocol::STARTUP)]
		private readonly Types\StartupPayload|null $startup = null,
	)
	{
	}

	public function getType(): Types\Capability
	{
		return Types\Capability::get(Types\Capability::TOGGLE);
	}

	public function getStartup(): Types\StartupPayload|null
	{
		return $this->startup;
	}

	public function getProtocols(): array
	{
		return [
			Types\Protocol::TOGGLE_STATE => $this->toggleState,
			Types\Protocol::STARTUP => $this->getStartup(),
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'value' => $this->toggleState->getValue(),
			'startup' => $this->getStartup()?->getValue(),
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->{Types\Protocol::TOGGLE_STATE} = $this->toggleState->getValue();

		if ($this->getStartup() !== null) {
			$json->{Types\Protocol::STARTUP} = $this->getStartup()->getValue();
		}

		return $json;
	}

}
