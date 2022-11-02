<?php declare(strict_types = 1);

/**
 * PropertyActionHydrator.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Hydrators
 * @since          0.6.0
 *
 * @date           08.01.22
 */

namespace FastyBird\Module\Triggers\Hydrators\Actions;

use FastyBird\JsonApi\Exceptions as JsonApiExceptions;
use FastyBird\Module\Triggers\Entities;
use Fig\Http\Message\StatusCodeInterface;
use IPub\JsonAPIDocument;
use Ramsey\Uuid;

/**
 * Property action entity hydrator
 *
 * @package         FastyBird:TriggersModule!
 * @subpackage      Hydrators
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @phpstan-template TEntityClass of Entities\Actions\IPropertyAction
 * @phpstan-extends  ActionHydrator<TEntityClass>
 */
abstract class PropertyActionHydrator extends ActionHydrator
{

	/** @var string[] */
	protected array $attributes = [
		'device',
		'property',
		'value',
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
	 * @return string
	 *
	 * @throws JsonApiExceptions\IJsonApiException
	 */
	protected function hydrateValueAttribute(
		JsonAPIDocument\Objects\IStandardObject $attributes
	): string {
		if (
			!is_scalar($attributes->get('value'))
			|| !$attributes->has('value')
			|| $attributes->get('value') === ''
		) {
			throw new JsonApiExceptions\JsonApiErrorException(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				$this->translator->translate('//triggers-module.base.messages.missingAttribute.heading'),
				$this->translator->translate('//triggers-module.base.messages.missingAttribute.message'),
				[
					'pointer' => '/data/attributes/value',
				]
			);
		}

		$value = $attributes->get('value');

		return is_bool($value) ? ($value ? 'true' : 'false') : strtolower((string) $value);
	}

}
