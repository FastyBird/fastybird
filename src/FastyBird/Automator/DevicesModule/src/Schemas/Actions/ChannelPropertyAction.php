<?php declare(strict_types = 1);

/**
 * ChannelPropertyAction.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModuleAutomator!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           04.04.20
 */

namespace FastyBird\Automator\DevicesModule\Schemas\Actions;

use FastyBird\Automator\DevicesModule\Entities;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Triggers\Schemas as TriggersSchemas;
use Neomerx\JsonApi;
use function array_merge;
use function strval;

/**
 * Trigger channel state action entity schema
 *
 * @extends TriggersSchemas\Actions\Action<Entities\Actions\ChannelPropertyAction>
 *
 * @package        FastyBird:DevicesModuleAutomator!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ChannelPropertyAction extends TriggersSchemas\Actions\Action
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\Sources\Automator::DEVICE_MODULE . '/action/' . Entities\Actions\ChannelPropertyAction::TYPE;

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

	public function getEntityClass(): string
	{
		return Entities\Actions\ChannelPropertyAction::class;
	}

	/**
	 * @return iterable<string, string|bool>
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getAttributes(
		$resource,
		JsonApi\Contracts\Schema\ContextInterface $context,
	): iterable
	{
		return array_merge((array) parent::getAttributes($resource, $context), [
			'device' => $resource->getDevice()->toString(),
			'channel' => $resource->getChannel()->toString(),
			'property' => $resource->getProperty()->toString(),
			'value' => strval($resource->getValue()),
		]);
	}

}
