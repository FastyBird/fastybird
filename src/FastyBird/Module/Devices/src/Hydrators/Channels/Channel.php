<?php declare(strict_types = 1);

/**
 * Channel.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Hydrators
 * @since          0.1.0
 *
 * @date           13.04.19
 */

namespace FastyBird\Module\Devices\Hydrators\Channels;

use FastyBird\JsonApi\Hydrators as JsonApiHydrators;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Schemas;
use IPub\JsonAPIDocument;
use function is_scalar;

/**
 * Device channel entity hydrator
 *
 * @extends JsonApiHydrators\Hydrator<Entities\Channels\Channel>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Channel extends JsonApiHydrators\Hydrator
{

	/** @var Array<int|string, string> */
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
			Schemas\Channels\Channel::RELATIONSHIPS_DEVICE,
		];

	public function getEntityName(): string
	{
		return Entities\Channels\Channel::class;
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

}
