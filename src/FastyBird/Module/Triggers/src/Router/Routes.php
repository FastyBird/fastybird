<?php declare(strict_types = 1);

/**
 * Routes.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Router
 * @since          0.1.0
 *
 * @date           04.04.20
 */

namespace FastyBird\TriggersModule\Router;

use FastyBird\Metadata;
use FastyBird\SimpleAuth\Middleware as SimpleAuthMiddleware;
use FastyBird\TriggersModule;
use FastyBird\TriggersModule\Controllers;
use FastyBird\TriggersModule\Middleware;
use IPub\SlimRouter\Routing;

/**
 * Module router configuration
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Router
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Routes
{

	public const URL_ITEM_ID = 'id';

	public const URL_TRIGGER_ID = 'trigger';

	public const RELATION_ENTITY = 'relationEntity';

	/** @var bool */
	private bool $usePrefix;

	/** @var Controllers\TriggersV1Controller */
	private Controllers\TriggersV1Controller $triggersV1Controller;

	/** @var Controllers\ActionsV1Controller */
	private Controllers\ActionsV1Controller $actionsV1Controller;

	/** @var Controllers\NotificationsV1Controller */
	private Controllers\NotificationsV1Controller $notificationsV1Controller;

	/** @var Controllers\ConditionsV1Controller */
	private Controllers\ConditionsV1Controller $conditionsV1Controller;

	/** @var Controllers\TriggerControlsV1Controller */
	private Controllers\TriggerControlsV1Controller $controlsV1Controller;

	/** @var Middleware\AccessMiddleware */
	private Middleware\AccessMiddleware $devicesAccessControlMiddleware;

	/** @var SimpleAuthMiddleware\AccessMiddleware */
	private SimpleAuthMiddleware\AccessMiddleware $accessControlMiddleware;

	/** @var SimpleAuthMiddleware\UserMiddleware */
	private SimpleAuthMiddleware\UserMiddleware $userMiddleware;

	public function __construct(
		bool $usePrefix,
		Controllers\TriggersV1Controller $triggersV1Controller,
		Controllers\ActionsV1Controller $actionsV1Controller,
		Controllers\NotificationsV1Controller $notificationsV1Controller,
		Controllers\ConditionsV1Controller $conditionsV1Controller,
		Controllers\TriggerControlsV1Controller $controlsV1Controller,
		Middleware\AccessMiddleware $devicesAccessControlMiddleware,
		SimpleAuthMiddleware\AccessMiddleware $accessControlMiddleware,
		SimpleAuthMiddleware\UserMiddleware $userMiddleware
	) {
		$this->usePrefix = $usePrefix;

		$this->triggersV1Controller = $triggersV1Controller;
		$this->actionsV1Controller = $actionsV1Controller;
		$this->notificationsV1Controller = $notificationsV1Controller;
		$this->conditionsV1Controller = $conditionsV1Controller;
		$this->controlsV1Controller = $controlsV1Controller;

		$this->devicesAccessControlMiddleware = $devicesAccessControlMiddleware;
		$this->accessControlMiddleware = $accessControlMiddleware;
		$this->userMiddleware = $userMiddleware;
	}

	/**
	 * @param Routing\IRouter $router
	 *
	 * @return void
	 */
	public function registerRoutes(Routing\IRouter $router): void
	{
		if ($this->usePrefix) {
			$routes = $router->group('/' . Metadata\Constants::MODULE_TRIGGERS_PREFIX, function (
				Routing\RouteCollector $group
			): void {
				$this->buildRoutes($group);
			});

		} else {
			$routes = $this->buildRoutes($router);
		}

		$routes->addMiddleware($this->accessControlMiddleware);
		$routes->addMiddleware($this->userMiddleware);
		$routes->addMiddleware($this->devicesAccessControlMiddleware);
	}

	/**
	 * @param Routing\IRouter | Routing\IRouteCollector $group
	 *
	 * @return Routing\IRouteGroup
	 */
	private function buildRoutes($group): Routing\IRouteGroup
	{
		return $group->group('/v1', function (Routing\RouteCollector $group): void {
			$group->group('/triggers', function (Routing\RouteCollector $group): void {
				/**
				 * TRIGGERS
				 */
				$route = $group->get('', [$this->triggersV1Controller, 'index']);
				$route->setName(TriggersModule\Constants::ROUTE_NAME_TRIGGERS);

				$route = $group->get('/{' . self::URL_ITEM_ID . '}', [$this->triggersV1Controller, 'read']);
				$route->setName(TriggersModule\Constants::ROUTE_NAME_TRIGGER);

				$group->post('', [$this->triggersV1Controller, 'create']);

				$group->patch('/{' . self::URL_ITEM_ID . '}', [$this->triggersV1Controller, 'update']);

				$group->delete('/{' . self::URL_ITEM_ID . '}', [$this->triggersV1Controller, 'delete']);

				$route = $group->get('/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}', [
					$this->triggersV1Controller,
					'readRelationship',
				]);
				$route->setName(TriggersModule\Constants::ROUTE_NAME_TRIGGER_RELATIONSHIP);
			});

			$group->group('/triggers/{' . self::URL_TRIGGER_ID . '}', function (Routing\RouteCollector $group): void {
				$group->group('/actions', function (Routing\RouteCollector $group): void {
					/**
					 * ACTIONS
					 */
					$route = $group->get('', [$this->actionsV1Controller, 'index']);
					$route->setName(TriggersModule\Constants::ROUTE_NAME_TRIGGER_ACTIONS);

					$route = $group->get('/{' . self::URL_ITEM_ID . '}', [$this->actionsV1Controller, 'read']);
					$route->setName(TriggersModule\Constants::ROUTE_NAME_TRIGGER_ACTION);

					$group->post('', [$this->actionsV1Controller, 'create']);

					$group->patch('/{' . self::URL_ITEM_ID . '}', [$this->actionsV1Controller, 'update']);

					$group->delete('/{' . self::URL_ITEM_ID . '}', [$this->actionsV1Controller, 'delete']);

					$route = $group->get('/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}', [
						$this->actionsV1Controller,
						'readRelationship',
					]);
					$route->setName(TriggersModule\Constants::ROUTE_NAME_TRIGGER_ACTION_RELATIONSHIP);
				});

				$group->group('/notifications', function (Routing\RouteCollector $group): void {
					/**
					 * NOTIFICATIONS
					 */
					$route = $group->get('', [$this->notificationsV1Controller, 'index']);
					$route->setName(TriggersModule\Constants::ROUTE_NAME_TRIGGER_NOTIFICATIONS);

					$route = $group->get('/{' . self::URL_ITEM_ID . '}', [$this->notificationsV1Controller, 'read']);
					$route->setName(TriggersModule\Constants::ROUTE_NAME_TRIGGER_NOTIFICATION);

					$group->post('', [$this->notificationsV1Controller, 'create']);

					$group->patch('/{' . self::URL_ITEM_ID . '}', [$this->notificationsV1Controller, 'update']);

					$group->delete('/{' . self::URL_ITEM_ID . '}', [$this->notificationsV1Controller, 'delete']);

					$route = $group->get('/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}', [
						$this->notificationsV1Controller,
						'readRelationship',
					]);
					$route->setName(TriggersModule\Constants::ROUTE_NAME_TRIGGER_NOTIFICATION_RELATIONSHIP);
				});

				$group->group('/conditions', function (Routing\RouteCollector $group): void {
					/**
					 * CONDITIONS
					 */
					$route = $group->get('', [$this->conditionsV1Controller, 'index']);
					$route->setName(TriggersModule\Constants::ROUTE_NAME_TRIGGER_CONDITIONS);

					$route = $group->get('/{' . self::URL_ITEM_ID . '}', [$this->conditionsV1Controller, 'read']);
					$route->setName(TriggersModule\Constants::ROUTE_NAME_TRIGGER_CONDITION);

					$group->post('', [$this->conditionsV1Controller, 'create']);

					$group->patch('/{' . self::URL_ITEM_ID . '}', [$this->conditionsV1Controller, 'update']);

					$group->delete('/{' . self::URL_ITEM_ID . '}', [$this->conditionsV1Controller, 'delete']);

					$route = $group->get('/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}', [
						$this->conditionsV1Controller,
						'readRelationship',
					]);
					$route->setName(TriggersModule\Constants::ROUTE_NAME_TRIGGER_CONDITION_RELATIONSHIP);
				});

				$group->group('/controls', function (Routing\RouteCollector $group): void {
					/**
					 * CONTROLS
					 */
					$route = $group->get('', [$this->controlsV1Controller, 'index']);
					$route->setName(TriggersModule\Constants::ROUTE_NAME_TRIGGER_CONTROLS);

					$route = $group->get('/{' . self::URL_ITEM_ID . '}', [$this->controlsV1Controller, 'read']);
					$route->setName(TriggersModule\Constants::ROUTE_NAME_TRIGGER_CONTROL);

					$group->post('', [$this->controlsV1Controller, 'create']);

					$group->patch('/{' . self::URL_ITEM_ID . '}', [$this->controlsV1Controller, 'update']);

					$group->delete('/{' . self::URL_ITEM_ID . '}', [$this->controlsV1Controller, 'delete']);

					$route = $group->get('/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}', [
						$this->controlsV1Controller,
						'readRelationship',
					]);
					$route->setName(TriggersModule\Constants::ROUTE_NAME_TRIGGER_CONTROL_RELATIONSHIP);
				});
			});
		});
	}

}
