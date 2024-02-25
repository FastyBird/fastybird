<?php declare(strict_types = 1);

/**
 * MetadataExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     DI
 * @since          1.0.0
 *
 * @date           24.06.20
 */

namespace FastyBird\Library\Metadata\DI;

use FastyBird\Library\Application\Boot as ApplicationBoot;
use FastyBird\Library\Metadata\Documents;
use FastyBird\Library\Metadata\Exceptions;
use FastyBird\Library\Metadata\Schemas;
use Nette\Caching;
use Nette\DI;
use Nette\Schema;
use stdClass;
use function array_values;
use function assert;
use function is_dir;
use function sprintf;

/**
 * Metadata extension container
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class MetadataExtension extends DI\CompilerExtension
{

	public const DRIVER_TAG = 'fastybird.metadata.attribute.driver';

	public static function register(
		ApplicationBoot\Configurator $config,
		string $extensionName = 'fbMetadataLibrary',
	): void
	{
		$config->onCompile[] = static function (
			ApplicationBoot\Configurator $config,
			DI\Compiler $compiler,
		) use ($extensionName): void {
			$compiler->addExtension($extensionName, new self());
		};
	}

	public function getConfigSchema(): Schema\Schema
	{
		return Schema\Expect::structure([
			'documents' => Schema\Expect::structure([
				'mapping' => Schema\Expect::arrayOf(Schema\Expect::string(), Schema\Expect::string())->required(),
				'excludePaths' => Schema\Expect::arrayOf(Schema\Expect::string(), Schema\Expect::string()),
			]),
		]);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();
		assert($configuration instanceof stdClass);

		$builder->addDefinition('schema.validator', new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Validator::class);

		/**
		 * DOCUMENTS SERVICES
		 */

		$metadataCache = $builder->addDefinition(
			$this->prefix('document.cache'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Caching\Cache::class)
			->setArguments([
				'namespace' => 'metadata_class_metadata',
			])
			->setAutowired(false);

		$builder->addDefinition('document.factory', new DI\Definitions\ServiceDefinition())
			->setType(Documents\DocumentFactory::class);

		$attributeDriver = $builder->addDefinition(
			'document.mapping.attributeDriver',
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Documents\Mapping\Driver\AttributeDriver::class)
			->setArguments([
				'paths' => array_values($configuration->documents->mapping),
			])
			->addSetup('addExcludePaths', [$configuration->documents->excludePaths])
			->addTag(self::DRIVER_TAG)
			->setAutowired(false);

		$mappingDriver = $builder->addDefinition(
			'document.mapping.mappingDriver',
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Documents\Mapping\Driver\MappingDriverChain::class);

		$builder->addDefinition('document.mapping.classMetadataFactory', new DI\Definitions\ServiceDefinition())
			->setType(Documents\Mapping\ClassMetadataFactory::class)
			->setArguments([
				'driver' => $mappingDriver,
				'cache' => $metadataCache,
			]);

		foreach ($configuration->documents->mapping as $namespace => $path) {
			if (!is_dir($path)) {
				throw new Exceptions\InvalidState(sprintf('Given mapping path "%s" does not exist', $path));
			}

			$mappingDriver->addSetup('addDriver', [$attributeDriver, $namespace]);
		}
	}

}
