<?php declare(strict_types = 1);

/**
 * AutomaticTriggerSchema.php
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
use FastyBird\Module\Triggers\Exceptions;
use FastyBird\Module\Triggers\Models;
use FastyBird\Module\Triggers\Router;
use IPub\SlimRouter\Routing;
use Neomerx\JsonApi;

/**
 * Automatic trigger entity schema
 *
 * @package         FastyBird:TriggersModule!
 * @subpackage      Schemas
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @phpstan-extends TriggerSchema<Entities\Triggers\IAutomaticTrigger>
 */
final class AutomaticTriggerSchema extends TriggerSchema
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = ModuleSourceType::SOURCE_MODULE_TRIGGERS . '/trigger/automatic';

	/**
	 * Define relationships names
	 */
	public const RELATIONSHIPS_CONDITIONS = 'conditions';

	/** @var Models\States\ConditionsRepository */
	private Models\States\ConditionsRepository $conditionStateRepository;

	public function __construct(
		Routing\IRouter $router,
		Models\States\ActionsRepository $actionStateRepository,
		Models\States\ConditionsRepository $conditionStateRepository
	) {
		parent::__construct($router, $actionStateRepository);

		$this->conditionStateRepository = $conditionStateRepository;
	}

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
		return Entities\Triggers\AutomaticTrigger::class;
	}

	/**
	 * @param Entities\Triggers\IAutomaticTrigger $trigger
	 * @param JsonApi\Contracts\Schema\ContextInterface $context
	 *
	 * @return iterable<string, string|bool|null>
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getAttributes($trigger, JsonApi\Contracts\Schema\ContextInterface $context): iterable
	{
		try {
			$isFulfilled = false;

			if (count($trigger->getConditions()) > 0) {
				$isFulfilled = true;

				foreach ($trigger->getConditions() as $condition) {
					$state = $this->conditionStateRepository->findOne($condition);

					if ($state === null || $state->isFulfilled() === false) {
						$isFulfilled = false;
					}
				}
			}
		} catch (Exceptions\NotImplementedException $ex) {
			$isFulfilled = null;
		}

		return array_merge((array) parent::getAttributes($trigger, $context), [
			'is_fulfilled' => $isFulfilled,
		]);
	}

	/**
	 * @param Entities\Triggers\IAutomaticTrigger $trigger
	 * @param JsonApi\Contracts\Schema\ContextInterface $context
	 *
	 * @return iterable<string, mixed>
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationships($trigger, JsonApi\Contracts\Schema\ContextInterface $context): iterable
	{
		return array_merge([
			self::RELATIONSHIPS_CONDITIONS => [
				self::RELATIONSHIP_DATA          => $trigger->getConditions(),
				self::RELATIONSHIP_LINKS_SELF    => true,
				self::RELATIONSHIP_LINKS_RELATED => true,
			],
		], (array) parent::getRelationships($trigger, $context));
	}

	/**
	 * @param Entities\Triggers\IAutomaticTrigger $trigger
	 * @param string $name
	 *
	 * @return JsonApi\Contracts\Schema\LinkInterface
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationshipRelatedLink($trigger, string $name): JsonApi\Contracts\Schema\LinkInterface
	{
		if ($name === self::RELATIONSHIPS_CONDITIONS) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					TriggersModule\Constants::ROUTE_NAME_TRIGGER_CONDITIONS,
					[
						Router\Routes::URL_TRIGGER_ID => $trigger->getPlainId(),
					]
				),
				true,
				[
					'count' => count($trigger->getConditions()),
				]
			);
		}

		return parent::getRelationshipRelatedLink($trigger, $name);
	}

	/**
	 * @param Entities\Triggers\IAutomaticTrigger $trigger
	 * @param string $name
	 *
	 * @return JsonApi\Contracts\Schema\LinkInterface
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationshipSelfLink($trigger, string $name): JsonApi\Contracts\Schema\LinkInterface
	{
		if ($name === self::RELATIONSHIPS_CONDITIONS) {
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
