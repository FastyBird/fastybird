<?php declare(strict_types = 1);

/**
 * TimeCondition.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Hydrators
 * @since          0.1.0
 *
 * @date           04.04.20
 */

namespace FastyBird\Module\Triggers\Hydrators\Conditions;

use DateTimeInterface;
use FastyBird\JsonApi\Exceptions as JsonApiExceptions;
use FastyBird\Module\Triggers\Entities;
use Fig\Http\Message\StatusCodeInterface;
use IPub\JsonAPIDocument;
use Nette\Utils;
use function in_array;
use function is_array;
use function is_scalar;

/**
 * Time condition entity hydrator
 *
 * @extends Condition<Entities\Conditions\TimeCondition>
 *
 * @package         FastyBird:TriggersModule!
 * @subpackage      Hydrators
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class TimeCondition extends Condition
{

	/** @var Array<int|string, string> */
	protected array $attributes = [
		'time',
		'days',
		'enabled',
	];

	public function getEntityName(): string
	{
		return Entities\Conditions\TimeCondition::class;
	}

	/**
	 * @throws JsonApiExceptions\JsonApi
	 */
	protected function hydrateTimeAttribute(
		JsonAPIDocument\Objects\IStandardObject $attributes,
	): DateTimeInterface
	{
		// Condition time have to be set
		if (!is_scalar($attributes->get('time')) || !$attributes->has('time')) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				$this->translator->translate('//triggers-module.base.messages.missingAttribute.heading'),
				$this->translator->translate('//triggers-module.base.messages.missingAttribute.message'),
				[
					'pointer' => '/data/attributes/time',
				],
			);
		}

		$date = Utils\DateTime::createFromFormat(DateTimeInterface::ATOM, (string) $attributes->get('time'));

		if (
			!$date instanceof DateTimeInterface
			|| $date->format(DateTimeInterface::ATOM) !== $attributes->get('time')
		) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				$this->translator->translate('//triggers-module.conditions.messages.invalidTime.heading'),
				$this->translator->translate('//triggers-module.conditions.messages.invalidTime.message'),
				[
					'pointer' => '/data/attributes/time',
				],
			);
		}

		return $date;
	}

	/**
	 * @return Array<int>
	 *
	 * @throws JsonApiExceptions\JsonApi
	 */
	protected function hydrateDaysAttribute(
		JsonAPIDocument\Objects\IStandardObject $attributes,
	): array
	{
		// Condition days have to be set
		if (!$attributes->has('days')) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				$this->translator->translate('//triggers-module.base.messages.missingAttribute.heading'),
				$this->translator->translate('//triggers-module.base.messages.missingAttribute.message'),
				[
					'pointer' => '/data/attributes/days',
				],
			);
		} elseif (!is_array($attributes->get('days'))) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				$this->translator->translate('//triggers-module.conditions.messages.invalidDays.heading'),
				$this->translator->translate('//triggers-module.conditions.messages.invalidDays.message'),
				[
					'pointer' => '/data/attributes/days',
				],
			);
		}

		$days = [];

		foreach ($attributes->get('days') as $day) {
			if (in_array($day, [1, 2, 3, 4, 5, 6, 7], true)) {
				$days[] = $day;
			}
		}

		return $days;
	}

}
