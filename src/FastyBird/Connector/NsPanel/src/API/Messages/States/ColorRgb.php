<?php declare(strict_types = 1);

/**
 * ColorRgb.php
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
use Orisai\ObjectMapper;
use stdClass;

/**
 * Color control capability state
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class ColorRgb implements State
{

	public function __construct(
		#[ObjectMapper\Rules\IntValue(min: 0, max: 255, unsigned: true)]
		#[ObjectMapper\Modifiers\FieldName(Types\Attribute::COLOR_RED->value)]
		private int $red,
		#[ObjectMapper\Rules\IntValue(min: 0, max: 255, unsigned: true)]
		#[ObjectMapper\Modifiers\FieldName(Types\Attribute::COLOR_GREEN->value)]
		private int $green,
		#[ObjectMapper\Rules\IntValue(min: 0, max: 255, unsigned: true)]
		#[ObjectMapper\Modifiers\FieldName(Types\Attribute::COLOR_BLUE->value)]
		private int $blue,
	)
	{
	}

	public function getType(): Types\Capability
	{
		return Types\Capability::COLOR_RGB;
	}

	public function getState(): array
	{
		return [
			Types\Attribute::COLOR_RED->value => $this->red,
			Types\Attribute::COLOR_GREEN->value => $this->green,
			Types\Attribute::COLOR_BLUE->value => $this->blue,
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'red' => $this->red,
			'green' => $this->green,
			'blue' => $this->blue,
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->{Types\Attribute::COLOR_RED->value} = $this->red;
		$json->{Types\Attribute::COLOR_GREEN->value} = $this->green;
		$json->{Types\Attribute::COLOR_BLUE->value} = $this->blue;

		return $json;
	}

}
