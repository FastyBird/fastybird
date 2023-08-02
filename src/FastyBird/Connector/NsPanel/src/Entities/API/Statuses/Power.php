<?php declare(strict_types = 1);

/**
 * Power.php
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
 * Power control capability state
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Power implements Status
{

	public function __construct(
		#[BootstrapObjectMapper\Rules\ConsistenceEnumValue(class: Types\PowerPayload::class)]
		#[ObjectMapper\Modifiers\FieldName(Types\Protocol::POWER_STATE)]
		private readonly Types\PowerPayload $value,
	)
	{
	}

	public function getType(): Types\Capability
	{
		return Types\Capability::get(Types\Capability::POWER);
	}

	public function getValue(): Types\PowerPayload
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
		$json->{Types\Protocol::POWER_STATE} = $this->getValue()->getValue();

		return $json;
	}

}
