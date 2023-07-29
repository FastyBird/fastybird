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
use function array_map;
use function explode;
use function implode;
use function in_array;
use function is_string;
use function lcfirst;
use function preg_replace;
use function str_replace;
use function strtolower;
use function strtoupper;
use function strval;
use function ucfirst;
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

	public static function createName(string $identifier): string|null
	{
		$transformed = preg_replace('/(?<!^)[A-Z]/', '_$0', $identifier);

		if (!is_string($transformed)) {
			return null;
		}

		$transformed = strtolower($transformed);
		$transformed = ucfirst(strtolower(str_replace('_', ' ', $transformed)));
		$transformed = explode(' ', $transformed);
		$transformed = array_map(static function (string $part): string {
			if (in_array(strtolower($part), ['ip', 'mac', 'id', 'uid'], true)) {
				return strtoupper($part);
			}

			return $part;
		}, $transformed);

		return ucfirst(implode(' ', $transformed));
	}

	public static function convertCapabilityToChannel(
		Types\Capability $capability,
		string|int|null $name = null,
	): string
	{
		$identifier = str_replace('-', '_', Utils\Strings::lower($capability->getValue()));

		if ($name !== null) {
			$identifier .= '_' . $name;
		}

		return $identifier;
	}

	public static function convertProtocolToProperty(Types\Protocol $protocol): string
	{
		return strtolower(strval(preg_replace('/(?<!^)[A-Z]/', '_$0', $protocol->getValue())));
	}

	public static function convertPropertyToProtocol(string $identifier): Types\Protocol
	{
		return Types\Protocol::get(
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