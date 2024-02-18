<?php declare(strict_types = 1);

/**
 * Name.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Helpers
 * @since          1.0.0
 *
 * @date           12.07.23
 */

namespace FastyBird\Connector\NsPanel\Helpers;

use FastyBird\Connector\NsPanel\Types;
use Nette\Utils;
use TypeError;
use ValueError;
use function lcfirst;
use function preg_replace;
use function str_replace;
use function strtolower;
use function strval;
use function ucwords;

/**
 * Useful name helpers
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Helpers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Name
{

	public static function convertCapabilityToChannel(
		Types\Capability $capability,
		string|int|null $name = null,
	): string
	{
		$identifier = str_replace('-', '_', Utils\Strings::lower($capability->value));

		if ($name !== null) {
			$identifier .= '_' . $name;
		}

		return $identifier;
	}

	public static function convertProtocolToProperty(Types\Protocol $protocol): string
	{
		return strtolower(strval(preg_replace('/(?<!^)[A-Z]/', '_$0', $protocol->value)));
	}

	/**
	 * @throws TypeError
	 * @throws ValueError
	 */
	public static function convertPropertyToProtocol(string $identifier): Types\Protocol
	{
		return Types\Protocol::from(
			lcfirst(
				str_replace(
					' ',
					'',
					ucwords(
						str_replace(
							'_',
							' ',
							$identifier,
						),
					),
				),
			),
		);
	}

}
