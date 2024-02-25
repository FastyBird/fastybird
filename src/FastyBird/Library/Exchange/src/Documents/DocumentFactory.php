<?php declare(strict_types = 1);

/**
 * DocumentFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ExchangeLibrary!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           13.06.22
 */

namespace FastyBird\Library\Exchange\Documents;

use FastyBird\Library\Exchange\Documents;
use FastyBird\Library\Exchange\Exceptions;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use Nette\Utils;
use ReflectionClass;
use function array_key_exists;
use function assert;
use function is_subclass_of;
use function sprintf;

/**
 * Exchange document factory
 *
 * @package        FastyBird:ExchangeLibrary!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DocumentFactory
{

	/** @var array<string, class-string<MetadataDocuments\Document>>|null */
	private array|null $routingMap = null;

	/** @var Mapping\Driver\AttributeReader<Documents\Mapping\MappingAttribute> */
	private Documents\Mapping\Driver\AttributeReader $reader;

	public function __construct(
		private readonly MetadataDocuments\Mapping\Driver\MappingDriver $mappingDriver,
		private readonly MetadataDocuments\DocumentFactory $documentFactory,
	)
	{
		$this->reader = new Documents\Mapping\Driver\AttributeReader();
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function create(
		Utils\ArrayHash $data,
		string $routingKey,
	): MetadataDocuments\Document
	{
		return $this->documentFactory->create(
			$this->loadDocument($routingKey),
			$data,
		);
	}

	/**
	 * @return class-string<MetadataDocuments\Document>
	 *
	 * @throws Exceptions\InvalidState
	 */
	private function loadDocument(string $routingKey): string
	{
		if ($this->routingMap === null) {
			$this->initialize();
		}

		if ($this->routingMap !== null && array_key_exists($routingKey, $this->routingMap)) {
			return $this->routingMap[$routingKey];
		}

		throw new Exceptions\InvalidState(
			sprintf('Document class was not found for provided message and routing key: %s', $routingKey),
		);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	private function initialize(): void
	{
		$this->routingMap = [];

		foreach ($this->mappingDriver->getAllClassNames() as $className) {
			if (!is_subclass_of($className, MetadataDocuments\Document::class)) {
				continue;
			}

			$classAttributes = $this->reader->getClassAttributes(new ReflectionClass($className));

			if (isset($classAttributes[Documents\Mapping\RoutingMap::class])) {
				$routingMapAttribute = $classAttributes[Documents\Mapping\RoutingMap::class];
				assert($routingMapAttribute instanceof Documents\Mapping\RoutingMap);

				foreach ($routingMapAttribute->value as $route) {
					if (array_key_exists($route, $this->routingMap)) {
						throw new Exceptions\InvalidState(sprintf(
							'Found duplicate route definition: "%s" for document class: "%s"',
							$route,
							$className,
						));
					}

					$this->routingMap[$route] = $className;
				}
			}
		}
	}

}
