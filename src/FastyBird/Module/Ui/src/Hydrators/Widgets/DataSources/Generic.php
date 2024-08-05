<?php declare(strict_types = 1);

/**
 * Generic.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           03.08.24
 */

namespace FastyBird\Module\Ui\Hydrators\Widgets\DataSources;

use FastyBird\Module\Ui\Entities;

/**
 * Generic data source entity hydrator
 *
 * @extends DataSource<Entities\Widgets\DataSources\Generic>
 *
 * @package        FastyBird:UIModule!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Generic extends DataSource
{

	public function getEntityName(): string
	{
		return Entities\Widgets\DataSources\Generic::class;
	}

}
