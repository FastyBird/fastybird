<?php declare(strict_types = 1);

/**
 * AppRouter.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Application!
 * @subpackage     Router
 * @since          1.0.0
 *
 * @date           16.06.24
 */

namespace FastyBird\App\Router;

use Nette\Application;

/**
 * Application router
 *
 * @package        FastyBird:Application!
 * @subpackage     Router
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class AppRouter extends Application\Routers\RouteList
{

	public function __construct()
	{
		parent::__construct();

		$this->addRoute('/', [
			'module' => 'App',
			'presenter' => 'Default',
			'action' => 'default',
		]);
	}

}
