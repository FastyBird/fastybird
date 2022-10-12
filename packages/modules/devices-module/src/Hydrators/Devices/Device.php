<?php declare(strict_types = 1);

/**
 * Device.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Hydrators
 * @since          0.1.0
 *
 * @date           07.06.19
 */

namespace FastyBird\DevicesModule\Hydrators\Devices;

use FastyBird\DevicesModule\Entities;
use FastyBird\DevicesModule\Schemas;
use FastyBird\JsonApi\Hydrators as JsonApiHydrators;
use IPub\JsonAPIDocument;
use function is_scalar;

/**
 * Device entity hydrator
 *
 * @template  TEntityClass of Entities\Devices\Device
 * @extends   JsonApiHydrators\Hydrator<TEntityClass>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class Device extends JsonApiHydrators\Hydrator
{

	/** @var Array<string> */
	protected array $attributes
		= [
			'identifier',
			'name',
			'comment',
			'params',
		];

	/** @var Array<string> */
	protected array $relationships
		= [
			Schemas\Devices\Device::RELATIONSHIPS_CONNECTOR,
			Schemas\Devices\Device::RELATIONSHIPS_PARENTS,
		];

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

}
