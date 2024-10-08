<?php declare(strict_types = 1);

/**
 * ApiRoutes.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnectorHomeKitConnectorBridge!
 * @subpackage     Router
 * @since          1.0.0
 *
 * @date           18.08.24
 */

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Router;

use FastyBird\Bridge\ShellyConnectorHomeKitConnector;
use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Controllers;
use FastyBird\Library\Metadata;
use FastyBird\Module\Devices\Middleware as DevicesMiddleware;
use FastyBird\SimpleAuth\Middleware as SimpleAuthMiddleware;
use IPub\SlimRouter\Routing;

/**
 * Bridge API routes configuration
 *
 * @package        FastyBird:ShellyConnectorHomeKitConnectorBridge!
 * @subpackage     Router
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ApiRoutes
{

	public const URL_ITEM_ID = 'id';

	public const RELATION_ENTITY = 'relationEntity';

	public function __construct(
		private readonly bool $usePrefix,
		private readonly Controllers\BridgesV1 $bridgesV1Controller,
		private readonly DevicesMiddleware\Access $devicesAccessControlMiddleware,
		private readonly SimpleAuthMiddleware\Authorization $authorizationMiddleware,
		private readonly SimpleAuthMiddleware\User $userMiddleware,
	)
	{
	}

	public function registerRoutes(Routing\IRouter $router): void
	{
		$routes = $router->group('/' . Metadata\Constants::ROUTER_API_PREFIX, function (
			Routing\RouteCollector $group,
		): void {
			if ($this->usePrefix) {
				$group->group(
					'/' . Metadata\Constants::BRIDGE_SHELLY_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX,
					function (
						Routing\RouteCollector $group,
					): void {
						$this->buildRoutes($group);
					},
				);

			} else {
				$this->buildRoutes($group);
			}
		});

		$routes->addMiddleware($this->authorizationMiddleware);
		$routes->addMiddleware($this->userMiddleware);
		$routes->addMiddleware($this->devicesAccessControlMiddleware);
	}

	private function buildRoutes(Routing\IRouter|Routing\IRouteCollector $group): Routing\IRouteGroup
	{
		return $group->group('/v1', function (Routing\RouteCollector $group): void {
			/**
			 * BRIDGES
			 */
			$group->group('/bridges', function (Routing\RouteCollector $group): void {
				$route = $group->get('', [$this->bridgesV1Controller, 'index']);
				$route->setName(ShellyConnectorHomeKitConnector\Constants::ROUTE_NAME_BRIDGES);

				$route = $group->get('/{' . self::URL_ITEM_ID . '}', [$this->bridgesV1Controller, 'read']);
				$route->setName(ShellyConnectorHomeKitConnector\Constants::ROUTE_NAME_BRIDGE);

				$group->post('', [$this->bridgesV1Controller, 'create']);

				$group->patch('/{' . self::URL_ITEM_ID . '}', [$this->bridgesV1Controller, 'update']);

				$group->delete('/{' . self::URL_ITEM_ID . '}', [$this->bridgesV1Controller, 'delete']);
			});
		});
	}

}
