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
use TypeError;
use ValueError;

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

	public static function convertCapabilityToChannel(Types\Capability $type, string|int|null $name = null): string
	{
		$identifier = $type->value;

		if ($name !== null) {
			$identifier .= '_' . $name;
		}

		return $identifier;
	}

	public static function convertAttributeToProperty(Types\Attribute $type): string
	{
		return $type->value;
	}

	/**
	 * @throws TypeError
	 * @throws ValueError
	 */
	public static function convertPropertyToAttribute(string $identifier): Types\Attribute
	{
		return Types\Attribute::from($identifier);
	}

}
