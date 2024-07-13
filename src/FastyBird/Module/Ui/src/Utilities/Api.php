<?php declare(strict_types = 1);

/**
 * Api.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     Utilities
 * @since          1.0.0
 *
 * @date           26.10.22
 */

namespace FastyBird\Module\Ui\Utilities;

use function preg_replace;
use function strtolower;
use function strval;

class Api
{

	public static function fieldToJsonApi(string $field): string
	{
		return strtolower(strval(preg_replace('/(?<!^)[A-Z]/', '_$0', $field)));
	}

}
