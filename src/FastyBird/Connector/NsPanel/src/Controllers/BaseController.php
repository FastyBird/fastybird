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
use FastyBird\Connector\NsPanel\Exceptions;
use Nette;
use Nette\Utils;
use const DIRECTORY_SEPARATOR;

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

	public function setLogger(NsPanel\Logger $logger): void
	{
		$this->logger = $logger;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	protected function getSchema(string $schemaFilename): string
	{
		try {
			$schema = Utils\FileSystem::read(
				NsPanel\Constants::RESOURCES_FOLDER . DIRECTORY_SEPARATOR . 'request' . DIRECTORY_SEPARATOR . $schemaFilename,
			);
		} catch (Nette\IOException) {
			throw new Exceptions\InvalidArgument('Validation schema for request could not be loaded');
		}

		return $schema;
	}

}
