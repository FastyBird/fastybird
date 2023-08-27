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
	public function getDeviceStatus(
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
	public function setDeviceStatus(
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
					$switches[] = [
						'id' => $configuration->offsetGet('id'),
						'name' => $configuration->offsetGet('name'),
						'mode' => $configuration->offsetGet('inMode'),
						'initial_state' => $configuration->offsetGet('initialState'),
						'auto_on' => $configuration->offsetGet('autoOn'),
						'auto_on_delay' => $configuration->offsetGet('autoOnDelay'),
						'auto_off' => $configuration->offsetGet('autoOff'),
						'auto_off_delay' => $configuration->offsetGet('autoOffDelay'),
						'input_id' => $configuration->offsetGet('inputId'),
						'power_limit' => $configuration->offsetGet('powerLimit'),
						'voltage_limit' => $configuration->offsetGet('voltageLimit'),
						'current_limit' => $configuration->offsetGet('currentLimit'),
					];
				} elseif ($componentMatches['component'] === Types\ComponentType::COVER) {
					$covers[] = [
						'id' => $configuration->offsetGet('id'),
						'name' => $configuration->offsetGet('name'),
						'mode' => $configuration->offsetGet('inMode'),
						'initial_state' => $configuration->offsetGet('initialState'),
						'power_limit' => $configuration->offsetGet('powerLimit'),
						'voltage_limit' => $configuration->offsetGet('voltageLimit'),
						'current_limit' => $configuration->offsetGet('currentLimit'),
						'motor' => $configuration->offsetGet('motor') instanceof Utils\ArrayHash
							? [
								'idle_power_threshold' => $configuration->offsetGet('motor')->offsetGet('idlePowerThr'),
								'idle_confirm_period' => $configuration->offsetGet('motor')->offsetGet(
									'idleConfirmPeriod',
								),
							]
							: $configuration->offsetGet('motor'),
						'maximum_opening_time' => $configuration->offsetGet('maxtimeOpen'),
						'maximum_closing_time' => $configuration->offsetGet('maxtimeClose'),
						'swapped_input' => $configuration->offsetGet('swapInputs'),
						'inverted_directions' => $configuration->offsetGet('invertDirections'),
						'obstruction_detection' => $configuration->offsetGet(
							'obstructionDetection',
						) instanceof Utils\ArrayHash
							? [
								'enabled' => $configuration->offsetGet('obstructionDetection')->offsetGet('enable'),
								'direction' => $configuration->offsetGet('obstructionDetection')->offsetGet(
									'direction',
								),
								'action' => $configuration->offsetGet('obstructionDetection')->offsetGet('action'),
								'power_threshold' => $configuration->offsetGet('obstructionDetection')->offsetGet(
									'powerThr',
								),
								'holdoff' => $configuration->offsetGet('obstructionDetection')->offsetGet('holdoff'),
							]
							: $configuration->offsetGet('obstructionDetection'),
						'safety_switch' => $configuration->offsetGet('safetySwitch') instanceof Utils\ArrayHash
							? [
								'enabled' => $configuration->offsetGet('safetySwitch')->offsetGet('enable'),
								'direction' => $configuration->offsetGet('safetySwitch')->offsetGet('direction'),
								'action' => $configuration->offsetGet('safetySwitch')->offsetGet('action'),
								'allowed_move' => $configuration->offsetGet('safetySwitch')->offsetGet('allowedMove'),
							]
							: $configuration->offsetGet('safetySwitch'),
					];
				} elseif ($componentMatches['component'] === Types\ComponentType::LIGHT) {
					$lights[] = [
						'id' => $configuration->offsetGet('id'),
						'name' => $configuration->offsetGet('name'),
						'initial_state' => $configuration->offsetGet('initialState'),
						'auto_on' => $configuration->offsetGet('autoOn'),
						'auto_on_delay' => $configuration->offsetGet('autoOnDelay'),
						'auto_off' => $configuration->offsetGet('autoOff'),
						'auto_off_delay' => $configuration->offsetGet('autoOffDelay'),
						'default' => $configuration->offsetGet('default') instanceof Utils\ArrayHash
							? [
								'brightness' => $configuration->offsetGet('default')->offsetGet('brightness'),
							]
							: $configuration->offsetGet('default'),
						'night_mode' => $configuration->offsetGet('nightMode') instanceof Utils\ArrayHash
							? [
								'enabled' => $configuration->offsetGet('nightMode')->offsetGet('enable'),
								'brightness' => $configuration->offsetGet('nightMode')->offsetGet('brightness'),
								'active_between' => $configuration->offsetGet('nightMode')->offsetGet(
									'activeBetween',
								) instanceof Utils\ArrayHash
									? (array) $configuration->offsetGet('nightMode')->offsetGet('activeBetween')
									: $configuration->offsetGet('nightMode')->offsetGet('activeBetween'),
							]
							: $configuration->offsetGet('nightMode'),
					];
				} elseif ($componentMatches['component'] === Types\ComponentType::INPUT) {
					$inputs[] = [
						'id' => $configuration->offsetGet('id'),
						'name' => $configuration->offsetGet('name'),
						'input_type' => $configuration->offsetGet('type'),
						'inverted' => $configuration->offsetGet('invert'),
						'factory_reset' => $configuration->offsetGet('factoryReset'),
						'report_threshold' => $configuration->offsetGet('reportThr'),
					];
				} elseif ($componentMatches['component'] === Types\ComponentType::TEMPERATURE) {
					$temperature[] = [
						'id' => $configuration->offsetGet('id'),
						'name' => $configuration->offsetGet('name'),
						'report_threshold' => $configuration->offsetGet('reportThrC'),
						'offset' => $configuration->offsetGet('offsetC'),
					];
				} elseif ($componentMatches['component'] === Types\ComponentType::HUMIDITY) {
					$humidity[] = [
						'id' => $configuration->offsetGet('id'),
						'name' => $configuration->offsetGet('name'),
						'report_threshold' => $configuration->offsetGet('reportThr'),
						'offset' => $configuration->offsetGet('offset'),
					];
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
					$switches[] = [
						'id' => $state->offsetGet('id'),
						'source' => $state->offsetGet('source'),
						'output' => $state->offsetGet('output'),
						'timer_started_at' => $state->offsetGet('timerStartedAt'),
						'timer_duration' => $state->offsetGet('timerDuration'),
						'active_power' => $state->offsetGet('apower'),
						'voltage' => $state->offsetGet('voltage'),
						'current' => $state->offsetGet('current'),
						'power_factor' => $state->offsetGet('pf'),
						'active_energy' => $state->offsetGet('aenergy') instanceof Utils\ArrayHash
							? [
								'total' => $state->offsetGet('aenergy')->offsetGet('total'),
								'by_minute' => $state->offsetGet('aenergy')->offsetGet('byMinute'),
								'minute_ts' => $state->offsetGet('aenergy')->offsetGet('minuteTs'),
							]
							: $state->offsetGet('aenergy'),
						'temperature' => $state->offsetGet('temperature') instanceof Utils\ArrayHash
							? [
								'temperature_celsius' => $state->offsetGet('temperature')->offsetGet('tC'),
								'temperature_fahrenheit' => $state->offsetGet('temperature')->offsetGet('tF'),
							]
							: $state->offsetGet('temperature'),
						'errors' => $state->offsetGet('errors') instanceof Utils\ArrayHash
							? (array) $state->offsetGet('errors')
							: $state->offsetGet('errors'),
					];
				} elseif ($componentMatches['component'] === Types\ComponentType::COVER) {
					$covers[] = [
						'id' => $state->offsetGet('id'),
						'source' => $state->offsetGet('source'),
						'state' => $state->offsetGet('state'),
						'active_power' => $state->offsetGet('apower'),
						'voltage' => $state->offsetGet('voltage'),
						'current' => $state->offsetGet('current'),
						'power_factor' => $state->offsetGet('pf'),
						'current_position' => $state->offsetGet('currentPos'),
						'target_position' => $state->offsetGet('targetPos'),
						'move_timeout' => $state->offsetGet('moveTimeout'),
						'move_started_at' => $state->offsetGet('moveStartedAt'),
						'has_position_control' => $state->offsetGet('posControl'),
						'active_energy' => $state->offsetGet('aenergy') instanceof Utils\ArrayHash
							? [
								'total' => $state->offsetGet('aenergy')->offsetGet('total'),
								'by_minute' => $state->offsetGet('aenergy')->offsetGet('byMinute'),
								'minute_ts' => $state->offsetGet('aenergy')->offsetGet('minuteTs'),
							]
							: $state->offsetGet('aenergy'),
						'temperature' => $state->offsetGet('temperature') instanceof Utils\ArrayHash
							? [
								'temperature_celsius' => $state->offsetGet('temperature')->offsetGet('tC'),
								'temperature_fahrenheit' => $state->offsetGet('temperature')->offsetGet('tF'),
							]
							: $state->offsetGet('temperature'),
						'errors' => $state->offsetGet('errors') instanceof Utils\ArrayHash
							? (array) $state->offsetGet('errors')
							: $state->offsetGet('errors'),
					];
				} elseif ($componentMatches['component'] === Types\ComponentType::LIGHT) {
					$lights[] = [
						'id' => $state->offsetGet('id'),
						'source' => $state->offsetGet('source'),
						'output' => $state->offsetGet('output'),
						'brightness' => $state->offsetGet('brightness'),
						'timer_started_at' => $state->offsetGet('timerStartedAt'),
						'timer_duration' => $state->offsetGet('timerDuration'),
					];
				} elseif ($componentMatches['component'] === Types\ComponentType::INPUT) {
					$inputs[] = [
						'id' => $state->offsetGet('id'),
						'state' => $state->offsetGet('state'),
						'percent' => $state->offsetGet('percent'),
						'errors' => $state->offsetGet('errors') instanceof Utils\ArrayHash
							? (array) $state->offsetGet('errors')
							: $state->offsetGet('errors'),
					];
				} elseif ($componentMatches['component'] === Types\ComponentType::TEMPERATURE) {
					$temperature[] = [
						'id' => $state->offsetGet('id'),
						'temperature_celsius' => $state->offsetGet('tC'),
						'temperature_fahrenheit' => $state->offsetGet('tF'),
						'errors' => $state->offsetGet('errors') instanceof Utils\ArrayHash
							? (array) $state->offsetGet('errors')
							: $state->offsetGet('errors'),
					];
				} elseif ($componentMatches['component'] === Types\ComponentType::HUMIDITY) {
					$humidity[] = [
						'id' => $state->offsetGet('id'),
						'relative_humidity' => $state->offsetGet('rh'),
						'errors' => $state->offsetGet('errors') instanceof Utils\ArrayHash
							? (array) $state->offsetGet('errors')
							: $state->offsetGet('errors'),
					];
				} elseif ($componentMatches['component'] === Types\ComponentType::ETHERNET) {
					$ethernet = [
						'ip' => $state->offsetGet('ip'),
					];
				} elseif ($componentMatches['component'] === Types\ComponentType::WIFI) {
					$wifi = [
						'sta_ip' => $state->offsetGet('staIp'),
						'status' => $state->offsetGet('status'),
						'ssid' => $state->offsetGet('ssid'),
						'rssi' => $state->offsetGet('rssi'),
						'ap_client_count' => $state->offsetGet('apClientCount'),
					];
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
