<?php declare(strict_types = 1);

/**
 * FindActions.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModuleAutomator!
 * @subpackage     Queries
 * @since          1.0.0
 *
 * @date           04.04.20
 */

namespace FastyBird\Automator\DevicesModule\Queries\Entities;

use Doctrine\ORM;
use FastyBird\Module\Triggers\Entities as TriggersEntities;
use FastyBird\Module\Triggers\Queries as TriggersQueries;
use Ramsey\Uuid;

/**
 * Find action entities query
 *
 * @template T of TriggersEntities\Actions\Action
 * @extends TriggersQueries\Entities\FindActions<T>
 *
 * @package        FastyBird:DevicesModuleAutomator!
 * @subpackage     Queries
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindActions extends TriggersQueries\Entities\FindActions
{

	public function forDevice(Uuid\UuidInterface $device): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($device): void {
			$qb->andWhere('a.device = :device')->setParameter('device', $device, Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	public function forChannel(Uuid\UuidInterface $channel): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($channel): void {
			$qb->andWhere('a.channel = :channel')
				->setParameter('channel', $channel, Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	public function forProperty(Uuid\UuidInterface $property): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($property): void {
			$qb->andWhere('a.property = :property')
				->setParameter('property', $property, Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

}
