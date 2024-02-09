<?php declare(strict_types = 1);

/**
 * Press.php
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
 * Press detection capability state
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Press implements State
{

	public function __construct(
		#[ApplicationObjectMapper\Rules\ConsistenceEnumValue(class: Types\PressPayload::class)]
		#[ObjectMapper\Modifiers\FieldName(Types\Protocol::PRESS)]
		private readonly Types\PressPayload $press,
	)
	{
	}

	public function getType(): Types\Capability
	{
		return Types\Capability::get(Types\Capability::PRESS);
	}

	public function getProtocols(): array
	{
		return [
			Types\Protocol::PRESS => $this->press,
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'value' => $this->press->getValue(),
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->{Types\Protocol::PRESS} = $this->press->getValue();

		return $json;
	}

}
