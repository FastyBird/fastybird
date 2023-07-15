<?php declare(strict_types = 1);

/**
 * ColorRgb.php
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
 * Color control capability state
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ColorRgb implements Status
{

	use Nette\SmartObject;

	public function __construct(
		private readonly int $red,
		private readonly int $green,
		private readonly int $blue,
	)
	{
	}

	public function getType(): Types\Capability
	{
		return Types\Capability::get(Types\Capability::COLOR_RGB);
	}

	public function getName(): string|null
	{
		return null;
	}

	public function getRed(): int
	{
		return $this->red;
	}

	public function getGreen(): int
	{
		return $this->green;
	}

	public function getBlue(): int
	{
		return $this->blue;
	}

	/**
	 * @return array<int>
	 */
	public function getValue(): array
	{
		return [
			$this->getRed(),
			$this->getGreen(),
			$this->getBlue(),
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'red' => $this->getRed(),
			'green' => $this->getGreen(),
			'blue' => $this->getBlue(),
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->{Types\Capability::COLOR_RGB} = new stdClass();
		$json->{Types\Capability::COLOR_RGB}->red = $this->getRed();
		$json->{Types\Capability::COLOR_RGB}->green = $this->getGreen();
		$json->{Types\Capability::COLOR_RGB}->blue = $this->getBlue();

		return $json;
	}

}
