<?php declare(strict_types = 1);

/**
 * Gen1HttpApi.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           20.12.22
 */

namespace FastyBird\Connector\Shelly\API;

use FastyBird\Connector\Shelly;
use FastyBird\Connector\Shelly\Exceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use GuzzleHttp;
use InvalidArgumentException;
use Nette;
use Nette\Utils;
use Psr\Http\Message;
use Psr\Log;
use React\EventLoop;
use React\Http;
use React\Promise;
use Throwable;
use function assert;
use function count;
use function http_build_query;
use function sprintf;
use const DIRECTORY_SEPARATOR;

/**
 * Generation 1 device http api interface
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class HttpApi
{

	use Nette\SmartObject;

	private GuzzleHttp\Client|null $client = null;

	private Http\Browser|null $asyncClient = null;

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly EventLoop\LoopInterface $eventLoop,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * @param array<string, mixed> $params
	 */
	protected function callRequest(
		string $method,
		string $path,
		array $params = [],
		string|null $body = null,
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|Message\ResponseInterface|false
	{
		$deferred = new Promise\Deferred();

		$this->logger->debug(sprintf(
			'Request: method = %s url = %s',
			$method,
			$path,
		), [
			'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
			'type' => 'http-api',
			'request' => [
				'method' => $method,
				'url' => $path,
				'params' => $params,
				'body' => $body,
			],
		]);

		if (count($params) > 0) {
			$path .= '?';
			$path .= http_build_query($params);
		}

		if ($async) {
			try {
				$request = $this->getClient()->request(
					$method,
					$path,
					[],
					$body ?? '',
				);

				assert($request instanceof Promise\PromiseInterface);

				$request
					->then(
						static function (Message\ResponseInterface $response) use ($deferred): void {
							$deferred->resolve($response);
						},
						function (Throwable $ex) use ($deferred, $method, $path, $params, $body): void {
							$this->logger->error('Calling api endpoint failed', [
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
								'type' => 'http-api',
								'exception' => [
									'message' => $ex->getMessage(),
									'code' => $ex->getCode(),
								],
								'request' => [
									'method' => $method,
									'url' => $path,
									'params' => $params,
									'body' => $body,
								],
							]);

							$deferred->reject($ex);
						},
					);
			} catch (Throwable $ex) {
				$deferred->reject($ex);
			}

			return $deferred->promise();
		} else {
			try {
				$response = $this->getClient(false)->request(
					$method,
					$path,
					[
						'body' => $body ?? '',
					],
				);

				assert($response instanceof Message\ResponseInterface);

			} catch (GuzzleHttp\Exception\GuzzleException | InvalidArgumentException $ex) {
				$this->logger->error('Calling api endpoint failed', [
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
					'type' => 'http-api',
					'exception' => [
						'message' => $ex->getMessage(),
						'code' => $ex->getCode(),
					],
					'request' => [
						'method' => $method,
						'url' => $path,
						'params' => $params,
						'body' => $body,
					],
				]);

				return false;
			} catch (Exceptions\HttpApiCall $ex) {
				$this->logger->error('Received payload is not valid', [
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
					'type' => 'http-api',
					'exception' => [
						'message' => $ex->getMessage(),
						'code' => $ex->getCode(),
					],
					'request' => [
						'method' => $method,
						'url' => $path,
						'params' => $params,
						'body' => $body,
					],
				]);

				return false;
			}

			return $response;
		}
	}

	/**
	 * @throws InvalidArgumentException
	 */
	protected function getClient(bool $async = true): GuzzleHttp\Client|Http\Browser
	{
		if ($async) {
			if ($this->asyncClient === null) {
				$this->asyncClient = new Http\Browser(null, $this->eventLoop);
			}

			return $this->asyncClient;
		} else {
			if ($this->client === null) {
				$this->client = new GuzzleHttp\Client();
			}

			return $this->client;
		}
	}

	/**
	 * @throws Exceptions\HttpApiCall
	 */
	protected function getSchemaFilePath(string $schemaFilename): string
	{
		try {
			$schema = Utils\FileSystem::read(
				Shelly\Constants::RESOURCES_FOLDER . DIRECTORY_SEPARATOR . $schemaFilename,
			);

		} catch (Nette\IOException) {
			throw new Exceptions\HttpApiCall('Validation schema for response could not be loaded');
		}

		return $schema;
	}

}
