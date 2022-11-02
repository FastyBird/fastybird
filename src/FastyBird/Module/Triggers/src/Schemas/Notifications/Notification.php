<?php declare(strict_types = 1);

/**
 * NotificationSchema.php
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

namespace FastyBird\Module\Triggers\Schemas\Notifications;

use FastyBird\JsonApi\Schemas as JsonApiSchemas;
use FastyBird\Module\Triggers;
use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Router;
use FastyBird\Module\Triggers\Schemas;
use IPub\SlimRouter\Routing;
use Neomerx\JsonApi;

/**
 * Notification entity schema
 *
 * @package          FastyBird:TriggersModule!
 * @subpackage       Schemas
 *
 * @author           Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @phpstan-template T of Entities\Notifications\INotification
 * @phpstan-extends  JsonApiSchemas\JsonApiSchema<T>
 */
abstract class NotificationSchema extends JsonApiSchemas\JsonApiSchema
{

	/**
	 * Define relationships names
	 */
	public const RELATIONSHIPS_TRIGGER = 'trigger';

	/** @var Routing\IRouter */
	protected Routing\IRouter $router;

	public function __construct(
		Routing\IRouter $router
	) {
		$this->router = $router;
	}

	/**
	 * @param Entities\Notifications\INotification $notification
	 * @param JsonApi\Contracts\Schema\ContextInterface $context
	 *
	 * @return iterable<string, bool>
	 *
	 * @phpstan-param T $notification
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getAttributes($notification, JsonApi\Contracts\Schema\ContextInterface $context): iterable
	{
		return [
			'enabled' => $notification->isEnabled(),
		];
	}

	/**
	 * @param Entities\Notifications\INotification $notification
	 *
	 * @return JsonApi\Contracts\Schema\LinkInterface
	 *
	 * @phpstan-param T $notification
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getSelfLink($notification): JsonApi\Contracts\Schema\LinkInterface
	{
		return new JsonApi\Schema\Link(
			false,
			$this->router->urlFor(
				TriggersModule\Constants::ROUTE_NAME_TRIGGER_NOTIFICATION,
				[
					Router\Routes::URL_TRIGGER_ID => $notification->getTrigger()->getPlainId(),
					Router\Routes::URL_ITEM_ID    => $notification->getPlainId(),
				]
			),
			false
		);
	}

	/**
	 * @param Entities\Notifications\INotification $notification
	 * @param JsonApi\Contracts\Schema\ContextInterface $context
	 *
	 * @return iterable<string, mixed>
	 *
	 * @phpstan-param T $notification
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationships($notification, JsonApi\Contracts\Schema\ContextInterface $context): iterable
	{
		return [
			self::RELATIONSHIPS_TRIGGER => [
				self::RELATIONSHIP_DATA          => $notification->getTrigger(),
				self::RELATIONSHIP_LINKS_SELF    => true,
				self::RELATIONSHIP_LINKS_RELATED => true,
			],
		];
	}

	/**
	 * @param Entities\Notifications\INotification $notification
	 * @param string $name
	 *
	 * @return JsonApi\Contracts\Schema\LinkInterface
	 *
	 * @phpstan-param T $notification
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationshipRelatedLink($notification, string $name): JsonApi\Contracts\Schema\LinkInterface
	{
		if ($name === self::RELATIONSHIPS_TRIGGER) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					TriggersModule\Constants::ROUTE_NAME_TRIGGER,
					[
						Router\Routes::URL_ITEM_ID => $notification->getTrigger()->getPlainId(),
					]
				),
				false
			);
		}

		return parent::getRelationshipRelatedLink($notification, $name);
	}

	/**
	 * @param Entities\Notifications\INotification $notification
	 * @param string $name
	 *
	 * @return JsonApi\Contracts\Schema\LinkInterface
	 *
	 * @phpstan-param T $notification
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationshipSelfLink($notification, string $name): JsonApi\Contracts\Schema\LinkInterface
	{
		if ($name === self::RELATIONSHIPS_TRIGGER) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					TriggersModule\Constants::ROUTE_NAME_TRIGGER_NOTIFICATION_RELATIONSHIP,
					[
						Router\Routes::URL_TRIGGER_ID  => $notification->getTrigger()->getPlainId(),
						Router\Routes::URL_ITEM_ID     => $notification->getPlainId(),
						Router\Routes::RELATION_ENTITY => $name,
					]
				),
				false
			);
		}

		return parent::getRelationshipSelfLink($notification, $name);
	}

}
