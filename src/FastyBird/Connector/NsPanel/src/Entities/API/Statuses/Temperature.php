<?php declare(strict_types = 1);

/**
 * Temperature.php
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
use Nette;
use stdClass;

/**
 * Temperature detection capability state
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Temperature implements Status
{

	use Nette\SmartObject;

	public function __construct(private readonly float $temperature)
	{
	}

	public function getType(): Types\Capability
	{
		return Types\Capability::get(Types\Capability::TEMPERATURE);
	}

	public function getName(): string|null
	{
		return null;
	}

	public function getValue(): float
	{
		return $this->temperature;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'value' => $this->getValue(),
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->temperature = $this->getValue();

		return $json;
	}

}
