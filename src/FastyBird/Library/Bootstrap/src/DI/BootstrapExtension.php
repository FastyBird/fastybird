<?php declare(strict_types = 1);

/**
 * BootstrapExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Bootstrap!
 * @subpackage     DI
 * @since          0.1.0
 *
 * @date           08.03.20
 */

namespace FastyBird\Library\Bootstrap\DI;

use FastyBird\Library\Bootstrap\Helpers;
use FastyBird\Library\Bootstrap\Subscribers;
use Monolog;
use Nette;
use Nette\DI;
use Nette\Schema;
use Sentry;
use stdClass;
use Symfony\Bridge\Monolog as SymfonyMonolog;
use function assert;
use function class_exists;
use function getenv;
use function is_string;
use const DIRECTORY_SEPARATOR;

/**
 * App bootstrap extension container
 *
 * @package        FastyBird:Bootstrap!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class BootstrapExtension extends DI\CompilerExtension
{

	public const NAME = 'fbBootstrapLibrary';

	public static function register(
		Nette\Configurator $config,
		string $extensionName = self::NAME,
	): void
	{
		$config->onCompile[] = static function (
			Nette\Configurator $config,
			DI\Compiler $compiler,
		) use ($extensionName): void {
			$compiler->addExtension($extensionName, new BootstrapExtension());
		};
	}

	public function getConfigSchema(): Schema\Schema
	{
		return Schema\Expect::structure([
			'logging' => Schema\Expect::structure(
				[
					'level' => Schema\Expect::int(Monolog\Logger::ERROR),
					'rotatingFile' => Schema\Expect::string(null)->nullable(),
					'stdOut' => Schema\Expect::bool(false),
					'console' => Schema\Expect::structure(
						[
							'enabled' => Schema\Expect::bool(false),
							'level' => Schema\Expect::int(Monolog\Logger::INFO),
						],
					),
				],
			),
			'sentry' => Schema\Expect::structure(
				[
					'dsn' => Schema\Expect::string(null)->nullable(),
				],
			),
		]);
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();
		assert($configuration instanceof stdClass);

		// Logger handlers
		if ($configuration->logging->rotatingFile !== null) {
			$builder->addDefinition(
				$this->prefix('logger.handler.rotatingFile'),
				new DI\Definitions\ServiceDefinition(),
			)
				->setType(Monolog\Handler\RotatingFileHandler::class)
				->setArguments([
					'filename' => FB_LOGS_DIR . DIRECTORY_SEPARATOR . $configuration->logging->rotatingFile,
					'maxFiles' => 10,
					'level' => $configuration->logging->level,
				]);
		}

		if ($configuration->logging->stdOut) {
			$builder->addDefinition($this->prefix('logger.handler.stdOut'), new DI\Definitions\ServiceDefinition())
				->setType(Monolog\Handler\StreamHandler::class)
				->setArguments([
					'stream' => 'php://stdout',
					'level' => $configuration->logging->level,
				]);
		}

		if ($configuration->logging->console->enabled) {
			$builder->addDefinition($this->prefix('logger.handler.console'), new DI\Definitions\ServiceDefinition())
				->setType(SymfonyMonolog\Handler\ConsoleHandler::class);

			$builder->addDefinition($this->prefix('subscribers.console'), new DI\Definitions\ServiceDefinition())
				->setType(Subscribers\Console::class)
				->setArguments([
					'level' => $configuration->logging->console->level,
				]);
		}

		if (class_exists('\Doctrine\DBAL\Connection') && class_exists('\Doctrine\ORM\EntityManager')) {
			$builder->addDefinition($this->prefix('helpers.database'), new DI\Definitions\ServiceDefinition())
				->setType(Helpers\Database::class);
		}

		if (
			isset($_ENV['FB_APP_PARAMETER__SENTRY_DSN'])
			&& is_string($_ENV['FB_APP_PARAMETER__SENTRY_DSN'])
			&& $_ENV['FB_APP_PARAMETER__SENTRY_DSN'] !== ''
		) {
			$sentryDSN = $_ENV['FB_APP_PARAMETER__SENTRY_DSN'];

		} elseif (
			getenv('FB_APP_PARAMETER__SENTRY_DSN') !== false
			&& is_string(getenv('FB_APP_PARAMETER__SENTRY_DSN'))
			&& getenv('FB_APP_PARAMETER__SENTRY_DSN') !== ''
		) {
			$sentryDSN = getenv('FB_APP_PARAMETER__SENTRY_DSN');

		} elseif ($configuration->sentry->dsn !== null) {
			$sentryDSN = $configuration->sentry->dsn;

		} else {
			$sentryDSN = null;
		}

		// Sentry issues logger
		if (is_string($sentryDSN) && $sentryDSN !== '') {
			$builder->addDefinition($this->prefix('sentry.handler'), new DI\Definitions\ServiceDefinition())
				->setType(Sentry\Monolog\Handler::class)
				->setArgument('level', $configuration->logging->level);

			$sentryClientBuilderService = $builder->addDefinition(
				$this->prefix('sentry.clientBuilder'),
				new DI\Definitions\ServiceDefinition(),
			)
				->setFactory('Sentry\ClientBuilder::create')
				->setArguments([['dsn' => $sentryDSN]]);

			$builder->addDefinition($this->prefix('sentry.client'), new DI\Definitions\ServiceDefinition())
				->setType(Sentry\ClientInterface::class)
				->setFactory([$sentryClientBuilderService, 'getClient']);

			$builder->addDefinition($this->prefix('sentry.hub'), new DI\Definitions\ServiceDefinition())
				->setType(Sentry\State\Hub::class);
		}
	}

	/**
	 * @throws Nette\DI\MissingServiceException
	 */
	public function beforeCompile(): void
	{
		parent::beforeCompile();

		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();
		assert($configuration instanceof stdClass);

		$sentryHandlerServiceName = $builder->getByType(Sentry\Monolog\Handler::class);

		if (
			$configuration->logging->rotatingFile !== null
			|| $configuration->logging->stdOut
			|| $sentryHandlerServiceName !== null
		) {
			$monologLoggerServiceName = $builder->getByType(Monolog\Logger::class);
			assert(is_string($monologLoggerServiceName));

			$monologLoggerService = $builder->getDefinition($monologLoggerServiceName);
			assert($monologLoggerService instanceof DI\Definitions\ServiceDefinition);

			if ($configuration->logging->rotatingFile) {
				$rotatingFileHandler = $builder->getDefinition($this->prefix('logger.handler.rotatingFile'));

				$monologLoggerService->addSetup('?->pushHandler(?)', ['@self', $rotatingFileHandler]);
			}

			if ($configuration->logging->stdOut) {
				$stdOutHandler = $builder->getDefinition($this->prefix('logger.handler.stdOut'));

				$monologLoggerService->addSetup('?->pushHandler(?)', ['@self', $stdOutHandler]);
			}

			if ($configuration->logging->console->enabled) {
				$consoleHandler = $builder->getDefinition($this->prefix('logger.handler.console'));

				$monologLoggerService->addSetup('?->pushHandler(?)', ['@self', $consoleHandler]);
			}

			if ($sentryHandlerServiceName !== null) {
				$sentryHandlerService = $builder->getDefinition($this->prefix('sentry.handler'));
				assert($sentryHandlerService instanceof DI\Definitions\ServiceDefinition);

				$monologLoggerService->addSetup('?->pushHandler(?)', ['@self', $sentryHandlerService]);
			}
		}
	}

}
