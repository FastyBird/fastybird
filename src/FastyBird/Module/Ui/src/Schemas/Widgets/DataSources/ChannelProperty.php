<?php declare(strict_types = 1);

/**
 * ChannelProperty.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           26.05.20
 */

namespace FastyBird\Module\Ui\Schemas\Widgets\DataSources;

use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Ui\Entities;
use Neomerx\JsonApi;
use function array_merge;

/**
 * Channel data source entity schema
 *
 * @template T of Entities\Widgets\DataSources\ChannelProperty
 * @extends  DataSource<T>
 *
 * @package          FastyBird:UIModule!
 * @subpackage       Schemas
 * @author           Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ChannelProperty extends DataSource
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\Sources\Module::UI->value . '/data-source/' . Entities\Widgets\DataSources\ChannelProperty::TYPE;

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

	public function getEntityClass(): string
	{
		return Entities\Widgets\DataSources\ChannelProperty::class;
	}

	/**
	 * @param T $resource
	 *
	 * @return iterable<string, mixed>
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getAttributes(
		$resource,
		JsonApi\Contracts\Schema\ContextInterface $context,
	): iterable
	{
		return array_merge(
			(array) parent::getAttributes($resource, $context),
			[
				'channel' => $resource->getChannel()->toString(),
				'property' => $resource->getProperty()->toString(),
			],
		);
	}

}
