<?php declare(strict_types = 1);

/**
 * Request.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Events
 * @since          1.0.0
 *
 * @date           20.06.24
 */

namespace FastyBird\Module\Accounts\Events;

use Nette\Application;
use Symfony\Contracts\EventDispatcher;

/**
 * Application request event
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Subscribers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Request extends EventDispatcher\Event
{

	public function __construct(
		private readonly Application\Application $application,
		private readonly Application\Request $request,
	)
	{
	}

	public function getApplication(): Application\Application
	{
		return $this->application;
	}

	public function getRequest(): Application\Request
	{
		return $this->request;
	}

}
