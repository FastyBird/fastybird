<?php declare(strict_types = 1);

/**
 * ManualTrigger.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           04.04.20
 */

namespace FastyBird\Module\Triggers\Entities\Triggers;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Library\Metadata\Types as MetadataTypes;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="fb_triggers_module_triggers_manual",
 *     options={
 *       "collate"="utf8mb4_general_ci",
 *       "charset"="utf8mb4",
 *       "comment"="Manual triggers"
 *     }
 * )
 */
class ManualTrigger extends Trigger
{

	public function getType(): MetadataTypes\TriggerType
	{
		return MetadataTypes\TriggerType::get(MetadataTypes\TriggerType::TYPE_MANUAL);
	}

}
