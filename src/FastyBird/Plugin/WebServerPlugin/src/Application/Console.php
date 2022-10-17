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

namespace FastyBird\WebServerPlugin\Application;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;
use Throwable;

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

	private Application $application;

	public function __construct(Application|null $application = null)
	{
		$this->application = $application ?? new Application();
	}

	/**
	 * @throws Throwable
	 */
	public function run(Input\InputInterface|null $input = null, Output\OutputInterface|null $output = null): int
	{
		if ($input === null) {
			$input = new Input\ArrayInput([
				'command' => 'fb:web-server:start',
			]);

		} else {
			$input->setArgument('command', 'fb:web-server:start');
		}

		return $this->application->run($input, $output);
	}

}
