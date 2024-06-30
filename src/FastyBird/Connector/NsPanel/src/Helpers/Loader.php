<?php declare(strict_types = 1);

/**
 * Loader.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Helpers
 * @since          1.0.0
 *
 * @date           20.07.23
 */

namespace FastyBird\Connector\NsPanel\Helpers;

use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\Exceptions;
use Nette;
use Nette\Utils;
use const DIRECTORY_SEPARATOR;

/**
 * Data structure loader
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Helpers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Loader
{

	private Utils\ArrayHash|null $categories = null;

	private Utils\ArrayHash|null $capabilities = null;

	private Utils\ArrayHash|null $protocols = null;

	/**
	 * @throws Exceptions\InvalidState
	 * @throws Nette\IOException
	 */
	public function loadCategories(): Utils\ArrayHash
	{
		if ($this->categories === null) {
			$metadata = NsPanel\Constants::RESOURCES_FOLDER . DIRECTORY_SEPARATOR . 'categories.json';
			$metadata = Utils\FileSystem::read($metadata);

			try {
				$this->categories = Utils\ArrayHash::from(
					(array) Utils\Json::decode($metadata, forceArrays: true),
				);
			} catch (Utils\JsonException) {
				throw new Exceptions\InvalidState('Categories metadata could not be loaded');
			}
		}

		return $this->categories;
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws Nette\IOException
	 */
	public function loadCapabilities(): Utils\ArrayHash
	{
		if ($this->capabilities === null) {
			$metadata = NsPanel\Constants::RESOURCES_FOLDER . DIRECTORY_SEPARATOR . 'capabilities.json';
			$metadata = Utils\FileSystem::read($metadata);

			try {
				$this->capabilities = Utils\ArrayHash::from(
					(array) Utils\Json::decode($metadata, forceArrays: true),
				);
			} catch (Utils\JsonException) {
				throw new Exceptions\InvalidState('Capabilities metadata could not be loaded');
			}
		}

		return $this->capabilities;
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws Nette\IOException
	 */
	public function loadProtocols(): Utils\ArrayHash
	{
		if ($this->protocols === null) {
			$metadata = NsPanel\Constants::RESOURCES_FOLDER . DIRECTORY_SEPARATOR . 'protocols.json';
			$metadata = Utils\FileSystem::read($metadata);

			try {
				$this->protocols = Utils\ArrayHash::from(
					(array) Utils\Json::decode($metadata, forceArrays: true),
				);
			} catch (Utils\JsonException) {
				throw new Exceptions\InvalidState('Protocols metadata could not be loaded');
			}
		}

		return $this->protocols;
	}

}
