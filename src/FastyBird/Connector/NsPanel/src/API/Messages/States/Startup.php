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
use FastyBird\Library\Application\ObjectMapper as ApplicationObjectMapper;
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
final class Startup implements State
{

	public function __construct(
		#[ApplicationObjectMapper\Rules\ConsistenceEnumValue(class: Types\StartupPayload::class)]
		#[ObjectMapper\Modifiers\FieldName(Types\Protocol::STARTUP)]
		private readonly Types\StartupPayload $startup,
	)
	{
	}

	public function getType(): Types\Capability
	{
		return Types\Capability::get(Types\Capability::STARTUP);
	}

	public function getProtocols(): array
	{
		return [
			Types\Protocol::STARTUP => $this->startup,
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'value' => $this->startup->getValue(),
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->{Types\Protocol::STARTUP} = $this->startup->getValue();

		return $json;
	}

}
