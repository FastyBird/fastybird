<?php declare(strict_types = 1);

/**
 * Startup.php
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
 * Power on state (Power Supply) capability state
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class Startup implements State
{

	public function __construct(
		#[ObjectMapper\Rules\BackedEnumValue(class: Types\Payloads\Startup::class)]
		#[ObjectMapper\Modifiers\FieldName(Types\Attribute::STARTUP->value)]
		private Types\Payloads\Startup $startup,
	)
	{
	}

	public function getType(): Types\Capability
	{
		return Types\Capability::STARTUP;
	}

	public function getState(): array
	{
		return [
			Types\Attribute::STARTUP->value => $this->startup,
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'value' => $this->startup->value,
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->{Types\Attribute::STARTUP->value} = $this->startup->value;

		return $json;
	}

}
