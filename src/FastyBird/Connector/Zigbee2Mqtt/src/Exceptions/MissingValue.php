<?php declare(strict_types = 1);

/**
 * MissingValue.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Exceptions
 * @since          1.0.0
 *
 * @date           25.02.24
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Exceptions;

use LogicException as PHPLogicException;

class MissingValue extends PHPLogicException implements Exception
{

}
