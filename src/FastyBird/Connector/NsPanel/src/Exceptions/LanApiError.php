<?php declare(strict_types = 1);

/**
 * LanApiError.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Exceptions
 * @since          1.0.0
 *
 * @date           02.12.23
 */

namespace FastyBird\Connector\NsPanel\Exceptions;

use RuntimeException;

class LanApiError extends RuntimeException implements Exception
{

}
