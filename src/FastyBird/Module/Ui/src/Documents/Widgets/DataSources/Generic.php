<?php declare(strict_types = 1);

/**
 * Generic.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           05.08.24
 */

namespace FastyBird\Module\Ui\Documents\Widgets\DataSources;

use FastyBird\Library\Metadata\Documents\Mapping as DOC;
use FastyBird\Module\Ui\Documents;
use FastyBird\Module\Ui\Entities;

#[DOC\Document(entity: Entities\Widgets\DataSources\Generic::class)]
#[DOC\DiscriminatorEntry(name: Entities\Widgets\DataSources\Generic::TYPE)]
class Generic extends Documents\Widgets\DataSources\DataSource
{

	public static function getType(): string
	{
		return Entities\Widgets\DataSources\Generic::TYPE;
	}

}
