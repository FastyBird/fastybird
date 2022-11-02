<?php declare(strict_types = 1);

/**
 * FindConditionsQuery.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Queries
 * @since          0.1.0
 *
 * @date           04.04.20
 */

namespace FastyBird\Module\Triggers\Queries;

use Closure;
use Doctrine\ORM;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Exceptions;
use IPub\DoctrineOrmQuery;
use Ramsey\Uuid;

/**
 * Find conditions entities query
 *
 * @package          FastyBird:TriggersModule!
 * @subpackage       Queries
 *
 * @author           Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @phpstan-extends DoctrineOrmQuery\QueryObject<Entities\Conditions\ICondition>
 */
class FindConditionsQuery extends DoctrineOrmQuery\QueryObject
{

	/** @var Closure[] */
	private array $filter = [];

	/** @var Closure[] */
	private array $select = [];

	/**
	 * @param Uuid\UuidInterface $id
	 *
	 * @return void
	 */
	public function byId(Uuid\UuidInterface $id): void
	{
		$this->filter[] = function (ORM\QueryBuilder $qb) use ($id): void {
			$qb->andWhere('c.id = :id')->setParameter('id', $id, Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	/**
	 * @param Entities\Triggers\ITrigger $trigger
	 *
	 * @return void
	 */
	public function forTrigger(Entities\Triggers\ITrigger $trigger): void
	{
		$this->filter[] = function (ORM\QueryBuilder $qb) use ($trigger): void {
			$qb->andWhere('trigger.id = :trigger')
				->setParameter('trigger', $trigger->getId(), Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	/**
	 * @param Uuid\UuidInterface $device
	 *
	 * @return void
	 */
	public function forDevice(Uuid\UuidInterface $device): void
	{
		$this->filter[] = function (ORM\QueryBuilder $qb) use ($device): void {
			$qb->andWhere('cdc.device = :device')->setParameter('device', $device, Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	/**
	 * @param Uuid\UuidInterface $channel
	 *
	 * @return void
	 */
	public function forChannel(Uuid\UuidInterface $channel): void
	{
		$this->filter[] = function (ORM\QueryBuilder $qb) use ($channel): void {
			$qb->andWhere('cdc.channel = :channel')
				->setParameter('channel', $channel, Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	/**
	 * @param Uuid\UuidInterface $property
	 *
	 * @return void
	 */
	public function forProperty(Uuid\UuidInterface $property): void
	{
		$this->filter[] = function (ORM\QueryBuilder $qb) use ($property): void {
			$qb->andWhere('cdc.property = :property')
				->setParameter('property', $property, Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	/**
	 * @param string $value
	 * @param string $operator
	 *
	 * @return void
	 */
	public function withPropertyValue(
		string $value,
		string $operator = MetadataTypes\TriggerConditionOperatorType::OPERATOR_VALUE_EQUAL
	): void {
		if (!MetadataTypes\TriggerConditionOperatorType::isValidValue($operator)) {
			throw new Exceptions\InvalidArgumentException('Invalid operator given');
		}

		$this->filter[] = function (ORM\QueryBuilder $qb) use ($operator): void {
			$qb->andWhere('cdc.operator = :operator')->setParameter('operator', $operator);
		};

		$this->filter[] = function (ORM\QueryBuilder $qb) use ($value): void {
			$qb->andWhere('cdc.operand = :operand')->setParameter('operand', $value);
		};
	}

	/**
	 * @param float $value
	 * @param float|null $previousValue
	 *
	 * @return void
	 */
	public function byValue(float $value, ?float $previousValue = null): void
	{
		$this->filter[] = function (ORM\QueryBuilder $qb) use ($value, $previousValue): void {
			if ($previousValue !== null) {
				$qb
					->andWhere(
						'(previousValue <= cdc.operand AND cdc.operand < :value AND cdc.operator = :operatorAbove)'
						. ' OR '
						. '(previousValue >= cdc.operand AND cdc.operand > :value AND cdc.operator = :operatorBelow)'
						. ' OR '
						. '(previousValue <> cdc.operand AND cdc.operand = :value AND cdc.operator = :operatorEqual)'
					)
					->setParameter('value', $value)
					->setParameter('previousValue', $previousValue)
					->setParameter('operatorAbove', MetadataTypes\TriggerConditionOperatorType::OPERATOR_VALUE_ABOVE)
					->setParameter('operatorBelow', MetadataTypes\TriggerConditionOperatorType::OPERATOR_VALUE_BELOW)
					->setParameter('operatorEqual', MetadataTypes\TriggerConditionOperatorType::OPERATOR_VALUE_EQUAL);

			} else {
				$qb
					->andWhere(
						'(cdc.operand < :value AND cdc.operator = :operatorAbove)'
						. ' OR '
						. '(cdc.operand > :value AND cdc.operator = :operatorBelow)'
						. ' OR '
						. '(cdc.operand = :value AND cdc.operator = :operatorEqual)'
					)
					->setParameter('value', $value)
					->setParameter('operatorAbove', MetadataTypes\TriggerConditionOperatorType::OPERATOR_VALUE_ABOVE)
					->setParameter('operatorBelow', MetadataTypes\TriggerConditionOperatorType::OPERATOR_VALUE_BELOW)
					->setParameter('operatorEqual', MetadataTypes\TriggerConditionOperatorType::OPERATOR_VALUE_EQUAL);
			}
		};
	}

	/**
	 * @param float $value
	 * @param float $previousValue
	 *
	 * @return void
	 */
	public function byValueAbove(float $value, float $previousValue): void
	{
		$this->filter[] = function (ORM\QueryBuilder $qb) use ($value, $previousValue): void {
			$qb
				->andWhere('cdc.operand >= :previousValue AND cdc.operand < :value AND cdc.operator = :operator')
				->setParameter('value', $value)
				->setParameter('previousValue', $previousValue)
				->setParameter('operator', MetadataTypes\TriggerConditionOperatorType::OPERATOR_VALUE_ABOVE);
		};
	}

	/**
	 * @param float $value
	 * @param float $previousValue
	 *
	 * @return void
	 */
	public function byValueBelow(float $value, float $previousValue): void
	{
		$this->filter[] = function (ORM\QueryBuilder $qb) use ($value, $previousValue): void {
			$qb
				->andWhere('cdc.operand <= :previousValue AND cdc.operand > :value AND cdc.operator = :operator')
				->setParameter('value', $value)
				->setParameter('previousValue', $previousValue)
				->setParameter('operator', MetadataTypes\TriggerConditionOperatorType::OPERATOR_VALUE_BELOW);
		};
	}

	/**
	 * @return void
	 */
	public function onlyEnabledTriggers(): void
	{
		$this->filter[] = function (ORM\QueryBuilder $qb): void {
			$qb->andWhere('trigger.enabled = :enabled')->setParameter('enabled', true);
		};
	}

	/**
	 * @param ORM\EntityRepository $repository
	 *
	 * @return ORM\QueryBuilder
	 *
	 * @phpstan-param ORM\EntityRepository<Entities\Conditions\ICondition> $repository
	 */
	protected function doCreateQuery(ORM\EntityRepository $repository): ORM\QueryBuilder
	{
		$qb = $this->createBasicDql($repository);

		foreach ($this->select as $modifier) {
			$modifier($qb);
		}

		return $qb;
	}

	/**
	 * @param ORM\EntityRepository $repository
	 *
	 * @return ORM\QueryBuilder
	 *
	 * @phpstan-param ORM\EntityRepository<Entities\Conditions\ICondition> $repository
	 */
	private function createBasicDql(ORM\EntityRepository $repository): ORM\QueryBuilder
	{
		if ($repository->getClassName() === Entities\Conditions\PropertyCondition::class) {
			$qb = $repository->createQueryBuilder('pc');
			$qb->join(Entities\Conditions\Condition::class, 'c', ORM\Query\Expr\Join::WITH, 'pc = c');

		} elseif (
			$repository->getClassName() === Entities\Conditions\ChannelPropertyCondition::class
			|| $repository->getClassName() === Entities\Conditions\DevicePropertyCondition::class
		) {
			$qb = $repository->createQueryBuilder('cdc');
			$qb->join(Entities\Conditions\Condition::class, 'c', ORM\Query\Expr\Join::WITH, 'cdc = c');
			$qb->join('c.trigger', 'trigger');

		} else {
			$qb = $repository->createQueryBuilder('c');
			$qb->addSelect('trigger');
			$qb->join('c.trigger', 'trigger');
		}

		foreach ($this->filter as $modifier) {
			$modifier($qb);
		}

		return $qb;
	}

	/**
	 * @param ORM\EntityRepository $repository
	 *
	 * @return ORM\QueryBuilder
	 *
	 * @phpstan-param ORM\EntityRepository<Entities\Conditions\ICondition> $repository
	 */
	protected function doCreateCountQuery(ORM\EntityRepository $repository): ORM\QueryBuilder
	{
		$qb = $this->createBasicDql($repository)->select('COUNT(c.id)');

		foreach ($this->select as $modifier) {
			$modifier($qb);
		}

		return $qb;
	}

}
