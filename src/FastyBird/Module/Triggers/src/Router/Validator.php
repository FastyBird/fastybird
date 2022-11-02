<?php declare(strict_types = 1);

/**
 * Routes.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Router
 * @since          0.6.0
 *
 * @date           11.01.22
 */

namespace FastyBird\TriggersModule\Router;

use FastRoute;
use FastRoute\RouteCollector as FastRouteCollector;
use FastRoute\RouteParser\Std;
use Fig\Http\Message\RequestMethodInterface;
use IPub\SlimRouter\Routing as SlimRouterRouting;
use Nette\DI;

/**
 * Route validator
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Router
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Validator
{

	/** @var DI\Container */
	private DI\Container $container;

	/** @var SlimRouterRouting\FastRouteDispatcher|null */
	private ?SlimRouterRouting\FastRouteDispatcher $routerDispatcher = null;

	public function __construct(
		DI\Container $container
	) {
		$this->container = $container;
	}

	/**
	 * @param string $link
	 * @param string $method
	 *
	 * @return bool
	 */
	public function validate(string $link, string $method = RequestMethodInterface::METHOD_GET): bool
	{
		$results = $this->getRouterDispatcher()
			->dispatch($method, $link);

		return $results[0] === SlimRouterRouting\RoutingResults::FOUND;
	}

	private function getRouterDispatcher(): SlimRouterRouting\FastRouteDispatcher
	{
		if ($this->routerDispatcher !== null) {
			return $this->routerDispatcher;
		}

		$router = $this->container->getByType(SlimRouterRouting\IRouter::class);

		$routeDefinitionCallback = function (FastRouteCollector $r) use ($router): void {
			$basePath = $router->getBasePath();

			/** @var SlimRouterRouting\IRoute $route */
			foreach ($router->getIterator() as $route) {
				$r->addRoute($route->getMethods(), $basePath . $route->getPattern(), $route->getIdentifier());
			}
		};

		/** @var SlimRouterRouting\FastRouteDispatcher $dispatcher */
		$dispatcher = FastRoute\simpleDispatcher($routeDefinitionCallback, [
			'dispatcher'  => SlimRouterRouting\FastRouteDispatcher::class,
			'routeParser' => new Std(),
		]);

		$this->routerDispatcher = $dispatcher;

		return $this->routerDispatcher;
	}

}
