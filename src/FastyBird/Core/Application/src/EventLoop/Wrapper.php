<?php declare(strict_types = 1);

/**
 * Wrapper.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Application!
 * @subpackage     Helpers
 * @since          1.0.0
 *
 * @date           02.01.24
 */

namespace FastyBird\Core\Application\EventLoop;

use FastyBird\Core\Application\Events;
use Psr\EventDispatcher;
use React\EventLoop as ReactEventLoop;
use function error_get_last;
use function register_shutdown_function;
use const E_COMPILE_ERROR;
use const E_CORE_ERROR;
use const E_ERROR;
use const E_RECOVERABLE_ERROR;
use const E_USER_ERROR;
use const SIGINT;
use const SIGTERM;

/**
 * React event loop wrapper
 *
 * @package        FastyBird:Application!
 * @subpackage     Helpers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Wrapper implements ReactEventLoop\LoopInterface
{

	private ReactEventLoop\LoopInterface|null $instance = null;

	private bool $stopped = false;

	public function __construct(
		private readonly EventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
	}

	public function addTimer($interval, $callback)
	{
		return $this->get()->addTimer($interval, $callback);
	}

	public function addPeriodicTimer($interval, $callback)
	{
		return $this->get()->addPeriodicTimer($interval, $callback);
	}

	public function cancelTimer(ReactEventLoop\TimerInterface $timer): void
	{
		$this->get()->cancelTimer($timer);
	}

	public function addSignal($signal, $listener): void
	{
		$this->get()->addSignal($signal, $listener);
	}

	public function removeSignal($signal, $listener): void
	{
		$this->get()->removeSignal($signal, $listener);
	}

	public function addReadStream($stream, $listener): void
	{
		$this->get()->addReadStream($stream, $listener);
	}

	public function removeReadStream($stream): void
	{
		$this->get()->removeReadStream($stream);
	}

	public function addWriteStream($stream, $listener): void
	{
		$this->get()->addWriteStream($stream, $listener);
	}

	public function removeWriteStream($stream): void
	{
		$this->get()->removeWriteStream($stream);
	}

	public function futureTick($listener): void
	{
		$this->get()->futureTick($listener);
	}

	public function run(): void
	{
		$this->dispatcher?->dispatch(new Events\EventLoopStarted());

		$this->addSignal(SIGTERM, function (): void {
			$this->dispatcher?->dispatch(new Events\EventLoopStopping());
		});

		$this->addSignal(SIGINT, function (): void {
			$this->dispatcher?->dispatch(new Events\EventLoopStopping());
		});

		$this->get()->run();
	}

	public function stop(): void
	{
		$this->get()->stop();

		$this->dispatcher?->dispatch(new Events\EventLoopStopped());
	}

	private function get(): ReactEventLoop\LoopInterface
	{
		if ($this->instance instanceof ReactEventLoop\LoopInterface) {
			return $this->instance;
		}

		$this->instance = $loop = ReactEventLoop\Factory::create();

		// Automatically run loop at end of program, unless already started or stopped explicitly.
		// This is tested using child processes, so coverage is actually 100%, see BinTest.
		$hasRun = false;

		$loop->futureTick(static function () use (&$hasRun): void {
			$hasRun = true;
		});

		$stopped =&$this->stopped;

		register_shutdown_function(static function () use ($loop, &$hasRun, &$stopped): void {
			// Don't run if we're coming from a fatal error (uncaught exception).
			$error = error_get_last();

			if (($error['type'] ?? 0) & (E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR)) {
				return;
			}

			if (!$hasRun && !$stopped) {
				$loop->run();
			}
		});

		return $this->instance;
	}

}
