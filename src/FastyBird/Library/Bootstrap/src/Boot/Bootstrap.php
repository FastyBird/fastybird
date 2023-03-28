<?php declare(strict_types = 1);

/**
 * Bootstrap.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Bootstrap!
 * @subpackage     Boot
 * @since          0.1.0
 *
 * @date           08.03.20
 */

namespace FastyBird\Library\Bootstrap\Boot;

use FastyBird\Library\Bootstrap\Exceptions;
use Tester;
use function array_key_exists;
use function array_merge;
use function array_shift;
use function boolval;
use function class_exists;
use function count;
use function define;
use function defined;
use function explode;
use function file_exists;
use function getenv;
use function implode;
use function in_array;
use function is_array;
use function is_dir;
use function is_numeric;
use function mkdir;
use function realpath;
use function sprintf;
use function strlen;
use function strpos;
use function strtolower;
use function strval;
use function substr;
use const DIRECTORY_SEPARATOR as DS;

/**
 * Service bootstrap configurator
 *
 * @package        FastyBird:Bootstrap!
 * @subpackage     Boot
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Bootstrap
{

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public static function boot(string $envPrefix = 'FB_APP_PARAMETER_'): Configurator
	{
		self::initConstants();

		// Create app configurator
		$config = new Configurator();

		$config->setTimeZone('UTC');

		// Define variables
		$config->addStaticParameters([
			'tempDir' => FB_TEMP_DIR,
			'logsDir' => FB_LOGS_DIR,
			'appDir' => FB_APP_DIR,
			'wwwDir' => FB_PUBLIC_DIR,

			'debugMode' => isset($_ENV['APP_ENV']) && strtolower(strval($_ENV['APP_ENV'])) === 'dev',
		]);

		// Load parameters from environment
		$config->addStaticParameters(self::loadEnvParameters($envPrefix));

		if (!class_exists('\Tester\Environment') || getenv(Tester\Environment::VariableRunner) === false) {
			$config->enableTracy(FB_LOGS_DIR);
		}

		// Default extension config
		$config->addConfig(__DIR__ . DS . '..' . DS . '..' . DS . 'config' . DS . 'common.neon');
		$config->addConfig(__DIR__ . DS . '..' . DS . '..' . DS . 'config' . DS . 'defaults.neon');

		if (file_exists(FB_CONFIG_DIR . DS . 'common.neon')) {
			$config->addConfig(FB_CONFIG_DIR . DS . 'common.neon');
		}

		if (file_exists(FB_CONFIG_DIR . DS . 'defaults.neon')) {
			$config->addConfig(FB_CONFIG_DIR . DS . 'defaults.neon');
		}

		if (file_exists(FB_CONFIG_DIR . DS . 'local.neon')) {
			$config->addConfig(FB_CONFIG_DIR . DS . 'local.neon');
		}

		return $config;
	}

	private static function initConstants(): void
	{
		// Configuring APP dir path
		if (isset($_ENV['FB_APP_DIR']) && !defined('FB_APP_DIR')) {
			define('FB_APP_DIR', $_ENV['FB_APP_DIR']);

		} elseif (getenv('FB_APP_DIR') !== false && !defined('FB_APP_DIR')) {
			define('FB_APP_DIR', getenv('FB_APP_DIR'));

		} elseif (!defined('FB_APP_DIR')) {
			$path = __DIR__;

			for ($i = 0;$i < 10;$i++) {
				$path .= DS . '..';

				$vendorPath = realpath($path . DS . 'vendor');

				if ($vendorPath !== false) {
					define('FB_APP_DIR', realpath($path));

					break;
				}
			}
		}

		// Configuring resources dir path
		if (isset($_ENV['FB_PUBLIC_DIR']) && !defined('FB_PUBLIC_DIR')) {
			define('FB_PUBLIC_DIR', $_ENV['FB_PUBLIC_DIR']);

		} elseif (getenv('FB_PUBLIC_DIR') !== false && !defined('FB_PUBLIC_DIR')) {
			define('FB_PUBLIC_DIR', getenv('FB_PUBLIC_DIR'));

		} elseif (!defined('FB_PUBLIC_DIR')) {
			define('FB_PUBLIC_DIR', FB_APP_DIR . DS . 'www');
		}

		// Configuring resources dir path
		if (isset($_ENV['FB_RESOURCES_DIR']) && !defined('FB_RESOURCES_DIR')) {
			define('FB_RESOURCES_DIR', $_ENV['FB_RESOURCES_DIR']);

		} elseif (getenv('FB_RESOURCES_DIR') !== false && !defined('FB_RESOURCES_DIR')) {
			define('FB_RESOURCES_DIR', getenv('FB_RESOURCES_DIR'));

		} elseif (!defined('FB_RESOURCES_DIR')) {
			define('FB_RESOURCES_DIR', FB_APP_DIR . DS . 'resources');
		}

		// Configuring temporary dir path
		if (isset($_ENV['FB_TEMP_DIR']) && !defined('FB_TEMP_DIR')) {
			define('FB_TEMP_DIR', $_ENV['FB_TEMP_DIR']);

		} elseif (getenv('FB_TEMP_DIR') !== false && !defined('FB_TEMP_DIR')) {
			define('FB_TEMP_DIR', getenv('FB_TEMP_DIR'));

		} elseif (!defined('FB_TEMP_DIR')) {
			define('FB_TEMP_DIR', FB_APP_DIR . DS . 'var' . DS . 'temp');
		}

		// Check for temporary dir
		if (!is_dir(strval(FB_TEMP_DIR))) {
			mkdir(strval(FB_TEMP_DIR), 0777, true);
		}

		// Configuring logs dir path
		if (isset($_ENV['FB_LOGS_DIR']) && !defined('FB_LOGS_DIR')) {
			define('FB_LOGS_DIR', $_ENV['FB_LOGS_DIR']);

		} elseif (getenv('FB_LOGS_DIR') !== false && !defined('FB_LOGS_DIR')) {
			define('FB_LOGS_DIR', getenv('FB_LOGS_DIR'));

		} elseif (!defined('FB_LOGS_DIR')) {
			define('FB_LOGS_DIR', FB_APP_DIR . DS . 'var' . DS . 'logs');
		}

		// Check for logs dir
		if (!is_dir(strval(FB_LOGS_DIR))) {
			mkdir(strval(FB_LOGS_DIR), 0777, true);
		}

		// Configuring configuration dir path
		if (isset($_ENV['FB_CONFIG_DIR']) && !defined('FB_CONFIG_DIR')) {
			define('FB_CONFIG_DIR', realpath(strval($_ENV['FB_CONFIG_DIR'])));

		} elseif (getenv('FB_CONFIG_DIR') !== false && !defined('FB_CONFIG_DIR')) {
			define('FB_CONFIG_DIR', realpath(getenv('FB_CONFIG_DIR')));

		} elseif (!defined('FB_CONFIG_DIR')) {
			if (realpath(FB_APP_DIR . DS . 'config') !== false) {
				define('FB_CONFIG_DIR', realpath(FB_APP_DIR . DS . 'config'));
			} else {
				define('FB_CONFIG_DIR', realpath(FB_APP_DIR . DS . 'var' . DS . 'config'));
			}
		}
	}

	/**
	 * @return array<mixed>
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	private static function loadEnvParameters(
		string $prefix,
		string $delimiter = '_',
	): array
	{
		if ($delimiter === '') {
			throw new Exceptions\InvalidArgument('Delimiter must be non-empty string');
		}

		$prefix .= $delimiter;

		$map = static function (&$array, array $keys, $value) use (&$map) {
			if (count($keys) <= 0) {
				return is_numeric($value) ? (int) $value : (in_array(
					strtolower(strval($value)),
					['true', 'false'],
					true,
				) ? boolval(
					strtolower(strval($value)),
				) : $value);
			}

			$key = array_shift($keys);

			if (!is_array($array)) {
				throw new Exceptions\InvalidState(
					sprintf('Invalid structure for key "%s" value "%s"', implode($keys), $value),
				);
			}

			if (!array_key_exists($key, $array)) {
				$array[$key] = [];
			}

			// Recursive
			$array[$key] = $map($array[$key], $keys, $value);

			return $array;
		};

		$parameters = [];

		foreach (array_merge($_ENV, getenv()) as $key => $value) {
			if (strpos($key, $prefix) === 0) {
				// Parse PREFIX{delimiter=_}{NAME-1}{delimiter=_}{NAME-N}
				$keys = explode($delimiter, strtolower(substr($key, strlen($prefix))));

				// Make array structure
				$map($parameters, $keys, $value);
			}
		}

		return $parameters;
	}

}
