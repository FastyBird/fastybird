<?php declare(strict_types = 1);

/**
 * ColorTemperature.php
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
use Orisai\ObjectMapper;
use stdClass;

/**
 * Color temperature control capability state
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ColorTemperature implements Status, ObjectMapper\MappedObject
{

	public function __construct(
		#[ObjectMapper\Rules\IntValue(min: 0, max: 100, unsigned: true)]
		private readonly int $colorTemperature,
	)
	{
	}

	public function getType(): Types\Capability
	{
		return Types\Capability::get(Types\Capability::COLOR_TEMPERATURE);
	}

	public function getName(): string|null
	{
		return null;
	}

	public function getValue(): int
	{
		return $this->colorTemperature;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			$this->getType()->getValue() => $this->getValue(),
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->colorTemperature = $this->getValue();

		return $json;
	}

}
