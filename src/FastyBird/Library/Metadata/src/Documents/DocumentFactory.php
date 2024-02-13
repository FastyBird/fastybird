<?php declare(strict_types = 1);

/**
 * DocumentFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           02.06.22
 */

namespace FastyBird\Library\Metadata\Documents;

use FastyBird\Library\Metadata\Documents;
use FastyBird\Library\Metadata\Exceptions;
use Nette\Utils;
use Orisai\ObjectMapper;
use function array_key_exists;
use function assert;
use function is_int;
use function is_object;
use function is_string;
use function sprintf;

/**
 * Data document factory
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DocumentFactory
{

	public function __construct(
		private readonly Mapping\ClassMetadataFactory $classMetadataFactory,
		private readonly ObjectMapper\Processing\Processor $documentMapper,
	)
	{
	}

	/**
	 * @template T of Documents\Document
	 *
	 * @param class-string<T> $document
	 * @param array<string, mixed>|string|object $data
	 *
	 * @return T
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\MalformedInput
	 * @throws Exceptions\Mapping
	 */
	public function create(string $document, array|string|object $data): Documents\Document
	{
		if (is_string($data)) {
			try {
				/** @var array<string, mixed> $data */
				$data = Utils\Json::decode($data, Utils\Json::FORCE_ARRAY);

			} catch (Utils\JsonException $ex) {
				throw new Exceptions\MalformedInput('Failed to decode input data', 0, $ex);
			}
		} elseif (is_object($data)) {
			try {
				/** @var array<string, mixed> $data */
				$data = Utils\Json::decode(Utils\Json::encode($data), Utils\Json::FORCE_ARRAY);

			} catch (Utils\JsonException $ex) {
				throw new Exceptions\MalformedInput('Failed to decode input data', 0, $ex);
			}
		}

		$metadata = $this->classMetadataFactory->getMetadataFor($document);

		if (!$metadata->isInheritanceTypeNone()) {
			$discriminatorColumnSettings = $metadata->getDiscriminatorColumn();

			if (
				$discriminatorColumnSettings === null
				|| !array_key_exists('name', $discriminatorColumnSettings)
				|| !array_key_exists('type', $discriminatorColumnSettings)
			) {
				throw new Exceptions\InvalidState(sprintf(
					'Discriminator column configuration is missing on class: "%s"',
					$metadata->getName(),
				));
			}

			$discriminatorColumn = $discriminatorColumnSettings['name'];

			if (!array_key_exists($discriminatorColumn, $data)) {
				throw new Exceptions\InvalidArgument(sprintf(
					'Discriminator column: "%s" is missing in data',
					$discriminatorColumn,
				));
			}

			$type = $data[$discriminatorColumn];
			assert(is_string($type) || is_int($type));

			$discriminatorMap = $metadata->getDiscriminatorMap();

			if (!array_key_exists($type, $discriminatorMap)) {
				throw new Exceptions\InvalidArgument(sprintf(
					'Missing discriminator map record for key: "%s" in class: "%s"',
					$type,
					$metadata->getName(),
				));
			}

			if ($metadata->isRootDocument()) {
				$document = $discriminatorMap[$type];
			} elseif ($metadata->getDiscriminatorValue() !== $type) {
				throw new Exceptions\InvalidArgument(sprintf(
					'Provided document class is different than discriminator value: "%s"',
					$metadata->getName(),
				));
			}
		}

		try {
			$options = new ObjectMapper\Processing\Options();
			$options->setAllowUnknownFields();

			return $this->documentMapper->process($data, $document, $options);
		} catch (ObjectMapper\Exception\InvalidData $ex) {
			$errorPrinter = new ObjectMapper\Printers\ErrorVisualPrinter(
				new ObjectMapper\Printers\TypeToStringConverter(),
			);

			throw new Exceptions\InvalidArgument('Could not map data to document: ' . $errorPrinter->printError($ex));
		}
	}

}
