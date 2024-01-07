<?php declare(strict_types = 1);

/**
 * TextType.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           24.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Entities\Messages\Exposes;

use FastyBird\Connector\Zigbee2Mqtt\Types;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use Orisai\ObjectMapper;

/**
 * Text type expose type message
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class TextType extends Type
{

	public function __construct(
		#[ObjectMapper\Rules\ArrayEnumValue(cases: [Types\ExposeType::TEXT])]
		private readonly string $type,
	)
	{
		parent::__construct();
	}

	public function getType(): Types\ExposeType
	{
		return Types\ExposeType::get($this->type);
	}

	public function getDataType(): MetadataTypes\DataType
	{
		return MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING);
	}

}
