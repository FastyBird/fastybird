<?php declare(strict_types = 1);

/**
 * Logger.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Bootstrap!
 * @subpackage     Helpers
 * @since          1.0.0
 *
 * @date           08.04.23
 */

namespace FastyBird\Library\Bootstrap\Helpers;

use Nette;
use Throwable;
use Tracy;
use function array_merge;

/**
 * Logger helpers
 *
 * @package         FastyBird:Bootstrap!
 * @subpackage      Helpers
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Logger
{

	use Nette\SmartObject;

	/**
	 * @return array<array<string, string|int>>
	 */
	public static function buildException(Throwable $ex): array
	{
		Tracy\Debugger::log($ex, Tracy\Debugger::ERROR);

		return self::processAllExceptions($ex);
	}

	/**
	 * @return array<array<string, string|int>>
	 */
	private static function processAllExceptions(Throwable $ex): array
	{
		$result = [
			[
				'message' => $ex->getMessage(),
				'code' => $ex->getCode(),
			],
		];

		if ($ex->getPrevious() !== null) {
			$result = array_merge($result, self::processAllExceptions($ex->getPrevious()));
		}

		return $result;
	}

}
