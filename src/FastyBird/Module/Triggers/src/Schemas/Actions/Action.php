<?php declare(strict_types = 1);

/**
 * ActionSchema.php
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
 * Action entity schema
 *
 * @package          FastyBird:TriggersModule!
 * @subpackage       Schemas
 *
 * @author           Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @phpstan-template T of Entities\Actions\IAction
 * @phpstan-extends  JsonApiSchemas\JsonApiSchema<T>
 */
abstract class ActionSchema extends JsonApiSchemas\JsonApiSchema
{

	/**
	 * Define relationships names
	 */
	public const RELATIONSHIPS_TRIGGER = 'trigger';

	/** @var Routing\IRouter */
	protected Routing\IRouter $router;

	/** @var Models\States\ActionsRepository */
	private Models\States\ActionsRepository $stateRepository;

	public function __construct(
		Routing\IRouter $router,
		Models\States\ActionsRepository $stateRepository
	) {
		$this->router = $router;
		$this->stateRepository = $stateRepository;
	}

	/**
	 * @param Entities\Actions\IAction $action
	 * @param JsonApi\Contracts\Schema\ContextInterface $context
	 *
	 * @return iterable<string, bool>
	 *
	 * @phpstan-param T $action
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getAttributes($action, JsonApi\Contracts\Schema\ContextInterface $context): iterable
	{
		try {
			$state = $this->stateRepository->findOne($action);

		} catch (Exceptions\NotImplementedException $ex) {
			$state = null;
		}

		return [
			'enabled'      => $action->isEnabled(),
			'is_triggered' => $state !== null && $state->isTriggered(),
		];
	}

	/**
	 * @param Entities\Actions\IAction $action
	 *
	 * @return JsonApi\Contracts\Schema\LinkInterface
	 *
	 * @phpstan-param T $action
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getSelfLink($action): JsonApi\Contracts\Schema\LinkInterface
	{
		return new JsonApi\Schema\Link(
			false,
			$this->router->urlFor(
				TriggersModule\Constants::ROUTE_NAME_TRIGGER_ACTION,
				[
					Router\Routes::URL_TRIGGER_ID => $action->getTrigger()->getPlainId(),
					Router\Routes::URL_ITEM_ID    => $action->getPlainId(),
				]
			),
			false
		);
	}

	/**
	 * @param Entities\Actions\IAction $action
	 * @param JsonApi\Contracts\Schema\ContextInterface $context
	 *
	 * @return iterable<string, mixed>
	 *
	 * @phpstan-param T $action
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationships($action, JsonApi\Contracts\Schema\ContextInterface $context): iterable
	{
		return [
			self::RELATIONSHIPS_TRIGGER => [
				self::RELATIONSHIP_DATA          => $action->getTrigger(),
				self::RELATIONSHIP_LINKS_SELF    => true,
				self::RELATIONSHIP_LINKS_RELATED => true,
			],
		];
	}

	/**
	 * @param Entities\Actions\IAction $action
	 * @param string $name
	 *
	 * @return JsonApi\Contracts\Schema\LinkInterface
	 *
	 * @phpstan-param T $action
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationshipRelatedLink($action, string $name): JsonApi\Contracts\Schema\LinkInterface
	{
		if ($name === self::RELATIONSHIPS_TRIGGER) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					TriggersModule\Constants::ROUTE_NAME_TRIGGER,
					[
						Router\Routes::URL_ITEM_ID => $action->getTrigger()->getPlainId(),
					]
				),
				false
			);
		}

		return parent::getRelationshipRelatedLink($action, $name);
	}

	/**
	 * @param Entities\Actions\IAction $action
	 * @param string $name
	 *
	 * @return JsonApi\Contracts\Schema\LinkInterface
	 *
	 * @phpstan-param T $action
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationshipSelfLink($action, string $name): JsonApi\Contracts\Schema\LinkInterface
	{
		if ($name === self::RELATIONSHIPS_TRIGGER) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					TriggersModule\Constants::ROUTE_NAME_TRIGGER_ACTION_RELATIONSHIP,
					[
						Router\Routes::URL_TRIGGER_ID  => $action->getTrigger()->getPlainId(),
						Router\Routes::URL_ITEM_ID     => $action->getPlainId(),
						Router\Routes::RELATION_ENTITY => $name,
					]
				),
				false
			);
		}

		return parent::getRelationshipSelfLink($action, $name);
	}

}
