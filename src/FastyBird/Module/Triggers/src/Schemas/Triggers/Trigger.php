<?php declare(strict_types = 1);

/**
 * TriggerSchema.php
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

use FastyBird\JsonApi\Schemas as JsonApiSchemas;
use FastyBird\Module\Triggers;
use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Exceptions;
use FastyBird\Module\Triggers\Models;
use FastyBird\Module\Triggers\Router;
use FastyBird\Module\Triggers\Schemas;
use IPub\SlimRouter\Routing;
use Neomerx\JsonApi;

/**
 * Base trigger entity schema
 *
 * @package          FastyBird:TriggersModule!
 * @subpackage       Schemas
 *
 * @phpstan-template T of Entities\Triggers\ITrigger
 * @phpstan-extends  JsonApiSchemas\JsonApiSchema<T>
 */
abstract class TriggerSchema extends JsonApiSchemas\JsonApiSchema
{

	/**
	 * Define relationships names
	 */
	public const RELATIONSHIPS_ACTIONS = 'actions';
	public const RELATIONSHIPS_NOTIFICATIONS = 'notifications';

	/** @var Routing\IRouter */
	protected Routing\IRouter $router;

	/** @var Models\States\ActionsRepository */
	private Models\States\ActionsRepository $actionStateRepository;

	public function __construct(
		Routing\IRouter $router,
		Models\States\ActionsRepository $actionStateRepository
	) {
		$this->router = $router;

		$this->actionStateRepository = $actionStateRepository;
	}

	/**
	 * @param Entities\Triggers\ITrigger $trigger
	 * @param JsonApi\Contracts\Schema\ContextInterface $context
	 *
	 * @return iterable<string, string|bool|null>
	 *
	 * @phpstan-param T $trigger
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getAttributes($trigger, JsonApi\Contracts\Schema\ContextInterface $context): iterable
	{
		try {
			$isTriggered = false;

			if (count($trigger->getActions()) > 0) {
				$isTriggered = true;

				foreach ($trigger->getActions() as $action) {
					$state = $this->actionStateRepository->findOne($action);

					if ($state === null || $state->isTriggered() === false) {
						$isTriggered = false;
					}
				}
			}
		} catch (Exceptions\NotImplementedException $ex) {
			$isTriggered = null;
		}

		return [
			'name'    => $trigger->getName(),
			'comment' => $trigger->getComment(),
			'enabled' => $trigger->isEnabled(),

			'is_triggered' => $isTriggered,
		];
	}

	/**
	 * @param Entities\Triggers\ITrigger $trigger
	 *
	 * @return JsonApi\Contracts\Schema\LinkInterface
	 *
	 * @phpstan-param T $trigger
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getSelfLink($trigger): JsonApi\Contracts\Schema\LinkInterface
	{
		return new JsonApi\Schema\Link(
			false,
			$this->router->urlFor(
				TriggersModule\Constants::ROUTE_NAME_TRIGGER,
				[
					Router\Routes::URL_ITEM_ID => $trigger->getPlainId(),
				]
			),
			false
		);
	}

	/**
	 * @param Entities\Triggers\ITrigger $trigger
	 * @param JsonApi\Contracts\Schema\ContextInterface $context
	 *
	 * @return iterable<string, mixed>
	 *
	 * @phpstan-param T $trigger
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationships($trigger, JsonApi\Contracts\Schema\ContextInterface $context): iterable
	{
		return [
			self::RELATIONSHIPS_ACTIONS       => [
				self::RELATIONSHIP_DATA          => $trigger->getActions(),
				self::RELATIONSHIP_LINKS_SELF    => true,
				self::RELATIONSHIP_LINKS_RELATED => true,
			],
			self::RELATIONSHIPS_NOTIFICATIONS => [
				self::RELATIONSHIP_DATA          => $trigger->getNotifications(),
				self::RELATIONSHIP_LINKS_SELF    => true,
				self::RELATIONSHIP_LINKS_RELATED => true,
			],
		];
	}

	/**
	 * @param Entities\Triggers\ITrigger $trigger
	 * @param string $name
	 *
	 * @return JsonApi\Contracts\Schema\LinkInterface
	 *
	 * @phpstan-param T $trigger
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationshipRelatedLink($trigger, string $name): JsonApi\Contracts\Schema\LinkInterface
	{
		if ($name === self::RELATIONSHIPS_ACTIONS) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					TriggersModule\Constants::ROUTE_NAME_TRIGGER_ACTIONS,
					[
						Router\Routes::URL_TRIGGER_ID => $trigger->getPlainId(),
					]
				),
				true,
				[
					'count' => count($trigger->getActions()),
				]
			);

		} elseif ($name === self::RELATIONSHIPS_NOTIFICATIONS) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					TriggersModule\Constants::ROUTE_NAME_TRIGGER_NOTIFICATIONS,
					[
						Router\Routes::URL_TRIGGER_ID => $trigger->getPlainId(),
					]
				),
				true,
				[
					'count' => count($trigger->getNotifications()),
				]
			);
		}

		return parent::getRelationshipRelatedLink($trigger, $name);
	}

	/**
	 * @param Entities\Triggers\ITrigger $trigger
	 * @param string $name
	 *
	 * @return JsonApi\Contracts\Schema\LinkInterface
	 *
	 * @phpstan-param T $trigger
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationshipSelfLink($trigger, string $name): JsonApi\Contracts\Schema\LinkInterface
	{
		if (
			$name === self::RELATIONSHIPS_ACTIONS
			|| $name === self::RELATIONSHIPS_NOTIFICATIONS
		) {
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
