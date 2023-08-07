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

namespace FastyBird\Connector\NsPanel\Entities\API\States;

use FastyBird\Connector\NsPanel\Types;
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
final class CameraStream implements State
{

	public function __construct(
		#[ObjectMapper\Rules\MappedObjectValue(CameraStreamConfiguration::class)]
		#[ObjectMapper\Modifiers\FieldName(Types\Protocol::CONFIGURATION)]
		private readonly CameraStreamConfiguration $configuration,
	)
	{
	}

	public function getType(): Types\Capability
	{
		return Types\Capability::get(Types\Capability::CAMERA_STREAM);
	}

	public function getProtocols(): array
	{
		return [
			Types\Protocol::STREAM_URL => $this->configuration->getStreamUrl(),
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'value' => $this->configuration->toArray(),
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->{Types\Protocol::CONFIGURATION} = $this->configuration->toJson();

		return $json;
	}

}
