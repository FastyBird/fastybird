<?php declare(strict_types = 1);

/**
 * ProfileEmail.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Hydrators
 * @since          0.1.0
 *
 * @date           21.08.20
 */

namespace FastyBird\Module\Accounts\Hydrators\Emails;

use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Types;
use IPub\JsonAPIDocument;

/**
 * Profile email entity hydrator
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Hydrators
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
trait TEmail
{

	public function getEntityName(): string
	{
		return Entities\Emails\Email::class;
	}

	protected function hydrateVisibilityAttribute(
		JsonAPIDocument\Objects\IStandardObject $attributes,
	): Types\EmailVisibility
	{
		$isPrivate = (bool) $attributes->get('private');

		return Types\EmailVisibility::get(
			$isPrivate ? Types\EmailVisibility::VISIBILITY_PRIVATE : Types\EmailVisibility::VISIBILITY_PUBLIC,
		);
	}

}
