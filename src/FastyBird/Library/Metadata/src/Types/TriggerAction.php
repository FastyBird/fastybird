<?php declare(strict_types = 1);

/**
 * TriggerAction.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           01.06.22
 */

namespace FastyBird\Library\Metadata\Types;

/**
 * Trigger action
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum TriggerAction: string
{

	case SET = 'set';

}
