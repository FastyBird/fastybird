<?php declare(strict_types = 1);

/**
 * Identity.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Hydrators
 * @since          0.1.0
 *
 * @date           15.08.20
 */

namespace FastyBird\Module\Accounts\Hydrators\Identities;

use FastyBird\JsonApi\Exceptions as JsonApiExceptions;
use FastyBird\JsonApi\Hydrators as JsonApiHydrators;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Exceptions;
use FastyBird\Module\Accounts\Helpers;
use FastyBird\Module\Accounts\Schemas;
use Fig\Http\Message\StatusCodeInterface;
use IPub\JsonAPIDocument;
use function is_scalar;

/**
 * Identity entity hydrator
 *
 * @extends JsonApiHydrators\Hydrator<Entities\Identities\Identity>
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Identity extends JsonApiHydrators\Hydrator
{

	/** @var array<int|string, string> */
	protected array $attributes = [
		'uid',
		'password',
	];

	/** @var array<string> */
	protected array $relationships = [
		Schemas\Identities\Identity::RELATIONSHIPS_ACCOUNT,
	];

	public function getEntityName(): string
	{
		return Entities\Identities\Identity::class;
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws JsonApiExceptions\JsonApiError
	 */
	protected function hydratePasswordAttribute(
		JsonAPIDocument\Objects\IStandardObject $attributes,
	): Helpers\Password
	{
		if (!is_scalar($attributes->get('password'))) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				$this->translator->translate('//accounts-module.base.messages.invalidAttribute.heading'),
				$this->translator->translate('//accounts-module.base.messages.invalidAttribute.message'),
				[
					'pointer' => '/data/attributes/password',
				],
			);
		}

		return Helpers\Password::createFromString((string) $attributes->get('password'));
	}

}
