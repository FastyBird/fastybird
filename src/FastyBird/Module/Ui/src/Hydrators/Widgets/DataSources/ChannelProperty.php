<?php declare(strict_types = 1);

/**
 * ChannelProperty.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           27.05.20
 */

namespace FastyBird\Module\Ui\Hydrators\Widgets\DataSources;

use FastyBird\Module\Ui\Entities;
use Ramsey\Uuid;
use IPub\JsonAPIDocument;

/**
 * Channel property data source entity hydrator
 *
 * @extends DataSource<Entities\Widgets\DataSources\DataSource>
 *
 * @package        FastyBird:UIModule!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ChannelProperty extends DataSource
{

	/** @var array<int|string, string> */
	protected array $attributes = [
		'channel',
		'property',
	];

	public function getEntityName(): string
	{
		return Entities\Widgets\DataSources\ChannelProperty::class;
	}

	public function hydrateChannelAttribute(
		JsonAPIDocument\Objects\IStandardObject $attributes,
		Entities\Widgets\DataSources\DataSource|null $entity = null,
	): Uuid\UuidInterface|null
	{
		if (
			!is_scalar($attributes->get('channel'))
			|| (string) $attributes->get('channel') === ''
			|| !Uuid\Uuid::isValid((string) $attributes->get('channel'))
		) {
			return null;
		}

		return Uuid\Uuid::fromString((string) $attributes->get('channel'));
	}

	public function hydratePropertyAttribute(
		JsonAPIDocument\Objects\IStandardObject $attributes,
		Entities\Widgets\DataSources\DataSource|null $entity = null,
	): Uuid\UuidInterface|null
	{
		if (
			!is_scalar($attributes->get('property'))
			|| (string) $attributes->get('property') === ''
			|| !Uuid\Uuid::isValid((string) $attributes->get('property'))
		) {
			return null;
		}

		return Uuid\Uuid::fromString((string) $attributes->get('property'));
	}

}
