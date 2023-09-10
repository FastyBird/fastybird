<?php declare(strict_types = 1);

/**
 * Gen2HttpApi.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           24.12.22
 */

namespace FastyBird\Connector\Shelly\API;

use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Exceptions;
use FastyBird\Connector\Shelly\Types;
use Fig\Http\Message\RequestMethodInterface;
use Fig\Http\Message\StatusCodeInterface;
use Nette;
use Nette\Utils;
use Psr\Http\Message;
use React\Promise;
use RuntimeException;
use Throwable;
use function array_key_exists;
use function array_merge;
use function intval;
use function preg_match;
use function sprintf;
use function uniqid;

/**
 * Generation 2 device http API interface
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Gen2HttpApi extends HttpApi
{

	use Nette\SmartObject;

	private const GET_DEVICE_INFORMATION_ENDPOINT = 'http://%s/rpc/Shelly.GetDeviceInfo';

	private const GET_DEVICE_CONFIGURATION_ENDPOINT = 'http://%s/rpc/Shelly.GetConfig';

	private const GET_DEVICE_STATE_ENDPOINT = 'http://%s/rpc/Shelly.GetStatus';

	private const SET_DEVICE_STATE_ENDPOINT = 'http://%s/rpc';

	private const GET_DEVICE_INFORMATION_MESSAGE_SCHEMA_FILENAME = 'gen2_http_shelly.json';

	private const GET_DEVICE_CONFIG_MESSAGE_SCHEMA_FILENAME = 'gen2_http_config.json';

	private const GET_DEVICE_STATE_MESSAGE_SCHEMA_FILENAME = 'gen2_http_state.json';

	private const PROPERTY_COMPONENT = '/^(?P<component>[a-zA-Z]+)_(?P<identifier>[0-9]+)(_(?P<attribute>[a-zA-Z0-9]+))?$/';

	private const COMPONENT_KEY = '/^(?P<component>[a-zA-Z]+)(:(?P<channel>[0-9_]+))?$/';

	private const SWITCH_SET_METHOD = 'Switch.Set';

	private const COVER_GO_TO_POSITION_METHOD = 'Cover.GoToPosition';

	private const LIGHT_SET_METHOD = 'Light.Set';

	/**
	 * @return ($async is true ? Promise\ExtendedPromiseInterface|Promise\PromiseInterface : Entities\API\Gen2\GetDeviceInformation)
	 *
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\HttpApiCall
	 * @throws RuntimeException
	 */
	public function getDeviceInformation(
		string $address,
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|Entities\API\Gen2\GetDeviceInformation
	{
		$deferred = new Promise\Deferred();

		$request = $this->createRequest(
			RequestMethodInterface::METHOD_GET,
			sprintf(self::GET_DEVICE_INFORMATION_ENDPOINT, $address),
		);

		$result = $this->callRequest($request, null, null, null, $async);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred, $request): void {
					try {
						$deferred->resolve($this->parseGetDeviceInformation($request, $response));
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->otherwise(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $this->parseGetDeviceInformation($request, $result);
	}

	/**
	 * @return ($async is true ? Promise\ExtendedPromiseInterface|Promise\PromiseInterface : Entities\API\Gen2\GetDeviceConfiguration)
	 *
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\HttpApiCall
	 * @throws RuntimeException
	 */
	public function getDeviceConfiguration(
		string $address,
		string|null $username,
		string|null $password,
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|Entities\API\Gen2\GetDeviceConfiguration
	{
		$deferred = new Promise\Deferred();

		$request = $this->createRequest(
			RequestMethodInterface::METHOD_GET,
			sprintf(self::GET_DEVICE_CONFIGURATION_ENDPOINT, $address),
		);

		$result = $this->callRequest($request, self::AUTHORIZATION_DIGEST, $username, $password, $async);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred, $request): void {
					try {
						$deferred->resolve($this->parseGetDeviceConfiguration($request, $response));
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->otherwise(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $this->parseGetDeviceConfiguration($request, $result);
	}

	/**
	 * @return ($async is true ? Promise\ExtendedPromiseInterface|Promise\PromiseInterface : Entities\API\Gen2\GetDeviceState)
	 *
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\HttpApiCall
	 * @throws RuntimeException
	 */
	public function getDeviceState(
		string $address,
		string|null $username,
		string|null $password,
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|Entities\API\Gen2\GetDeviceState
	{
		$deferred = new Promise\Deferred();

		$request = $this->createRequest(
			RequestMethodInterface::METHOD_GET,
			sprintf(self::GET_DEVICE_STATE_ENDPOINT, $address),
		);

		$result = $this->callRequest($request, self::AUTHORIZATION_DIGEST, $username, $password, $async);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred, $request): void {
					try {
						$deferred->resolve($this->parseGetDeviceState($request, $response));
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->otherwise(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $this->parseGetDeviceState($request, $result);
	}

	/**
	 * @return ($async is true ? Promise\ExtendedPromiseInterface|Promise\PromiseInterface : bool)
	 *
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\HttpApiCall
	 * @throws Exceptions\HttpApiError
	 */
	public function setDeviceState(
		string $address,
		string|null $username,
		string|null $password,
		string $component,
		int|float|string|bool $value,
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|bool
	{
		if (
			preg_match(self::PROPERTY_COMPONENT, $component, $propertyMatches) !== 1
			|| !array_key_exists('component', $propertyMatches)
			|| !array_key_exists('identifier', $propertyMatches)
			|| !array_key_exists('attribute', $propertyMatches)
		) {
			if ($async) {
				return Promise\reject(new Exceptions\InvalidState('Property identifier is not in expected format'));
			}

			throw new Exceptions\HttpApiCall('Property identifier is not in expected format');
		}

		try {
			$componentMethod = $this->buildComponentMethod($component);

		} catch (Exceptions\InvalidState) {
			if ($async) {
				return Promise\reject(new Exceptions\InvalidState('Component action could not be created'));
			}

			throw new Exceptions\HttpApiCall('Component action could not be created');
		}

		try {
			$body = Utils\Json::encode([
				'id' => uniqid(),
				'method' => $componentMethod,
				'params' => [
					'id' => intval($propertyMatches['identifier']),
					$propertyMatches['attribute'] => $value,
				],
			]);
		} catch (Utils\JsonException $ex) {
			if ($async) {
				return Promise\reject(new Exceptions\InvalidState(
					'Message body could not be encoded',
					$ex->getCode(),
					$ex,
				));
			}

			throw new Exceptions\InvalidState(
				'Message body could not be encoded',
				$ex->getCode(),
				$ex,
			);
		}

		$deferred = new Promise\Deferred();

		$request = $this->createRequest(
			RequestMethodInterface::METHOD_POST,
			sprintf(self::SET_DEVICE_STATE_ENDPOINT, $address),
			[],
			[],
			$body,
		);

		$result = $this->callRequest($request, self::AUTHORIZATION_DIGEST, $username, $password, $async);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(static function (Message\ResponseInterface $response) use ($deferred): void {
					$deferred->resolve($response->getStatusCode() === StatusCodeInterface::STATUS_OK);
				})
				->otherwise(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $result->getStatusCode() === StatusCodeInterface::STATUS_OK;
	}

	/**
	 * @throws Exceptions\HttpApiCall
	 * @throws Exceptions\HttpApiError
	 */
	private function parseGetDeviceInformation(
		Message\RequestInterface $request,
		Message\ResponseInterface $response,
	): Entities\API\Gen2\GetDeviceInformation
	{
		$body = $this->validateResponseBody(
			$request,
			$response,
			self::GET_DEVICE_INFORMATION_MESSAGE_SCHEMA_FILENAME,
		);

		return $this->createEntity(Entities\API\Gen2\GetDeviceInformation::class, $body);
	}

	/**
	 * @throws Exceptions\HttpApiCall
	 * @throws Exceptions\HttpApiError
	 */
	private function parseGetDeviceConfiguration(
		Message\RequestInterface $request,
		Message\ResponseInterface $response,
	): Entities\API\Gen2\GetDeviceConfiguration
	{
		$body = $this->validateResponseBody(
			$request,
			$response,
			self::GET_DEVICE_CONFIG_MESSAGE_SCHEMA_FILENAME,
		);

		$switches = $covers = $lights = $inputs = $temperature = $humidity = [];

		foreach ($body as $key => $configuration) {
			if (
				$configuration instanceof Utils\ArrayHash
				&& preg_match(self::COMPONENT_KEY, $key, $componentMatches) === 1
				&& array_key_exists('component', $componentMatches)
				&& Types\ComponentType::isValidValue($componentMatches['component'])
			) {
				if ($componentMatches['component'] === Types\ComponentType::SWITCH) {
					$switches[] = (array) $configuration;
				} elseif ($componentMatches['component'] === Types\ComponentType::COVER) {
					$covers[] = array_merge(
						(array) $configuration,
						[
							'motor' => (array) $configuration->offsetGet('motor'),
							'obstruction_detection' => (array) $configuration->offsetGet('obstruction_detection'),
							'safety_switch' => (array) $configuration->offsetGet('safety_switch'),
						],
					);
				} elseif ($componentMatches['component'] === Types\ComponentType::LIGHT) {
					$lights[] = array_merge(
						(array) $configuration,
						[
							'default' => (array) $configuration->offsetGet('default'),
							'night_mode' => (array) $configuration->offsetGet('night_mode'),
							'safety_switch' => (array) $configuration->offsetGet('safety_switch'),
						],
					);
				} elseif ($componentMatches['component'] === Types\ComponentType::INPUT) {
					$inputs[] = (array) $configuration;
				} elseif ($componentMatches['component'] === Types\ComponentType::TEMPERATURE) {
					$temperature[] = (array) $configuration;
				} elseif ($componentMatches['component'] === Types\ComponentType::HUMIDITY) {
					$humidity[] = (array) $configuration;
				}
			}
		}

		return $this->createEntity(Entities\API\Gen2\GetDeviceConfiguration::class, Utils\ArrayHash::from([
			'switches' => $switches,
			'covers' => $covers,
			'inputs' => $inputs,
			'lights' => $lights,
			'temperature' => $temperature,
			'humidity' => $humidity,
		]));
	}

	/**
	 * @throws Exceptions\HttpApiCall
	 * @throws Exceptions\HttpApiError
	 */
	private function parseGetDeviceState(
		Message\RequestInterface $request,
		Message\ResponseInterface $response,
	): Entities\API\Gen2\GetDeviceState
	{
		$body = $this->validateResponseBody(
			$request,
			$response,
			self::GET_DEVICE_STATE_MESSAGE_SCHEMA_FILENAME,
		);

		$switches = $covers = $lights = $inputs = $temperature = $humidity = [];
		$ethernet = $wifi = null;

		foreach ($body as $key => $state) {
			if (
				$state instanceof Utils\ArrayHash
				&& preg_match(self::COMPONENT_KEY, $key, $componentMatches) === 1
				&& array_key_exists('component', $componentMatches)
				&& Types\ComponentType::isValidValue($componentMatches['component'])
			) {
				if ($componentMatches['component'] === Types\ComponentType::SWITCH) {
					$switches[] = array_merge(
						(array) $state,
						[
							'aenergy' => (array) $state->offsetGet('aenergy'),
							'temperature' => (array) $state->offsetGet('temperature'),
							'errors' => (array) $state->offsetGet('errors'),
						],
					);
				} elseif ($componentMatches['component'] === Types\ComponentType::COVER) {
					$covers[] = array_merge(
						(array) $state,
						[
							'aenergy' => (array) $state->offsetGet('aenergy'),
							'temperature' => (array) $state->offsetGet('temperature'),
							'errors' => (array) $state->offsetGet('errors'),
						],
					);
				} elseif ($componentMatches['component'] === Types\ComponentType::LIGHT) {
					$lights[] = (array) $state;
				} elseif ($componentMatches['component'] === Types\ComponentType::INPUT) {
					$inputs[] = array_merge(
						(array) $state,
						[
							'errors' => (array) $state->offsetGet('errors'),
						],
					);
				} elseif ($componentMatches['component'] === Types\ComponentType::TEMPERATURE) {
					$temperature[] = array_merge(
						(array) $state,
						[
							'errors' => (array) $state->offsetGet('errors'),
						],
					);
				} elseif ($componentMatches['component'] === Types\ComponentType::HUMIDITY) {
					$humidity[] = array_merge(
						(array) $state,
						[
							'errors' => (array) $state->offsetGet('errors'),
						],
					);
				} elseif ($componentMatches['component'] === Types\ComponentType::ETHERNET) {
					$ethernet = (array) $state;
				} elseif ($componentMatches['component'] === Types\ComponentType::WIFI) {
					$wifi = (array) $state;
				}
			}
		}

		return $this->createEntity(Entities\API\Gen2\GetDeviceState::class, Utils\ArrayHash::from([
			'switches' => $switches,
			'covers' => $covers,
			'inputs' => $inputs,
			'lights' => $lights,
			'temperature' => $temperature,
			'humidity' => $humidity,
			'ethernet' => $ethernet,
			'wifi' => $wifi,
		]));
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	private function buildComponentMethod(string $component): string
	{
		if (
			preg_match(self::PROPERTY_COMPONENT, $component, $componentMatches) !== 1
			|| !array_key_exists('component', $componentMatches)
			|| !array_key_exists('identifier', $componentMatches)
			|| !array_key_exists('attribute', $componentMatches)
		) {
			throw new Exceptions\InvalidState('Property identifier is not in expected format');
		}

		if (
			$componentMatches['component'] === Types\ComponentType::SWITCH
			&& $componentMatches['attribute'] === Types\ComponentAttributeType::ON
		) {
			return self::SWITCH_SET_METHOD;
		}

		if (
			$componentMatches['component'] === Types\ComponentType::COVER
			&& $componentMatches['attribute'] === Types\ComponentAttributeType::POSITION
		) {
			return self::COVER_GO_TO_POSITION_METHOD;
		}

		if (
			$componentMatches['component'] === Types\ComponentType::LIGHT
			&& (
				$componentMatches['description'] === Types\ComponentAttributeType::ON
				|| $componentMatches['attribute'] === Types\ComponentAttributeType::BRIGHTNESS
			)
		) {
			return self::LIGHT_SET_METHOD;
		}

		throw new Exceptions\InvalidState('Property method could not be build');
	}

}
