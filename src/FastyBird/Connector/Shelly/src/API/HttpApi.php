<?php declare(strict_types = 1);

/**
 * HttpApi.php
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

use FastyBird\Connector\Shelly\Exceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp;
use Nette;
use Nette\Utils;
use Psr\Http\Message;
use Psr\Log;
use React\EventLoop;
use React\Http;
use React\Promise;
use Throwable;
use function array_key_exists;
use function array_merge;
use function base64_encode;
use function count;
use function hash;
use function http_build_query;
use function implode;
use function parse_url;
use function preg_match_all;
use function sprintf;
use function time;
use const PHP_URL_PATH;

/**
 * Device http api interface
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class HttpApi
{

	use Nette\SmartObject;

	private const REQUEST_AUTHORIZATION_HEADER = 'Authorization';

	private const RESPONSE_AUTHENTICATION_RESPONSE_HEADER = 'WWW-Authenticate';

	protected const AUTHORIZATION_BASIC = 'basic';

	protected const AUTHORIZATION_DIGEST = 'digest';

	protected GuzzleHttp\Client|null $client = null;

	protected Http\Browser|null $asyncClient = null;

	protected Log\LoggerInterface $logger;

	public function __construct(
		protected readonly EventLoop\LoopInterface $eventLoop,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * @param array<string, mixed> $params
	 * @param array<string, mixed> $headers
	 *
	 * @throws Exceptions\HttpApiCall
	 */
	protected function callRequest(
		string $method,
		string $path,
		array $params = [],
		array $headers = [],
		string|null $body = null,
		string|null $authorization = null,
		string|null $username = null,
		string|null $password = null,
	): Message\ResponseInterface
	{
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

		$options = [
			GuzzleHttp\RequestOptions::HEADERS => $headers,
			GuzzleHttp\RequestOptions::BODY => $body ?? '',
		];

		if ($authorization === self::AUTHORIZATION_BASIC) {
			$options[GuzzleHttp\RequestOptions::AUTH] = [$username, $password];
		} elseif ($authorization === self::AUTHORIZATION_DIGEST) {
			$options[GuzzleHttp\RequestOptions::AUTH] = [$username, $password, 'digest'];
		}

		try {
			if ($this->client === null) {
				$this->client = new GuzzleHttp\Client();
			}

			return $this->client->request($method, $path, $options);
		} catch (Throwable $ex) {
			throw new Exceptions\HttpApiCall('Calling api endpoint failed', $ex->getCode(), $ex);
		}
	}

	/**
	 * @param array<string, mixed> $params
	 * @param array<string, mixed> $headers
	 */
	protected function callAsyncRequest(
		string $method,
		string $path,
		array $params = [],
		array $headers = [],
		string|null $body = null,
		string|null $authorization = null,
		string|null $username = null,
		string|null $password = null,
		bool $handlingAuthentication = false,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface
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

		if ($authorization === self::AUTHORIZATION_BASIC) {
			$headers[self::REQUEST_AUTHORIZATION_HEADER] = 'Basic ' . base64_encode($username . ':' . $password);
		}

		try {
			if ($this->asyncClient === null) {
				$this->asyncClient = new Http\Browser(null, $this->eventLoop);
			}

			$this->asyncClient->request($method, $path, $headers, $body ?? '')
				->then(
					static function (Message\ResponseInterface $response) use ($deferred): void {
						$deferred->resolve($response);
					},
					function (Throwable $ex) use (
						$deferred,
						$method,
						$path,
						$params,
						$headers,
						$body,
						$authorization,
						$username,
						$password,
						$handlingAuthentication,
					): void {
						if (
							$authorization === self::AUTHORIZATION_DIGEST
							&& !$handlingAuthentication
							&& $ex instanceof Http\Message\ResponseException
							&& $ex->getResponse()->getStatusCode() === StatusCodeInterface::STATUS_UNAUTHORIZED
							&& $ex->getResponse()->hasHeader(self::RESPONSE_AUTHENTICATION_RESPONSE_HEADER)
							&& count($ex->getResponse()->getHeader(self::RESPONSE_AUTHENTICATION_RESPONSE_HEADER)) > 0
							&& $username !== null
							&& $password !== null
						) {
							$authHeader = $this->parseAuthentication(
								$method,
								$path,
								$username,
								$password,
								$ex->getResponse()->getHeader(self::RESPONSE_AUTHENTICATION_RESPONSE_HEADER)[0],
							);

							if ($authHeader !== null) {
								$this->callAsyncRequest(
									$method,
									$path,
									$params,
									array_merge(
										$headers,
										[
											self::REQUEST_AUTHORIZATION_HEADER => $authHeader,
										],
									),
									$body,
									$authorization,
									null,
									null,
									true,
								)
									->then(
										static function (Message\ResponseInterface $response) use ($deferred): void {
											$deferred->resolve($response);
										},
									)
									->otherwise(static function (Throwable $ex) use ($deferred): void {
										$deferred->reject($ex);
									});

								return;
							}
						}

						$deferred->reject($ex);
					},
				);
		} catch (Throwable $ex) {
			$deferred->reject($ex);
		}

		return $deferred->promise();
	}

	protected function parseAuthentication(
		string $method,
		string $url,
		string $username,
		string $password,
		string $header,
	): string|null
	{
		// Parses the 'www-authenticate' header for nonce, realm and other values
		preg_match_all('#((\w+)="?([^\s"]+))#', $header, $matches);

		$serverBits = [];

		foreach ($matches[2] as $i => $key) {
			$serverBits[$key] = $matches[3][$i];
		}

		if (
			!array_key_exists('realm', $serverBits)
			|| !array_key_exists('nonce', $serverBits)
			|| !array_key_exists('qop', $serverBits)
		) {
			return null;
		}

		if (array_key_exists('algorithm', $serverBits)) {
			switch (Utils\Strings::lower($serverBits['algorithm'])) {
				case 'sha-256':
				case 'sha256':
					$algo = 'sha256';

					break;
				default:
					$algo = 'md5';
			}
		} else {
			$algo = 'md5';
		}

		$nc = 1;

		$path = parse_url($url, PHP_URL_PATH);

		$clientNonce = time();

		$ha1 = hash($algo, $username . ':' . $serverBits['realm'] . ':' . $password);
		$ha2 = hash($algo, $method . ':' . $path);

		// The order of this array matters, because it affects resulting hashed val
		$response = hash(
			$algo,
			implode(
				':',
				[
					$ha1,
					$serverBits['nonce'],
					$nc,
					$clientNonce,
					'auth',
					$ha2,
				],
			),
		);

		$digestHeaderValues = [
			'username' => $username,
			'realm' => $serverBits['realm'],
			'nonce' => $serverBits['nonce'],
			'cnonce' => $clientNonce,
			'uri' => $path,
			'response' => $response,
			'qop' => $serverBits['qop'],
			'nc' => $nc,
			'algorithm' => array_key_exists('algorithm', $serverBits) ? $serverBits['algorithm'] : 'MD5',
		];

		$digestHeader = [];

		foreach ($digestHeaderValues as $key => $value) {
			$digestHeader[] = $key . '=' . $value;
		}

		return 'Digest ' . implode(', ', $digestHeader);
	}

}
