<?php declare(strict_types = 1);

/**
 * Routes.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Router
 * @since          0.1.0
 *
 * @date           13.03.20
 */

namespace FastyBird\Module\Devices\Router;

use FastyBird\Library\Metadata;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Controllers;
use FastyBird\Module\Devices\Middleware;
use FastyBird\SimpleAuth\Middleware as SimpleAuthMiddleware;
use IPub\SlimRouter\Routing;

/**
 * Module routes configuration
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Router
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Routes
{

	public const URL_ITEM_ID = 'id';

	public const URL_DEVICE_ID = 'device';

	public const URL_CHANNEL_ID = 'channel';

	public const URL_CONNECTOR_ID = 'connector';

	public const URL_PROPERTY_ID = 'property';

	public const RELATION_ENTITY = 'relationEntity';

	public function __construct(
		private readonly bool $usePrefix,
		private readonly Controllers\DevicesV1 $devicesV1Controller,
		private readonly Controllers\DeviceParentsV1 $deviceParentsV1Controller,
		private readonly Controllers\DeviceChildrenV1 $deviceChildrenV1Controller,
		private readonly Controllers\DevicePropertiesV1 $devicePropertiesV1Controller,
		private readonly Controllers\DevicePropertyChildrenV1 $devicePropertyChildrenV1Controller,
		private readonly Controllers\DeviceControlsV1 $deviceControlsV1Controller,
		private readonly Controllers\DeviceAttributesV1 $deviceAttributesV1Controller,
		private readonly Controllers\ChannelsV1 $channelsV1Controller,
		private readonly Controllers\ChannelPropertiesV1 $channelPropertiesV1Controller,
		private readonly Controllers\ChannelPropertyChildrenV1 $channelPropertyChildrenV1Controller,
		private readonly Controllers\ChannelControlsV1 $channelControlsV1Controller,
		private readonly Controllers\ConnectorsV1 $connectorsV1Controller,
		private readonly Controllers\ConnectorPropertiesV1 $connectorPropertiesV1Controller,
		private readonly Controllers\ConnectorControlsV1 $connectorControlsV1Controller,
		private readonly Middleware\Access $devicesAccessControlMiddleware,
		private readonly SimpleAuthMiddleware\Access $accessControlMiddleware,
		private readonly SimpleAuthMiddleware\User $userMiddleware,
	)
	{
	}

	public function registerRoutes(Routing\IRouter $router): void
	{
		if ($this->usePrefix) {
			$routes = $router->group('/' . Metadata\Constants::MODULE_DEVICES_PREFIX, function (
				Routing\RouteCollector $group,
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

	private function buildRoutes(Routing\IRouter|Routing\IRouteCollector $group): Routing\IRouteGroup
	{
		return $group->group('/v1', function (Routing\RouteCollector $group): void {
			/**
			 * DEVICES
			 */
			$group->group('/devices', function (Routing\RouteCollector $group): void {
				$route = $group->get('', [$this->devicesV1Controller, 'index']);
				$route->setName(Devices\Constants::ROUTE_NAME_DEVICES);

				$route = $group->get('/{' . self::URL_ITEM_ID . '}', [$this->devicesV1Controller, 'read']);
				$route->setName(Devices\Constants::ROUTE_NAME_DEVICE);

				$group->post('', [$this->devicesV1Controller, 'create']);

				$group->patch('/{' . self::URL_ITEM_ID . '}', [$this->devicesV1Controller, 'update']);

				$group->delete('/{' . self::URL_ITEM_ID . '}', [$this->devicesV1Controller, 'delete']);

				$route = $group->get('/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}', [
					$this->devicesV1Controller,
					'readRelationship',
				]);
				$route->setName(Devices\Constants::ROUTE_NAME_DEVICE_RELATIONSHIP);

				$group->group('/{' . self::URL_DEVICE_ID . '}', function (Routing\RouteCollector $group): void {
					/**
					 * PARENTS
					 */
					$route = $group->get('/parents', [$this->deviceParentsV1Controller, 'index']);
					$route->setName(Devices\Constants::ROUTE_NAME_DEVICE_PARENTS);

					/**
					 * CHILDREN
					 */
					$route = $group->get('/children', [$this->deviceChildrenV1Controller, 'index']);
					$route->setName(Devices\Constants::ROUTE_NAME_DEVICE_CHILDREN);

					/**
					 * DEVICE PROPERTIES
					 */
					$group->group('/properties', function (Routing\RouteCollector $group): void {
						$route = $group->get('', [$this->devicePropertiesV1Controller, 'index']);
						$route->setName(Devices\Constants::ROUTE_NAME_DEVICE_PROPERTIES);

						$route = $group->get('/{' . self::URL_ITEM_ID . '}', [
							$this->devicePropertiesV1Controller,
							'read',
						]);
						$route->setName(Devices\Constants::ROUTE_NAME_DEVICE_PROPERTY);

						$group->post('', [$this->devicePropertiesV1Controller, 'create']);

						$group->patch('/{' . self::URL_ITEM_ID . '}', [$this->devicePropertiesV1Controller, 'update']);

						$group->delete('/{' . self::URL_ITEM_ID . '}', [$this->devicePropertiesV1Controller, 'delete']);

						$route = $group->get(
							'/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}',
							[
								$this->devicePropertiesV1Controller,
								'readRelationship',
							],
						);
						$route->setName(Devices\Constants::ROUTE_NAME_DEVICE_PROPERTY_RELATIONSHIP);

						$group->group(
							'/{' . self::URL_PROPERTY_ID . '}',
							function (Routing\RouteCollector $group): void {
								/**
								 * CHILDREN
								 */
								$route = $group->get('/children', [$this->devicePropertyChildrenV1Controller, 'index']);
								$route->setName(Devices\Constants::ROUTE_NAME_DEVICE_PROPERTY_CHILDREN);
							},
						);
					});

					/**
					 * DEVICE CONTROLS
					 */
					$group->group('/controls', function (Routing\RouteCollector $group): void {
						$route = $group->get('', [$this->deviceControlsV1Controller, 'index']);
						$route->setName(Devices\Constants::ROUTE_NAME_DEVICE_CONTROLS);

						$route = $group->get('/{' . self::URL_ITEM_ID . '}', [
							$this->deviceControlsV1Controller,
							'read',
						]);
						$route->setName(Devices\Constants::ROUTE_NAME_DEVICE_CONTROL);

						$route = $group->get(
							'/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}',
							[
								$this->deviceControlsV1Controller,
								'readRelationship',
							],
						);
						$route->setName(Devices\Constants::ROUTE_NAME_DEVICE_CONTROL_RELATIONSHIP);
					});

					/**
					 * DEVICE ATTRIBUTES
					 */
					$group->group('/attributes', function (Routing\RouteCollector $group): void {
						$route = $group->get('', [$this->deviceAttributesV1Controller, 'index']);
						$route->setName(Devices\Constants::ROUTE_NAME_DEVICE_ATTRIBUTES);

						$route = $group->get('/{' . self::URL_ITEM_ID . '}', [
							$this->deviceAttributesV1Controller,
							'read',
						]);
						$route->setName(Devices\Constants::ROUTE_NAME_DEVICE_ATTRIBUTE);

						$route = $group->get(
							'/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}',
							[
								$this->deviceAttributesV1Controller,
								'readRelationship',
							],
						);
						$route->setName(Devices\Constants::ROUTE_NAME_DEVICE_ATTRIBUTE_RELATIONSHIP);
					});

					/**
					 * CHANNELS
					 */
					$group->group('/channels', function (Routing\RouteCollector $group): void {
						$route = $group->get('', [$this->channelsV1Controller, 'index']);
						$route->setName(Devices\Constants::ROUTE_NAME_CHANNELS);

						$route = $group->get('/{' . self::URL_ITEM_ID . '}', [$this->channelsV1Controller, 'read']);
						$route->setName(Devices\Constants::ROUTE_NAME_CHANNEL);

						$group->post('', [$this->channelsV1Controller, 'create']);

						$group->patch('/{' . self::URL_ITEM_ID . '}', [$this->channelsV1Controller, 'update']);

						$group->delete('/{' . self::URL_ITEM_ID . '}', [$this->channelsV1Controller, 'delete']);

						$route = $group->get(
							'/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}',
							[
								$this->channelsV1Controller,
								'readRelationship',
							],
						);
						$route->setName(Devices\Constants::ROUTE_NAME_CHANNEL_RELATIONSHIP);

						$group->group(
							'/{' . self::URL_CHANNEL_ID . '}',
							function (Routing\RouteCollector $group): void {
								/**
								 * CHANNEL PROPERTIES
								 */
								$group->group('/properties', function (Routing\RouteCollector $group): void {
									$route = $group->get('', [$this->channelPropertiesV1Controller, 'index']);
									$route->setName(Devices\Constants::ROUTE_NAME_CHANNEL_PROPERTIES);

									$route = $group->get('/{' . self::URL_ITEM_ID . '}', [
										$this->channelPropertiesV1Controller,
										'read',
									]);
									$route->setName(Devices\Constants::ROUTE_NAME_CHANNEL_PROPERTY);

									$group->post('', [$this->channelPropertiesV1Controller, 'create']);

									$group->patch('/{' . self::URL_ITEM_ID . '}', [
										$this->channelPropertiesV1Controller,
										'update',
									]);

									$group->delete('/{' . self::URL_ITEM_ID . '}', [
										$this->channelPropertiesV1Controller,
										'delete',
									]);

									$route = $group->get(
										'/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}',
										[
											$this->channelPropertiesV1Controller,
											'readRelationship',
										],
									);
									$route->setName(Devices\Constants::ROUTE_NAME_CHANNEL_PROPERTY_RELATIONSHIP);

									$group->group('/{' . self::URL_PROPERTY_ID . '}', function (
										Routing\RouteCollector $group,
									): void {
										/**
										 * CHILDREN
										 */
										$route = $group->get('/children', [
											$this->channelPropertyChildrenV1Controller,
											'index',
										]);
										$route->setName(Devices\Constants::ROUTE_NAME_CHANNEL_PROPERTY_CHILDREN);
									});
								});

								/**
								 * CHANNEL CONTROLS
								 */
								$group->group('/controls', function (Routing\RouteCollector $group): void {
									$route = $group->get('', [$this->channelControlsV1Controller, 'index']);
									$route->setName(Devices\Constants::ROUTE_NAME_CHANNEL_CONTROLS);

									$route = $group->get('/{' . self::URL_ITEM_ID . '}', [
										$this->channelControlsV1Controller,
										'read',
									]);
									$route->setName(Devices\Constants::ROUTE_NAME_CHANNEL_CONTROL);

									$route = $group->get(
										'/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}',
										[
											$this->channelControlsV1Controller,
											'readRelationship',
										],
									);
									$route->setName(Devices\Constants::ROUTE_NAME_CHANNEL_CONTROL_RELATIONSHIP);
								});
							},
						);
					});
				});
			});

			/**
			 * CONNECTORS
			 */
			$group->group('/connectors', function (Routing\RouteCollector $group): void {
				$route = $group->get('', [$this->connectorsV1Controller, 'index']);
				$route->setName(Devices\Constants::ROUTE_NAME_CONNECTORS);

				$route = $group->get('/{' . self::URL_ITEM_ID . '}', [$this->connectorsV1Controller, 'read']);
				$route->setName(Devices\Constants::ROUTE_NAME_CONNECTOR);

				$group->patch('/{' . self::URL_ITEM_ID . '}', [$this->connectorsV1Controller, 'update']);

				$route = $group->get('/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}', [
					$this->connectorsV1Controller,
					'readRelationship',
				]);
				$route->setName(Devices\Constants::ROUTE_NAME_CONNECTOR_RELATIONSHIP);

				$group->group('/{' . self::URL_CONNECTOR_ID . '}', function (Routing\RouteCollector $group): void {
					/**
					 * CONNECTOR PROPERTIES
					 */
					$group->group('/properties', function (Routing\RouteCollector $group): void {
						$route = $group->get('', [$this->connectorPropertiesV1Controller, 'index']);
						$route->setName(Devices\Constants::ROUTE_NAME_CONNECTOR_PROPERTIES);

						$route = $group->get('/{' . self::URL_ITEM_ID . '}', [
							$this->connectorPropertiesV1Controller,
							'read',
						]);
						$route->setName(Devices\Constants::ROUTE_NAME_CONNECTOR_PROPERTY);

						$group->post('', [$this->connectorPropertiesV1Controller, 'create']);

						$group->patch('/{' . self::URL_ITEM_ID . '}', [
							$this->connectorPropertiesV1Controller,
							'update',
						]);

						$group->delete('/{' . self::URL_ITEM_ID . '}', [
							$this->connectorPropertiesV1Controller,
							'delete',
						]);

						$route = $group->get(
							'/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}',
							[
								$this->connectorPropertiesV1Controller,
								'readRelationship',
							],
						);
						$route->setName(Devices\Constants::ROUTE_NAME_CONNECTOR_PROPERTY_RELATIONSHIP);
					});

					/**
					 * CONNECTOR CONTROLS
					 */
					$group->group('/controls', function (Routing\RouteCollector $group): void {
						$route = $group->get('', [$this->connectorControlsV1Controller, 'index']);
						$route->setName(Devices\Constants::ROUTE_NAME_CONNECTOR_CONTROLS);

						$route = $group->get('/{' . self::URL_ITEM_ID . '}', [
							$this->connectorControlsV1Controller,
							'read',
						]);
						$route->setName(Devices\Constants::ROUTE_NAME_CONNECTOR_CONTROL);

						$route = $group->get(
							'/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}',
							[
								$this->connectorControlsV1Controller,
								'readRelationship',
							],
						);
						$route->setName(Devices\Constants::ROUTE_NAME_CONNECTOR_CONTROL_RELATIONSHIP);
					});
				});
			});
		});
	}

}
