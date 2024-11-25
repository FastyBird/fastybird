<?php declare(strict_types = 1);

/**
 * AppRouter.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Router
 * @since          1.0.0
 *
 * @date           05.07.24
 */

namespace FastyBird\Module\Accounts\Router;

use Nette\Application;
use Nette\Routing;

/**
 * Application router
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Router
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class AppRouter
{

	public static function createRouter(Application\Routers\RouteList $router): void
	{
		$list = $router->withModule('Accounts');

		$list->addRoute('/sign/<action>', [
			'presenter' => 'Sign',
			'action' => [
				Routing\Route::Pattern => 'in|up',
			],
		]);

		$list->addRoute('/reset-password', [
			'presenter' => 'Sign',
			'action' => 'reset',
		]);

		$list->addRoute('/account[/<action=default>]', [
			'presenter' => 'Account',
			'action' => [
				Routing\Route::Pattern => 'profile|password',
			],
		]);
	}

}
