<?php declare(strict_types = 1);

/**
 * Battery.php
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
 * Remaining battery detection capability state
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Battery implements Status
{

	use Nette\SmartObject;

	public function __construct(private readonly int $battery)
	{
	}

	public function getType(): Types\Capability
	{
		return Types\Capability::get(Types\Capability::BATTERY);
	}

	public function getName(): string|null
	{
		return null;
	}

	public function getValue(): int
	{
		return $this->battery;
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
		$json->battery = $this->getValue();

		return $json;
	}

}
