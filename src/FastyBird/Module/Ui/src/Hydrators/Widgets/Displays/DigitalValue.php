<?php declare(strict_types = 1);

/**
 * DigitalValue.php
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
 * Digital value widget display entity hydrator
 *
 * @extends Display<Entities\Widgets\Displays\DigitalValue>
 *
 * @package        FastyBird:UIModule!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DigitalValue extends Display
{

	public function getEntityName(): string
	{
		return Entities\Widgets\Displays\DigitalValue::class;
	}

}
