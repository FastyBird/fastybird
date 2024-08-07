<?php declare(strict_types = 1);

/**
 * DataSourceAction.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           05.08.24
 */

namespace FastyBird\Module\Ui\Types;

/**
 * Property action
 *
 * @package        FastyBird:UIModule!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum DataSourceAction: string
{

	case SET = 'set';

	case GET = 'get';

}
