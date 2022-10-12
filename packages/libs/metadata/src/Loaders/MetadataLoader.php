<?php declare(strict_types = 1);

/**
 * MetadataLoader.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Metadata!
 * @subpackage     Loaders
 * @since          0.1.0
 *
 * @date           24.06.20
 */

namespace FastyBird\Metadata\Loaders;

use FastyBird\Metadata;
use FastyBird\Metadata\Exceptions;
use FastyBird\Metadata\Schemas;
use Nette\Utils;
use function file_get_contents;
use const DIRECTORY_SEPARATOR;

/**
 * Metadata loader
 *
 * @package        FastyBird:Metadata!
 * @subpackage     Loaders
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class MetadataLoader
{

	public function __construct(private readonly Schemas\Validator $jsonValidator)
	{
	}

	/**
	 * @throws Exceptions\FileNotFound
	 */
	public function load(): Utils\ArrayHash
	{
		$schema = Metadata\Constants::RESOURCES_FOLDER . DIRECTORY_SEPARATOR . 'schemas' . DIRECTORY_SEPARATOR . 'application.json';

		$metadata = Metadata\Constants::RESOURCES_FOLDER . DIRECTORY_SEPARATOR . 'application.json';

		$schema = file_get_contents($schema);

		if ($schema === false) {
			throw new Exceptions\FileNotFound('Schema could not be loaded');
		}

		$metadata = file_get_contents($metadata);

		if ($metadata === false) {
			throw new Exceptions\FileNotFound('Metadata could not be loaded');
		}

		return $this->jsonValidator->validate($metadata, $schema);
	}

}
