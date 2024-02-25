<?php declare(strict_types = 1);

/**
 * MissingValue.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Exceptions
 * @since          1.0.0
 *
 * @date           25.02.24
 */

namespace FastyBird\Connector\NsPanel\Exceptions;

use LogicException as PHPLogicException;

class MissingValue extends PHPLogicException implements Exception
{

}
