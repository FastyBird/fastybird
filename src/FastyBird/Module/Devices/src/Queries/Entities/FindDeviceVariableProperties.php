<?php declare(strict_types = 1);

/**
 * FindDeviceVariableProperties.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Queries
 * @since          1.0.0
 *
 * @date           05.08.20
 */

namespace FastyBird\Module\Devices\Queries\Entities;

use Doctrine\ORM;
use FastyBird\Module\Devices\Entities;

/**
 * Find device variable properties entities query
 *
 * @template T of Entities\Devices\Properties\Variable
 * @extends  FindDeviceProperties<T>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Queries
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindDeviceVariableProperties extends FindDeviceProperties
{

	public function byValue(string $value): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($value): void {
			$qb->andWhere('p.value = :value')->setParameter('value', $value);
		};
	}

}
