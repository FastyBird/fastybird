<?php declare(strict_types = 1);

/**
 * AnalogActuator.php
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

namespace FastyBird\Module\Ui\Hydrators\Widgets;

use FastyBird\Module\Ui\Entities;

/**
 * Analog actuator widget entity hydrator
 *
 * @extends Widget<Entities\Widgets\AnalogActuator>
 *
 * @package        FastyBird:UIModule!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class AnalogActuator extends Widget
{

	public function getEntityName(): string
	{
		return Entities\Widgets\AnalogActuator::class;
	}

}
