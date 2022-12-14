<?php declare(strict_types = 1);

/**
 * Server.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WebServerPlugin!
 * @subpackage     Subscribers
 * @since          0.1.0
 *
 * @date           15.04.20
 */

namespace FastyBird\Plugin\WebServer\Subscribers;

use Doctrine\DBAL;
use FastyBird\Library\Bootstrap\Exceptions as BootstrapExceptions;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Plugin\WebServer\Events;
use FastyBird\Plugin\WebServer\Exceptions;
use Symfony\Component\EventDispatcher;
use function gc_collect_cycles;

/**
 * Database check subscriber
 *
 * @package         FastyBird:WebServerPlugin!
 * @subpackage      Subscribers
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Server implements EventDispatcher\EventSubscriberInterface
{

	public function __construct(private readonly BootstrapHelpers\Database $database)
	{
	}

	public static function getSubscribedEvents(): array
	{
		return [
			Events\Startup::class => 'check',
			Events\Request::class => 'request',
			Events\Response::class => 'response',
		];
	}

	/**
	 * @throws BootstrapExceptions\InvalidState
	 * @throws DBAL\Exception
	 * @throws Exceptions\InvalidState
	 */
	public function check(): void
	{
		// Check if ping to DB is possible...
		if (!$this->database->ping()) {
			// ...if not, try to reconnect
			$this->database->reconnect();

			// ...and ping again
			if (!$this->database->ping()) {
				throw new Exceptions\InvalidState('Connection to database could not be established');
			}
		}
	}

	/**
	 * @throws BootstrapExceptions\InvalidState
	 * @throws DBAL\Exception
	 */
	public function request(): void
	{
		if (!$this->database->ping()) {
			$this->database->reconnect();
		}

		// Make sure we don't work with outdated entities
		$this->database->clear();
	}

	/**
	 * @throws BootstrapExceptions\InvalidState
	 */
	public function response(): void
	{
		// Clearing Doctrine's entity manager allows
		// for more memory to be released by PHP
		$this->database->clear();

		// Just in case PHP would choose not to run garbage collection,
		// we run it manually at the end of each batch so that memory is
		// regularly released
		gc_collect_cycles();
	}

}
