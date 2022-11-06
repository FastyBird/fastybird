<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Tests\Fixtures\Dummy;

use FastyBird\JsonApi\Exceptions as JsonApiExceptions;
use FastyBird\Module\Triggers\Hydrators;
use Fig\Http\Message\StatusCodeInterface;
use IPub\JsonAPIDocument;
use Ramsey\Uuid;
use function is_scalar;

final class DummyActionHydrator extends Hydrators\Actions\Action
{

	/** @var Array<int|string, string> */
	protected array $attributes = [
		0 => 'value',
		1 => 'enabled',
		'do_item' => 'doItem',
	];

	public function getEntityName(): string
	{
		return DummyActionEntity::class;
	}

	/**
	 * @throws JsonApiExceptions\JsonApi
	 */
	protected function hydrateDoItemAttribute(
		JsonAPIDocument\Objects\IStandardObject $attributes,
	): Uuid\UuidInterface
	{
		if (
			!is_scalar($attributes->get('do_item'))
			|| !$attributes->has('do_item')
			|| $attributes->get('do_item') === ''
			|| !Uuid\Uuid::isValid((string) $attributes->get('do_item'))
		) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				$this->translator->translate('//triggers-module.base.messages.missingAttribute.heading'),
				$this->translator->translate('//triggers-module.base.messages.missingAttribute.message'),
				[
					'pointer' => '/data/attributes/do_item',
				],
			);
		}

		return Uuid\Uuid::fromString((string) $attributes->get('do_item'));
	}

}
