<?php declare(strict_types = 1);

/**
 * Startup.php
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
use FastyBird\Library\Bootstrap\ObjectMapper as BootstrapObjectMapper;
use Orisai\ObjectMapper;
use stdClass;

/**
 * Power on state (Power Supply) capability state
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Startup implements Status
{

	public function __construct(
		#[BootstrapObjectMapper\Rules\ConsistenceEnumValue(class: Types\StartupPayload::class)]
		#[ObjectMapper\Modifiers\FieldName(Types\Protocol::STARTUP)]
		private readonly Types\StartupPayload $value,
	)
	{
	}

	public function getType(): Types\Capability
	{
		return Types\Capability::get(Types\Capability::STARTUP);
	}

	public function getValue(): Types\StartupPayload
	{
		return $this->value;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'value' => $this->getValue()->getValue(),
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->{Types\Protocol::STARTUP} = $this->getValue()->getValue();

		return $json;
	}

}
