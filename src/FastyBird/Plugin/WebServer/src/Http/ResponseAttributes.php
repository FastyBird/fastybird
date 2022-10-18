<?php declare(strict_types = 1);

/**
 * ResponseAttributes.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WebServerPlugin!
 * @subpackage     Http
 * @since          0.1.0
 *
 * @date           17.03.20
 */

namespace FastyBird\Plugin\WebServer\Http;

interface ResponseAttributes
{

	public const ATTR_ENTITY = '__entity__';

	public const ATTR_TOTAL_COUNT = '__total_records_count__';

}
