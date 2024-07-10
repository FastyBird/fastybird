<?php declare(strict_types = 1);

/**
 * Gauge.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           26.05.20
 */

namespace FastyBird\Module\Ui\Hydrators\Widgets\Displays;

use FastyBird\Module\Ui\Entities;

/**
 * Gauge widget display entity hydrator
 *
 * @extends Display<Entities\Widgets\Display\Gauge>
 *
 * @package        FastyBird:UIModule!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Gauge extends Display
{

	/** @var array<int|string, string> */
	protected array $attributes = [
		'precision' => 'precision',
	];

	public function getEntityName(): string
	{
		return Entities\Widgets\Display\Gauge::class;
	}

}
