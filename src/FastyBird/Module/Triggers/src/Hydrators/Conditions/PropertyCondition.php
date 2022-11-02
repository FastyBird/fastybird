<?php declare(strict_types = 1);

/**
 * PropertyConditionHydrator.php
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
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Triggers\Entities;
use Fig\Http\Message\StatusCodeInterface;
use IPub\JsonAPIDocument;
use Ramsey\Uuid;

/**
 * Property condition entity hydrator
 *
 * @package         FastyBird:TriggersModule!
 * @subpackage      Hydrators
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @phpstan-template TEntityClass of Entities\Conditions\IPropertyCondition
 * @phpstan-extends  ConditionHydrator<TEntityClass>
 */
abstract class PropertyConditionHydrator extends ConditionHydrator
{

	/** @var string[] */
	protected array $attributes = [
		'device',
		'property',
		'operator',
		'operand',
		'enabled',
	];

	/**
	 * @param JsonAPIDocument\Objects\IStandardObject $attributes
	 *
	 * @return Uuid\UuidInterface
	 *
	 * @throws JsonApiExceptions\IJsonApiException
	 */
	protected function hydrateDeviceAttribute(
		JsonAPIDocument\Objects\IStandardObject $attributes
	): Uuid\UuidInterface {
		if (
			!is_scalar($attributes->get('device'))
			|| !$attributes->has('device')
			|| $attributes->get('device') === ''
			|| !Uuid\Uuid::isValid((string) $attributes->get('device'))
		) {
			throw new JsonApiExceptions\JsonApiErrorException(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				$this->translator->translate('//triggers-module.base.messages.missingAttribute.heading'),
				$this->translator->translate('//triggers-module.base.messages.missingAttribute.message'),
				[
					'pointer' => '/data/attributes/device',
				]
			);
		}

		return Uuid\Uuid::fromString((string) $attributes->get('device'));
	}

	/**
	 * @param JsonAPIDocument\Objects\IStandardObject $attributes
	 *
	 * @return Uuid\UuidInterface
	 *
	 * @throws JsonApiExceptions\IJsonApiException
	 */
	protected function hydratePropertyAttribute(
		JsonAPIDocument\Objects\IStandardObject $attributes
	): Uuid\UuidInterface {
		if (
			!is_scalar($attributes->get('property'))
			|| !$attributes->has('property')
			|| $attributes->get('property') === ''
			|| !Uuid\Uuid::isValid((string) $attributes->get('property'))
		) {
			throw new JsonApiExceptions\JsonApiErrorException(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				$this->translator->translate('//triggers-module.base.messages.missingAttribute.heading'),
				$this->translator->translate('//triggers-module.base.messages.missingAttribute.message'),
				[
					'pointer' => '/data/attributes/property',
				]
			);
		}

		return Uuid\Uuid::fromString((string) $attributes->get('property'));
	}

	/**
	 * @param JsonAPIDocument\Objects\IStandardObject $attributes
	 *
	 * @return MetadataTypes\TriggerConditionOperatorType
	 *
	 * @throws JsonApiExceptions\IJsonApiException
	 */
	protected function hydrateOperatorAttribute(
		JsonAPIDocument\Objects\IStandardObject $attributes
	): MetadataTypes\TriggerConditionOperatorType {
		// Condition operator have to be set
		if (
			!is_scalar($attributes->get('operator'))
			|| !$attributes->has('operator')
			|| $attributes->get('operator') === ''
		) {
			throw new JsonApiExceptions\JsonApiErrorException(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				$this->translator->translate('//triggers-module.base.messages.missingAttribute.heading'),
				$this->translator->translate('//triggers-module.base.messages.missingAttribute.message'),
				[
					'pointer' => '/data/attributes/operator',
				]
			);

			// ...and have to be valid value
		} elseif (!MetadataTypes\TriggerConditionOperatorType::isValidValue($attributes->get('operator'))) {
			throw new JsonApiExceptions\JsonApiErrorException(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				$this->translator->translate('//triggers-module.conditions.messages.invalidOperator.heading'),
				$this->translator->translate('//triggers-module.conditions.messages.invalidOperator.message'),
				[
					'pointer' => '/data/attributes/operator',
				]
			);
		}

		return MetadataTypes\TriggerConditionOperatorType::get($attributes->get('operator'));
	}

	/**
	 * @param JsonAPIDocument\Objects\IStandardObject $attributes
	 *
	 * @return string
	 *
	 * @throws JsonApiExceptions\IJsonApiException
	 */
	protected function hydrateOperandAttribute(
		JsonAPIDocument\Objects\IStandardObject $attributes
	): string {
		if (
			!is_scalar($attributes->get('operand'))
			|| !$attributes->has('operand')
			|| $attributes->get('operand') === ''
		) {
			throw new JsonApiExceptions\JsonApiErrorException(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				$this->translator->translate('//triggers-module.base.messages.missingAttribute.heading'),
				$this->translator->translate('//triggers-module.base.messages.missingAttribute.message'),
				[
					'pointer' => '/data/attributes/operand',
				]
			);
		}

		$operand = $attributes->get('operand');

		return is_bool($operand) ? ($operand ? 'true' : 'false') : strtolower((string) $operand);
	}

}
