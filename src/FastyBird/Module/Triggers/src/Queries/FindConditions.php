<?php declare(strict_types = 1);

/**
 * FindConditions.php
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
 * @extends DoctrineOrmQuery\QueryObject<Entities\Conditions\Condition>
 *
 * @package          FastyBird:TriggersModule!
 * @subpackage       Queries
 * @author           Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindConditions extends DoctrineOrmQuery\QueryObject
{

	/** @var Array<Closure(ORM\QueryBuilder $qb): void> */
	private array $filter = [];

	/** @var Array<Closure(ORM\QueryBuilder $qb): void> */
	private array $select = [];

	public function byId(Uuid\UuidInterface $id): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($id): void {
			$qb->andWhere('c.id = :id')->setParameter('id', $id, Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	public function forTrigger(Entities\Triggers\Trigger $trigger): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($trigger): void {
			$qb->andWhere('trigger.id = :trigger')
				->setParameter('trigger', $trigger->getId(), Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	public function forDevice(Uuid\UuidInterface $device): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($device): void {
			$qb->andWhere('cdc.device = :device')->setParameter('device', $device, Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	public function forChannel(Uuid\UuidInterface $channel): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($channel): void {
			$qb->andWhere('cdc.channel = :channel')
				->setParameter('channel', $channel, Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	public function forProperty(Uuid\UuidInterface $property): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($property): void {
			$qb->andWhere('cdc.property = :property')
				->setParameter('property', $property, Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function withPropertyValue(
		string $value,
		string $operator = MetadataTypes\TriggerConditionOperator::OPERATOR_VALUE_EQUAL,
	): void
	{
		if (!MetadataTypes\TriggerConditionOperator::isValidValue($operator)) {
			throw new Exceptions\InvalidArgument('Invalid operator given');
		}

		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($operator): void {
			$qb->andWhere('cdc.operator = :operator')->setParameter('operator', $operator);
		};

		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($value): void {
			$qb->andWhere('cdc.operand = :operand')->setParameter('operand', $value);
		};
	}

	public function byValue(float $value, float|null $previousValue = null): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($value, $previousValue): void {
			if ($previousValue !== null) {
				$qb
					->andWhere(
						'(previousValue <= cdc.operand AND cdc.operand < :value AND cdc.operator = :operatorAbove)'
						. ' OR '
						. '(previousValue >= cdc.operand AND cdc.operand > :value AND cdc.operator = :operatorBelow)'
						. ' OR '
						. '(previousValue <> cdc.operand AND cdc.operand = :value AND cdc.operator = :operatorEqual)',
					)
					->setParameter('value', $value)
					->setParameter('previousValue', $previousValue)
					->setParameter('operatorAbove', MetadataTypes\TriggerConditionOperator::OPERATOR_VALUE_ABOVE)
					->setParameter('operatorBelow', MetadataTypes\TriggerConditionOperator::OPERATOR_VALUE_BELOW)
					->setParameter('operatorEqual', MetadataTypes\TriggerConditionOperator::OPERATOR_VALUE_EQUAL);

			} else {
				$qb
					->andWhere(
						'(cdc.operand < :value AND cdc.operator = :operatorAbove)'
						. ' OR '
						. '(cdc.operand > :value AND cdc.operator = :operatorBelow)'
						. ' OR '
						. '(cdc.operand = :value AND cdc.operator = :operatorEqual)',
					)
					->setParameter('value', $value)
					->setParameter('operatorAbove', MetadataTypes\TriggerConditionOperator::OPERATOR_VALUE_ABOVE)
					->setParameter('operatorBelow', MetadataTypes\TriggerConditionOperator::OPERATOR_VALUE_BELOW)
					->setParameter('operatorEqual', MetadataTypes\TriggerConditionOperator::OPERATOR_VALUE_EQUAL);
			}
		};
	}

	public function byValueAbove(float $value, float $previousValue): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($value, $previousValue): void {
			$qb
				->andWhere('cdc.operand >= :previousValue AND cdc.operand < :value AND cdc.operator = :operator')
				->setParameter('value', $value)
				->setParameter('previousValue', $previousValue)
				->setParameter('operator', MetadataTypes\TriggerConditionOperator::OPERATOR_VALUE_ABOVE);
		};
	}

	public function byValueBelow(float $value, float $previousValue): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($value, $previousValue): void {
			$qb
				->andWhere('cdc.operand <= :previousValue AND cdc.operand > :value AND cdc.operator = :operator')
				->setParameter('value', $value)
				->setParameter('previousValue', $previousValue)
				->setParameter('operator', MetadataTypes\TriggerConditionOperator::OPERATOR_VALUE_BELOW);
		};
	}

	public function onlyEnabledTriggers(): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb): void {
			$qb->andWhere('trigger.enabled = :enabled')->setParameter('enabled', true);
		};
	}

	/**
	 * @phpstan-param ORM\EntityRepository<Entities\Conditions\Condition> $repository
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
	 * @phpstan-param ORM\EntityRepository<Entities\Conditions\Condition> $repository
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
	 * @phpstan-param ORM\EntityRepository<Entities\Conditions\Condition> $repository
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
