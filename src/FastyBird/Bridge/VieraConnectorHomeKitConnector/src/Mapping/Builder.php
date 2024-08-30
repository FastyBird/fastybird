<?php declare(strict_types = 1);

/**
 * Builder.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Helpers
 * @since          1.0.0
 *
 * @date           25.08.24
 */

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\Mapping;

use FastyBird\Bridge\VieraConnectorHomeKitConnector;
use FastyBird\Bridge\VieraConnectorHomeKitConnector\Exceptions;
use FastyBird\Bridge\VieraConnectorHomeKitConnector\Mapping;
use Nette;
use Nette\Utils;
use Orisai\ObjectMapper;
use const DIRECTORY_SEPARATOR;

/**
 * Mapping builder
 *
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Helpers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Builder
{

	private Mapping\Services|null $mapping = null;

	public function __construct(
		private readonly ObjectMapper\Processing\Processor $processor,
	)
	{
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 */
	public function getServicesMapping(): Mapping\Services
	{
		if ($this->mapping === null) {
			try {
				$mapping = VieraConnectorHomeKitConnector\Constants::RESOURCES_FOLDER . DIRECTORY_SEPARATOR . 'mapping.json';
				$mapping = Utils\FileSystem::read($mapping);

				$data = (array) Utils\Json::decode($mapping, forceArrays: true);

			} catch (Utils\JsonException | Nette\IOException) {
				throw new Exceptions\InvalidState('Services mapping could not be loaded');
			}

			$this->mapping = $this->create(Mapping\Services::class, $data);
		}

		return $this->mapping;
	}

	/**
	 * @template T of Mapping\Mapping
	 *
	 * @param class-string<T> $mapping
	 * @param array<mixed> $data
	 *
	 * @return T
	 *
	 * @throws Exceptions\Runtime
	 */
	private function create(string $mapping, array $data): Mapping\Mapping
	{
		try {
			$options = new ObjectMapper\Processing\Options();
			$options->setAllowUnknownFields();

			return $this->processor->process($data, $mapping, $options);
		} catch (ObjectMapper\Exception\InvalidData $ex) {
			$errorPrinter = new ObjectMapper\Printers\ErrorVisualPrinter(
				new ObjectMapper\Printers\TypeToStringConverter(),
			);

			throw new Exceptions\Runtime('Could not map data to mapping: ' . $errorPrinter->printError($ex));
		}
	}

}
