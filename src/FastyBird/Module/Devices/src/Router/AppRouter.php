<?php declare(strict_types = 1);

/**
 * AppRouter.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Router
 * @since          1.0.0
 *
 * @date           21.06.24
 */

namespace FastyBird\Module\Devices\Router;

use FastyBird\Library\Application\Router as ApplicationRouter;

/**
 * Application router
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Router
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class AppRouter
{

	public static function createRouter(ApplicationRouter\AppRouter $router): void
	{
		$list = $router->withModule('Devices');

		$list->addRoute('/devices/<id>/settings', [
			'presenter' => 'Devices',
			'action' => 'settings',
		]);

		$list->addRoute('/devices/<id>/settings/channel/add', [
			'presenter' => 'Channels',
			'action' => 'add',
		]);

		$list->addRoute('/devices/<id>/settings/channel/<channelId>', [
			'presenter' => 'Channels',
			'action' => 'edit',
		]);

		$list->addRoute('/devices[/<id>]', [
			'presenter' => 'Devices',
			'action' => 'default',
		]);

		$list->addRoute('/connectors/<id>/settings', [
			'presenter' => 'Connectors',
			'action' => 'settings',
		]);

		$list->addRoute('/connectors/<id>/settings/device/add', [
			'presenter' => 'Connectors',
			'action' => 'settings',
		]);

		$list->addRoute('/connectors/<id>/settings/device/<deviceId>', [
			'presenter' => 'Connectors',
			'action' => 'settings',
		]);

		$list->addRoute('/connectors/<id>/settings/device/<deviceId>/channel/add', [
			'presenter' => 'Connectors',
			'action' => 'settings',
		]);

		$list->addRoute('/connectors/<id>/settings/device/<deviceId>/channel/<channelId>', [
			'presenter' => 'Connectors',
			'action' => 'settings',
		]);

		$list->addRoute('/connectors[/<id>]', [
			'presenter' => 'Connectors',
			'action' => 'default',
		]);
	}

}
