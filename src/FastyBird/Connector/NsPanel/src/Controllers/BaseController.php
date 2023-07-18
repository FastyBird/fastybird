<?php declare(strict_types = 1);

/**
 * BaseController.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Controllers
 * @since          1.0.0
 *
 * @date           11.07.23
 */

namespace FastyBird\Connector\NsPanel\Controllers;

use FastyBird\Connector\NsPanel;
use Nette;

/**
 * API base controller
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Controllers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class BaseController
{

	use Nette\SmartObject;

	protected NsPanel\Logger $logger;

	public function injectLogger(NsPanel\Logger $logger): void
	{
		$this->logger = $logger;
	}

}
