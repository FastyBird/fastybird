<?php declare(strict_types = 1);

/**
 * LocalApiBusy.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Exceptions
 * @since          1.0.0
 *
 * @date           17.12.22
 */

namespace FastyBird\Connector\Tuya\Exceptions;

use LogicException;

class LocalApiBusy extends LogicException implements Exception
{

}
