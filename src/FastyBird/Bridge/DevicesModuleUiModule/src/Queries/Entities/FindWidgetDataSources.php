<?php declare(strict_types = 1);

/**
 * FindWidgetDataSources.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModuleUiModuleBridge!
 * @subpackage     Queries
 * @since          1.0.0
 *
 * @date           06.08.24
 */

namespace FastyBird\Bridge\DevicesModuleUiModule\Queries\Entities;

use Doctrine\ORM;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Ui\Entities as UiEntities;
use FastyBird\Module\Ui\Queries as UiQueries;
use Ramsey\Uuid;

/**
 * Find widget data sources entities query
 *
 * @template T of UiEntities\Widgets\DataSources\DataSource
 * @extends  UiQueries\Entities\FindWidgetDataSources<T>
 *
 * @package          FastyBird:DevicesModuleUiModuleBridge!
 * @subpackage       Queries
 * @author           Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindWidgetDataSources extends UiQueries\Entities\FindWidgetDataSources
{

	public function forProperty(DevicesEntities\Property $property): void
	{
		$this->select[] = static function (ORM\QueryBuilder $qb): void {
			$qb->join('d.property', 'property');
		};

		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($property): void {
			$qb->andWhere('property.id = :property')->setParameter(
				'property',
				$property->getId(),
				Uuid\Doctrine\UuidBinaryType::NAME,
			);
		};
	}

}
