<?php declare(strict_types = 1);

/**
 * ChannelPropertyActionSchema.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Schemas
 * @since          0.1.0
 *
 * @date           04.04.20
 */

namespace FastyBird\Module\Triggers\Schemas\Actions;

use FastyBird\Library\Metadata\Types\ModuleSourceType;
use FastyBird\Module\Triggers\Entities;
use Neomerx\JsonApi;

/**
 * Trigger channel state action entity schema
 *
 * @package         FastyBird:TriggersModule!
 * @subpackage      Schemas
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @phpstan-extends ActionSchema<Entities\Actions\IChannelPropertyAction>
 */
final class ChannelPropertyActionSchema extends ActionSchema
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = ModuleSourceType::SOURCE_MODULE_TRIGGERS . '/action/channel-property';

	/**
	 * @return string
	 */
	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getEntityClass(): string
	{
		return Entities\Actions\ChannelPropertyAction::class;
	}

	/**
	 * @param Entities\Actions\IChannelPropertyAction $action
	 * @param JsonApi\Contracts\Schema\ContextInterface $context
	 *
	 * @return iterable<string, string|bool>
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getAttributes($action, JsonApi\Contracts\Schema\ContextInterface $context): iterable
	{
		return array_merge((array) parent::getAttributes($action, $context), [
			'device'   => $action->getDevice()->toString(),
			'channel'  => $action->getChannel()->toString(),
			'property' => $action->getProperty()->toString(),
			'value'    => (string) $action->getValue(),
		]);
	}

}
