<?php declare(strict_types = 1);

/**
 * ChannelPropertyConditionHydrator.php
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

use FastyBird\JsonApi\Exceptions as JsonApiExceptions;
use FastyBird\Module\Triggers\Entities;
use Fig\Http\Message\StatusCodeInterface;
use IPub\JsonAPIDocument;
use Ramsey\Uuid;

/**
 * Channel property condition entity hydrator
 *
 * @package         FastyBird:TriggersModule!
 * @subpackage      Hydrators
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @phpstan-extends PropertyConditionHydrator<Entities\Conditions\IChannelPropertyCondition>
 */
final class ChannelPropertyConditionHydrator extends PropertyConditionHydrator
{

	/** @var string[] */
	protected array $attributes = [
		'device',
		'channel',
		'property',
		'operator',
		'operand',
		'enabled',
	];

	/**
	 * {@inheritDoc}
	 */
	public function getEntityName(): string
	{
		return Entities\Conditions\ChannelPropertyCondition::class;
	}

	/**
	 * @param JsonAPIDocument\Objects\IStandardObject $attributes
	 *
	 * @return Uuid\UuidInterface
	 *
	 * @throws JsonApiExceptions\IJsonApiException
	 */
	protected function hydrateChannelAttribute(
		JsonAPIDocument\Objects\IStandardObject $attributes
	): Uuid\UuidInterface {
		if (
			!is_scalar($attributes->get('channel'))
			|| !$attributes->has('channel')
			|| $attributes->get('channel') === ''
			|| !Uuid\Uuid::isValid((string) $attributes->get('channel'))
		) {
			throw new JsonApiExceptions\JsonApiErrorException(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				$this->translator->translate('//triggers-module.base.messages.missingAttribute.heading'),
				$this->translator->translate('//triggers-module.base.messages.missingAttribute.message'),
				[
					'pointer' => '/data/attributes/channel',
				]
			);
		}

		return Uuid\Uuid::fromString((string) $attributes->get('channel'));
	}

}
