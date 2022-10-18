<?php declare(strict_types = 1);

/**
 * Connector.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Devices!
 * @subpackage     Hydrators
 * @since          0.1.0
 *
 * @date           16.04.21
 */

namespace FastyBird\Module\Devices\Hydrators\Connectors;

use FastyBird\JsonApi\Hydrators as JsonApiHydrators;
use FastyBird\Module\Devices\Entities;
use IPub\JsonAPIDocument;
use function boolval;
use function is_scalar;

/**
 * Connector entity hydrator
 *
 * @template  TEntityClass of Entities\Connectors\Connector
 * @extends   JsonApiHydrators\Hydrator<TEntityClass>
 *
 * @package        FastyBird:Devices!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class Connector extends JsonApiHydrators\Hydrator
{

	/** @var Array<int|string, string> */
	protected array $attributes
		= [
			'identifier',
			'name',
			'comment',
			'enabled',
		];

	public function getEntityName(): string
	{
		return Entities\Connectors\Connector::class;
	}

	protected function hydrateNameAttribute(JsonAPIDocument\Objects\IStandardObject $attributes): string|null
	{
		if (
			!is_scalar($attributes->get('name'))
			|| (string) $attributes->get('name') === ''
		) {
			return null;
		}

		return (string) $attributes->get('name');
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

	protected function hydrateEnabledAttribute(JsonAPIDocument\Objects\IStandardObject $attributes): bool
	{
		return is_scalar($attributes->get('enabled')) && boolval($attributes->get('enabled'));
	}

}
