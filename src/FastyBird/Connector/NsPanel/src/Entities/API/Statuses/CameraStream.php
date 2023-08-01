<?php declare(strict_types = 1);

/**
 * CameraStream.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           09.07.23
 */

namespace FastyBird\Connector\NsPanel\Entities\API\Statuses;

use FastyBird\Connector\NsPanel\Types;
use Nette;
use Orisai\ObjectMapper;
use stdClass;

/**
 * Camera stream capability state
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class CameraStream implements Status, ObjectMapper\MappedObject
{

	public function __construct(
		private readonly object $configuration,
	)
	{
	}

	public function getType(): Types\Capability
	{
		return Types\Capability::get(Types\Capability::CAMERA_STREAM);
	}

	public function getName(): string|null
	{
		return null;
	}

	public function getValue(): string
	{
		return '';
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			$this->getType()->getValue() => $this->getValue(),
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->configuration = new stdClass();
		$json->configuration->streamUrl = $this->getValue();

		return $json;
	}

}
