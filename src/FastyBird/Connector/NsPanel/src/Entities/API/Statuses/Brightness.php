<?php declare(strict_types = 1);

/**
 * Brightness.php
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

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Types;
use Nette;
use stdClass;

/**
 * Brightness control capability state
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Brightness implements Status
{

	use Nette\SmartObject;

	public function __construct(private readonly int $value)
	{
	}

	public function getType(): Types\Capability
	{
		return Types\Capability::get(Types\Capability::BRIGHTNESS);
	}

	public function getName(): string|null
	{
		return null;
	}

	public function getValue(): int
	{
		return $this->value;
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
		$json->{Types\Capability::BRIGHTNESS} = new stdClass();
		$json->{Types\Capability::BRIGHTNESS}->brightness = $this->getValue();

		return $json;
	}

}
