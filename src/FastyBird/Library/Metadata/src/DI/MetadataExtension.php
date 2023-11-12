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

use FastyBird\Library\Bootstrap\Boot as BootstrapBoot;
use FastyBird\Library\Metadata\Entities;
use FastyBird\Library\Metadata\Loaders;
use FastyBird\Library\Metadata\Schemas;
use Nette\DI;

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

	public static function register(
		BootstrapBoot\Configurator $config,
		string $extensionName = 'fbMetadataLibrary',
	): void
	{
		$config->onCompile[] = static function (
			BootstrapBoot\Configurator $config,
			DI\Compiler $compiler,
		) use ($extensionName): void {
			$compiler->addExtension($extensionName, new self());
		};
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		$builder->addDefinition('schema.validator', new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Validator::class);

		$builder->addDefinition('loaders.metadata', new DI\Definitions\ServiceDefinition())
			->setType(Loaders\MetadataLoader::class);

		$builder->addDefinition('loaders.schema', new DI\Definitions\ServiceDefinition())
			->setType(Loaders\SchemaLoader::class);

		// Transformer factories
		$builder->addDefinition('entity.factory', new DI\Definitions\ServiceDefinition())
			->setType(Entities\EntityFactory::class);
	}

}
