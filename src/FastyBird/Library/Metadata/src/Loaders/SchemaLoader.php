<?php declare(strict_types = 1);

/**
 * SchemaLoader.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Helpers
 * @since          0.1.0
 *
 * @date           09.03.20
 */

namespace FastyBird\Library\Metadata\Loaders;

use FastyBird\Library\Metadata;
use FastyBird\Library\Metadata\Exceptions;
use FastyBird\Library\Metadata\Types;
use function array_key_exists;
use function file_exists;
use function file_get_contents;
use function sprintf;
use function str_replace;
use function strval;
use const DIRECTORY_SEPARATOR;

/**
 * JSON schema loader
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Helpers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class SchemaLoader
{

	/** @var array<string, string>  */
	private array $byRoutingKey = [];

	/** @var array<string, string>  */
	private array $byNamespace = [];

	/**
	 * @throws Exceptions\FileNotFound
	 * @throws Exceptions\InvalidArgument
	 */
	public function loadByRoutingKey(Types\RoutingKey $routingKey): string
	{
		if (array_key_exists(strval($routingKey->getValue()), Metadata\Constants::JSON_SCHEMAS_MAPPING)) {
			if (array_key_exists(
				Metadata\Constants::JSON_SCHEMAS_MAPPING[$routingKey->getValue()],
				$this->byRoutingKey,
			)) {
				return $this->byRoutingKey[Metadata\Constants::JSON_SCHEMAS_MAPPING[$routingKey->getValue()]];
			}

			$schema = file_get_contents(Metadata\Constants::JSON_SCHEMAS_MAPPING[$routingKey->getValue()]);

			if ($schema === false) {
				throw new Exceptions\FileNotFound('Schema could not be loaded');
			}

			$this->byRoutingKey[Metadata\Constants::JSON_SCHEMAS_MAPPING[$routingKey->getValue()]] = $schema;

			return $schema;
		}

		throw new Exceptions\InvalidArgument(
			sprintf('Schema for routing key: %s is not configured', strval($routingKey->getValue())),
		);
	}

	/**
	 * @throws Exceptions\FileNotFound
	 * @throws Exceptions\InvalidArgument
	 */
	public function loadByNamespace(string $namespace, string $schemaFile): string
	{
		if (array_key_exists($namespace . $schemaFile, $this->byNamespace)) {
			return $this->byNamespace[$namespace . $schemaFile];
		}

		$filePath = Metadata\Constants::RESOURCES_FOLDER . DIRECTORY_SEPARATOR . str_replace(
			'/',
			DIRECTORY_SEPARATOR,
			$namespace,
		) . DIRECTORY_SEPARATOR . $schemaFile;

		if (file_exists($filePath)) {
			$schema = file_get_contents($filePath);

			if ($schema === false) {
				throw new Exceptions\FileNotFound('Schema could not be loaded');
			}

			$this->byNamespace[$namespace . $schemaFile] = $schema;

			return $schema;
		}

		throw new Exceptions\InvalidArgument(
			sprintf('Schema for namespace: %s and file: %s is not configured', $namespace, $schemaFile),
		);
	}

}
