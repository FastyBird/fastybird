<?php declare(strict_types = 1);

/**
 * ApplicationExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ApplicationLibrary!
 * @subpackage     DI
 * @since          1.0.0
 *
 * @date           08.03.20
 */

namespace FastyBird\Library\Application\DI;

use FastyBird\Library\Application\Boot;
use FastyBird\Library\Application\EventLoop;
use FastyBird\Library\Application\Helpers;
use FastyBird\Library\Application\Router;
use FastyBird\Library\Application\Subscribers;
use FastyBird\Library\Application\UI;
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
use function interface_exists;
use function is_string;
use const DIRECTORY_SEPARATOR;

/**
 * App application extension container
 *
 * @package        FastyBird:ApplicationLibrary!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ApplicationExtension extends DI\CompilerExtension
{

	public const NAME = 'fbApplicationLibrary';

	public static function register(
		Boot\Configurator $config,
		string $extensionName = self::NAME,
	): void
	{
		$config->onCompile[] = static function (
			Boot\Configurator $config,
			DI\Compiler $compiler,
		) use ($extensionName): void {
			$compiler->addExtension($extensionName, new self());
		};
	}

	public function getConfigSchema(): Schema\Schema
	{
		return Schema\Expect::structure([
			'logging' => Schema\Expect::structure(
				[
					'rotatingFile' => Schema\Expect::structure(
						[
							'enabled' => Schema\Expect::bool(true),
							'level' => Schema\Expect::int(Monolog\Level::Info),
							'filename' => Schema\Expect::string('app.log'),
						],
					),
					'stdOut' => Schema\Expect::structure(
						[
							'enabled' => Schema\Expect::bool(false),
							'level' => Schema\Expect::int(Monolog\Level::Info),
						],
					),
					'console' => Schema\Expect::structure(
						[
							'enabled' => Schema\Expect::bool(false),
							'level' => Schema\Expect::int(Monolog\Level::Info),
						],
					),
				],
			),
			'sentry' => Schema\Expect::structure(
				[
					'dsn' => Schema\Expect::string()->nullable(),
					'level' => Schema\Expect::int(Monolog\Level::Warning),
				],
			),
		]);
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();
		assert($configuration instanceof stdClass);

		/**
		 * LOGGERS
		 */

		if ($configuration->logging->rotatingFile->enabled === true) {
			$builder->addDefinition(
				$this->prefix('logger.handler.rotatingFile'),
				new DI\Definitions\ServiceDefinition(),
			)
				->setType(Monolog\Handler\RotatingFileHandler::class)
				->setArguments([
					'filename' => FB_LOGS_DIR . DIRECTORY_SEPARATOR . $configuration->logging->rotatingFile->filename,
					'maxFiles' => 10,
					'level' => $configuration->logging->rotatingFile->level,
				]);
		}

		if ($configuration->logging->stdOut->enabled === true) {
			$builder->addDefinition($this->prefix('logger.handler.stdOut'), new DI\Definitions\ServiceDefinition())
				->setType(Monolog\Handler\StreamHandler::class)
				->setArguments([
					'stream' => 'php://stdout',
					'level' => $configuration->logging->stdOut->level,
				]);
		}

		$consoleHandler = null;

		if ($configuration->logging->console->enabled) {
			$consoleHandler = $builder->addDefinition(
				$this->prefix('logger.handler.console'),
				new DI\Definitions\ServiceDefinition(),
			)
				->setType(SymfonyMonolog\Handler\ConsoleHandler::class);
		}

		/**
		 * HELPERS
		 */

		$builder->addDefinition($this->prefix('helpers.eventLoop'), new DI\Definitions\ServiceDefinition())
			->setType(EventLoop\Wrapper::class);

		if (class_exists('\Doctrine\DBAL\Connection') && class_exists('\Doctrine\ORM\EntityManager')) {
			$builder->addDefinition($this->prefix('helpers.database'), new DI\Definitions\ServiceDefinition())
				->setType(Helpers\Database::class);
		}

		/**
		 * SUBSCRIBERS
		 */

		if ($configuration->logging->console->enabled) {
			$builder->addDefinition(
				$this->prefix('subscribers.console'),
				new DI\Definitions\ServiceDefinition(),
			)
				->setType(Subscribers\Console::class)
				->setArguments([
					'handler' => $consoleHandler,
					'level' => $configuration->logging->console->level,
				]);
		}

		if (class_exists('\Doctrine\DBAL\Connection') && class_exists('\Doctrine\ORM\EntityManager')) {
			$builder->addDefinition(
				$this->prefix('subscribers.entityDiscriminator'),
				new DI\Definitions\ServiceDefinition(),
			)
				->setType(Subscribers\EntityDiscriminator::class);
		}

		/**
		 * SENTRY ISSUES LOGGER
		 */

		if (interface_exists('\Sentry\ClientInterface')) {
			$builder->addDefinition(
				$this->prefix('helpers.sentry'),
				new DI\Definitions\ServiceDefinition(),
			)
				->setType(Helpers\Sentry::class);
		}

		if (
			isset($_ENV['FB_APP_PARAMETER__SENTRY_DSN'])
			&& is_string($_ENV['FB_APP_PARAMETER__SENTRY_DSN'])
			&& $_ENV['FB_APP_PARAMETER__SENTRY_DSN'] !== ''
		) {
			$sentryDSN = $_ENV['FB_APP_PARAMETER__SENTRY_DSN'];

		} elseif (
			getenv('FB_APP_PARAMETER__SENTRY_DSN') !== false
			&& getenv('FB_APP_PARAMETER__SENTRY_DSN') !== ''
		) {
			$sentryDSN = getenv('FB_APP_PARAMETER__SENTRY_DSN');

		} elseif ($configuration->sentry->dsn !== null) {
			$sentryDSN = $configuration->sentry->dsn;

		} else {
			$sentryDSN = null;
		}

		if (is_string($sentryDSN) && $sentryDSN !== '') {
			$builder->addDefinition($this->prefix('sentry.handler'), new DI\Definitions\ServiceDefinition())
				->setType(Sentry\Monolog\Handler::class)
				->setArgument('level', $configuration->logging->sentry->level);

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

		/**
		 * UI
		 */

		$builder->addDefinition($this->prefix('ui.templateFactory'), new DI\Definitions\ServiceDefinition())
			->setType(UI\TemplateFactory::class);

		$builder->addDefinition($this->prefix('ui.routes'), new DI\Definitions\ServiceDefinition())
			->setType(Router\AppRouter::class);
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

		/**
		 * LOGGERS
		 */

		if (
			$configuration->logging->rotatingFile->enabled === true
			|| $configuration->logging->stdOut->enabled === true
		) {
			$monologLoggerServiceName = $builder->getByType(Monolog\Logger::class);
			assert(is_string($monologLoggerServiceName));

			$monologLoggerService = $builder->getDefinition($monologLoggerServiceName);
			assert($monologLoggerService instanceof DI\Definitions\ServiceDefinition);

			if ($configuration->logging->rotatingFile->enabled === true) {
				$rotatingFileHandler = $builder->getDefinition($this->prefix('logger.handler.rotatingFile'));

				$monologLoggerService->addSetup('?->pushHandler(?)', ['@self', $rotatingFileHandler]);
			}

			if ($configuration->logging->stdOut->enabled === true) {
				$stdOutHandler = $builder->getDefinition($this->prefix('logger.handler.stdOut'));

				$monologLoggerService->addSetup('?->pushHandler(?)', ['@self', $stdOutHandler]);
			}
		}

		/**
		 * SENTRY
		 */

		$sentryHandlerServiceName = $builder->getByType(Sentry\Monolog\Handler::class);

		if ($sentryHandlerServiceName !== null) {
			$monologLoggerServiceName = $builder->getByType(Monolog\Logger::class);
			assert(is_string($monologLoggerServiceName));

			$monologLoggerService = $builder->getDefinition($monologLoggerServiceName);
			assert($monologLoggerService instanceof DI\Definitions\ServiceDefinition);

			$sentryHandlerService = $builder->getDefinition($this->prefix('sentry.handler'));
			assert($sentryHandlerService instanceof DI\Definitions\ServiceDefinition);

			$monologLoggerService->addSetup('?->pushHandler(?)', ['@self', $sentryHandlerService]);
		}

		/**
		 * DOCTRINE
		 */

		if (
			class_exists('\Doctrine\DBAL\Connection')
			&& class_exists('\Doctrine\ORM\EntityManager')
			&& $builder->getByType('\Doctrine\ORM\EntityManagerInterface') !== null
		) {
			$emService = $builder->getDefinitionByType('\Doctrine\ORM\EntityManagerInterface');
			assert($emService instanceof DI\Definitions\ServiceDefinition);

			$emService
				->addSetup('?->getEventManager()->addEventSubscriber(?)', [
					'@self',
					$builder->getDefinitionByType(Subscribers\EntityDiscriminator::class),
				]);
		}
	}

}
