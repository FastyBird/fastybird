<?php declare(strict_types = 1);

/**
 * DigitalSensor.php
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
 * Digital sensor widget entity hydrator
 *
 * @extends Widget<Entities\Widgets\DigitalSensor>
 *
 * @package        FastyBird:IOTApiModule!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DigitalSensor extends Widget
{

	public function getEntityName(): string
	{
		return Entities\Widgets\DigitalSensor::class;
	}

}
