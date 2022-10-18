<?php declare(strict_types = 1);

/**
 * Router.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WebServer!
 * @subpackage     Middleware
 * @since          0.1.0
 *
 * @date           08.05.21
 */

namespace FastyBird\Plugin\WebServer\Middleware;

use FastyBird\Plugin\WebServer\Events;
use IPub\SlimRouter\Routing;
use Psr\EventDispatcher;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Application router middleware
 *
 * @package        FastyBird:WebServer!
 * @subpackage     Middleware
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Router
{

	public function __construct(
		private readonly Routing\IRouter $router,
		private readonly EventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
	}

	public function __invoke(ServerRequestInterface $request): ResponseInterface
	{
		$this->dispatcher?->dispatch(new Events\Request($request));

		$response = $this->router->handle($request);

		$this->dispatcher?->dispatch(new Events\Response($request, $response));

		return $response;
	}

}
