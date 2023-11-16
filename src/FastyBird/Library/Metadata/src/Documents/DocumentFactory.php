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
use function is_object;
use function is_string;

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
		private readonly ObjectMapper\Processing\Processor $documentMapper,
	)
	{
	}

	/**
	 * @template T of Documents\Document
	 *
	 * @param class-string<T> $document
	 * @param array<mixed>|string|object $data
	 *
	 * @return T
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\MalformedInput
	 */
	public function create(string $document, array|string|object $data): Documents\Document
	{
		if (is_string($data)) {
			try {
				$data = Utils\Json::decode($data, Utils\Json::FORCE_ARRAY);

			} catch (Utils\JsonException $ex) {
				throw new Exceptions\MalformedInput('Failed to decode input data', 0, $ex);
			}
		} elseif (is_object($data)) {
			try {
				$data = Utils\Json::decode(Utils\Json::encode($data), Utils\Json::FORCE_ARRAY);

			} catch (Utils\JsonException $ex) {
				throw new Exceptions\MalformedInput('Failed to decode input data', 0, $ex);
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
