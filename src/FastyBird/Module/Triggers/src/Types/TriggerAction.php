<?php declare(strict_types = 1);

/**
 * TriggerAction.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           01.06.22
 */

namespace FastyBird\Module\Triggers\Types;

/**
 * Trigger action
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum TriggerAction: string
{

	case SET = 'set';

}
