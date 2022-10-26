<?php declare(strict_types = 1);

/**
 * Role.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Hydrators
 * @since          0.1.0
 *
 * @date           03.06.20
 */

namespace FastyBird\Module\Accounts\Hydrators\Roles;

use FastyBird\JsonApi\Hydrators as JsonApiHydrators;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Schemas;
use IPub\JsonAPIDocument;
use function is_scalar;

/**
 * Role entity hydrator
 *
 * @extends JsonApiHydrators\Hydrator<Entities\Roles\Role>
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Role extends JsonApiHydrators\Hydrator
{

	/** @var Array<int|string, string> */
	protected array $attributes = [
		'comment',
	];

	/** @var Array<string> */
	protected array $relationships = [
		Schemas\Roles\Role::RELATIONSHIPS_PARENT,
	];

	public function getEntityName(): string
	{
		return Entities\Roles\Role::class;
	}

	protected function hydrateCommentAttribute(JsonAPIDocument\Objects\IStandardObject $attributes): string|null
	{
		if (
			!is_scalar($attributes->get('comment'))
			|| (string) $attributes->get('comment') === ''
		) {
			return null;
		}

		return (string) $attributes->get('comment');
	}

}
