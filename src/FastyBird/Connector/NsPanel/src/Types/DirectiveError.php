<?php declare(strict_types = 1);

/**
 * DirectiveError.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           09.07.23
 */

namespace FastyBird\Connector\NsPanel\Types;

use Consistence;
use function strval;

/**
 * Directive error type types
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class DirectiveError extends Consistence\Enum\Enum
{

	/**
	 * Permissions
	 */
	public const ENDPOINT_UNREACHABLE = 'ENDPOINT_UNREACHABLE';

	public const ENDPOINT_LOW_POWER = 'ENDPOINT_LOW_POWER';

	public const INVALID_DIRECTIVE = 'INVALID_DIRECTIVE';

	public const NO_SUCH_ENDPOINT = 'NO_SUCH_ENDPOINT';

	public const NOT_SUPPORTED_IN_CURRENT_MODE = 'NOT_SUPPORTED_IN_CURRENT_MODE';

	public const INTERNAL_ERROR = 'INTERNAL_ERROR';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
