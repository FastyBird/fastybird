<?php declare(strict_types = 1);

/**
 * ConsoleLogger.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Bootstrap!
 * @subpackage     Subscribers
 * @since          0.1.0
 *
 * @date           06.10.22
 */

namespace FastyBird\Library\Bootstrap\Subscribers;

use Monolog;
use Monolog\Logger;
use Psr\Log\LogLevel;
use Symfony\Bridge\Monolog as SymfonyMonolog;
use Symfony\Component\Console as SymfonyConsole;
use Symfony\Component\EventDispatcher;

/**
 * Console subscriber
 *
 * @phpstan-import-type Level from Logger
 * @phpstan-import-type LevelName from Logger
 *
 * @package         FastyBird:Bootstrap!
 * @subpackage      Subscribers
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Console implements EventDispatcher\EventSubscriberInterface
{

	/**
	 * @param array<Monolog\Handler\AbstractProcessingHandler> $loggerHandlers
	 * @param Level|LevelName|LogLevel::* $level
	 */
	public function __construct(
		private readonly array $loggerHandlers,
		private readonly int|string $level,
	)
	{
	}

	public static function getSubscribedEvents(): array
	{
		return [
			SymfonyConsole\ConsoleEvents::COMMAND => 'command',
		];
	}

	public function command(): void
	{
		foreach ($this->loggerHandlers as $handler) {
			if (!$handler instanceof SymfonyMonolog\Handler\ConsoleHandler) {
				if ($handler->getLevel() < $this->level) {
					$handler->setLevel($this->level);
				}
			}
		}
	}

}
