<?php declare(strict_types = 1);

/**
 * Detect.php
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
use Orisai\ObjectMapper;
use stdClass;

/**
 * State detection capability state
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Detect implements State
{

	public function __construct(
		#[ObjectMapper\Rules\BoolValue()]
		#[ObjectMapper\Modifiers\FieldName(Types\Protocol::DETECT)]
		private readonly bool $detect,
	)
	{
	}

	public function getType(): Types\Capability
	{
		return Types\Capability::get(Types\Capability::DETECT);
	}

	public function getProtocols(): array
	{
		return [
			Types\Protocol::DETECT => $this->detect,
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'value' => $this->detect,
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->{Types\Protocol::DETECT} = $this->detect;

		return $json;
	}

}
