<?php declare(strict_types = 1);

/**
 * Router.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Middleware
 * @since          1.0.0
 *
 * @date           19.09.22
 */

namespace FastyBird\Connector\NsPanel\Middleware;

use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\Events;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Servers;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use Fig\Http\Message\StatusCodeInterface;
use InvalidArgumentException;
use IPub\SlimRouter;
use IPub\SlimRouter\Exceptions as SlimRouterExceptions;
use IPub\SlimRouter\Http as SlimRouterHttp;
use IPub\SlimRouter\Routing as SlimRouterRouting;
use Nette\Utils;
use Psr\EventDispatcher;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid;
use Throwable;

/**
 * Connector HTTP server router middleware
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Middleware
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Router
{

	private SlimRouterHttp\ResponseFactory $responseFactory;

	public function __construct(
		private readonly SlimRouterRouting\IRouter $router,
		private readonly NsPanel\Logger $logger,
		private readonly EventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
		$this->responseFactory = new SlimRouterHttp\ResponseFactory();
	}

	/**
	 * @throws InvalidArgumentException
	 * @throws Utils\JsonException
	 */
	public function __invoke(ServerRequestInterface $request): ResponseInterface
	{
		$this->dispatcher?->dispatch(new Events\Request($request));

		try {
			$response = $this->router->handle($request);
			$response = $response->withHeader('Server', 'FastyBird NS Panel Connector');

		} catch (Exceptions\ServerRequestError $ex) {
			$this->logger->warning(
				'Request ended with error',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
					'type' => 'router-middleware',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
					'request' => [
						'method' => $request->getMethod(),
						'path' => $request->getUri()->getPath(),
					],
				],
			);

			$response = $this->responseFactory->createResponse($ex->getCode());

			$response = $response->withHeader('Content-Type', Servers\Http::JSON_CONTENT_TYPE);
			$response = $response->withBody(SlimRouter\Http\Stream::fromBodyString(Utils\Json::encode([
				'event' => [
					'header' => [
						'name' => Types\Header::ERROR_RESPONSE,
						'message_id' => Uuid\Uuid::uuid4()->toString(),
						'version' => '1',
					],
					'payload' => [
						'type' => Types\ServerStatus::INTERNAL_ERROR,
					],
				],
			])));
		} catch (SlimRouterExceptions\HttpException $ex) {
			$this->logger->warning(
				'Received invalid HTTP request',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
					'type' => 'router-middleware',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
					'request' => [
						'method' => $request->getMethod(),
						'path' => $request->getUri()->getPath(),
					],
				],
			);

			$response = $this->responseFactory->createResponse($ex->getCode());

			$response = $response->withHeader('Content-Type', Servers\Http::JSON_CONTENT_TYPE);
			$response = $response->withBody(SlimRouter\Http\Stream::fromBodyString(Utils\Json::encode([
				'event' => [
					'header' => [
						'name' => Types\Header::ERROR_RESPONSE,
						'message_id' => Uuid\Uuid::uuid4()->toString(),
						'version' => '1',
					],
					'payload' => [
						'type' => Types\ServerStatus::INTERNAL_ERROR,
					],
				],
			])));
		} catch (Throwable $ex) {
			$this->logger->error(
				'An unhandled error occurred during handling server HTTP request',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
					'type' => 'router-middleware',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$response = $this->responseFactory->createResponse(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);

			$response = $response->withHeader('Content-Type', Servers\Http::JSON_CONTENT_TYPE);
			$response = $response->withBody(SlimRouter\Http\Stream::fromBodyString(Utils\Json::encode([
				'event' => [
					'header' => [
						'name' => Types\Header::ERROR_RESPONSE,
						'message_id' => Uuid\Uuid::uuid4()->toString(),
						'version' => '1',
					],
					'payload' => [
						'type' => Types\ServerStatus::INTERNAL_ERROR,
					],
				],
			])));
		}

		$this->dispatcher?->dispatch(new Events\Response($request, $response));

		return $response;
	}

}