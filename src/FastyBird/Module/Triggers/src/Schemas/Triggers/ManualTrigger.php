<?php declare(strict_types = 1);

/**
 * ManualTriggerSchema.php
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

namespace FastyBird\Module\Triggers\Schemas\Triggers;

use FastyBird\Library\Metadata\Types\ModuleSourceType;
use FastyBird\Module\Triggers;
use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Router;
use Neomerx\JsonApi;

/**
 * Manual trigger entity schema
 *
 * @package         FastyBird:TriggersModule!
 * @subpackage      Schemas
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @phpstan-extends TriggerSchema<Entities\Triggers\IManualTrigger>
 */
final class ManualTriggerSchema extends TriggerSchema
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = ModuleSourceType::SOURCE_MODULE_TRIGGERS . '/trigger/manual';

	/**
	 * Define relationships names
	 */
	public const RELATIONSHIPS_CONTROLS = 'controls';

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
		return Entities\Triggers\ManualTrigger::class;
	}

	/**
	 * @param Entities\Triggers\IManualTrigger $trigger
	 * @param JsonApi\Contracts\Schema\ContextInterface $context
	 *
	 * @return iterable<string, mixed>
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationships($trigger, JsonApi\Contracts\Schema\ContextInterface $context): iterable
	{
		return array_merge([
			self::RELATIONSHIPS_CONTROLS => [
				self::RELATIONSHIP_DATA          => $trigger->getControls(),
				self::RELATIONSHIP_LINKS_SELF    => true,
				self::RELATIONSHIP_LINKS_RELATED => true,
			],
		], (array) parent::getRelationships($trigger, $context));
	}

	/**
	 * @param Entities\Triggers\IManualTrigger $trigger
	 * @param string $name
	 *
	 * @return JsonApi\Contracts\Schema\LinkInterface
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationshipRelatedLink($trigger, string $name): JsonApi\Contracts\Schema\LinkInterface
	{
		if ($name === self::RELATIONSHIPS_CONTROLS) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					TriggersModule\Constants::ROUTE_NAME_TRIGGER_CONTROLS,
					[
						Router\Routes::URL_TRIGGER_ID => $trigger->getPlainId(),
					]
				),
				true,
				[
					'count' => count($trigger->getControls()),
				]
			);
		}

		return parent::getRelationshipRelatedLink($trigger, $name);
	}

	/**
	 * @param Entities\Triggers\IManualTrigger $trigger
	 * @param string $name
	 *
	 * @return JsonApi\Contracts\Schema\LinkInterface
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationshipSelfLink($trigger, string $name): JsonApi\Contracts\Schema\LinkInterface
	{
		if ($name === self::RELATIONSHIPS_CONTROLS) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					TriggersModule\Constants::ROUTE_NAME_TRIGGER_RELATIONSHIP,
					[
						Router\Routes::URL_ITEM_ID     => $trigger->getPlainId(),
						Router\Routes::RELATION_ENTITY => $name,
					]
				),
				false
			);
		}

		return parent::getRelationshipSelfLink($trigger, $name);
	}

}
