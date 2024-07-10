<?php declare(strict_types = 1);

/**
 * AnalogSensor.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           25.05.20
 */

namespace FastyBird\Module\Ui\Entities\Widgets;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Library\Application\Entities\Mapping as ApplicationMapping;
use FastyBird\Module\Ui\Entities;

#[ORM\Entity]
#[ApplicationMapping\DiscriminatorEntry(name: self::TYPE)]
class AnalogSensor extends Sensor
{

	public const TYPE = 'analog-sensor';

	public static function getType(): string
	{
		return self::TYPE;
	}

	public function getAllowedDisplayTypes(): array
	{
		return [
			Entities\Widgets\Display\ChartGraph::class,
			Entities\Widgets\Display\Gauge::class,
			Entities\Widgets\Display\AnalogValue::class,
		];
	}

}