<?php declare(strict_types = 1);

/**
 * ConsoleApplication.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WebServerPlugin!
 * @subpackage     Application
 * @since          0.1.0
 *
 * @date           21.05.21
 */

namespace FastyBird\Plugin\WebServer\Application;

use Exception;
use FastyBird\Plugin\WebServer\Commands;
use Symfony\Component\Console as SymfonyConsole;

/**
 * Console application
 *
 * @package        FastyBird:WebServerPlugin!
 * @subpackage     Application
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Console
{

	private SymfonyConsole\Application $application;

	public function __construct(SymfonyConsole\Application|null $application = null)
	{
		$this->application = $application ?? new SymfonyConsole\Application();
	}

	/**
	 * @throws Exception
	 * @throws SymfonyConsole\Exception\InvalidArgumentException
	 */
	public function run(
		SymfonyConsole\Input\InputInterface|null $input = null,
		SymfonyConsole\Output\OutputInterface|null $output = null,
	): int
	{
		if ($input === null) {
			$input = new SymfonyConsole\Input\ArrayInput([
				'command' => Commands\HttpServer::NAME,
			]);

		} else {
			$input->setArgument('command', Commands\HttpServer::NAME);
		}

		return $this->application->run($input, $output);
	}

}
