<?php declare(strict_types = 1);

/**
 * DigitalActuator.php
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
 * Digital actuator widget entity hydrator
 *
 * @extends Widget<Entities\Widgets\DigitalActuator>
 *
 * @package        FastyBird:UIModule!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DigitalActuator extends Widget
{

	public function getEntityName(): string
	{
		return Entities\Widgets\DigitalActuator::class;
	}

}
