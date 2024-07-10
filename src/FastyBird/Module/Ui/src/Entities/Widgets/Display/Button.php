<?php declare(strict_types = 1);

/**
 * Button.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           25.05.20
 */

namespace FastyBird\Module\Ui\Entities\Widgets\Display;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Library\Application\Entities\Mapping as ApplicationMapping;
use FastyBird\Module\Ui\Entities;
use function array_merge;

#[ORM\Entity]
#[ApplicationMapping\DiscriminatorEntry(name: self::TYPE)]
class Button extends Display implements Entities\Widgets\Display\Parameters\Icon
{

	use Entities\Widgets\Display\Parameters\TIcon;

	public const TYPE = 'button';

	public static function getType(): string
	{
		return self::TYPE;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'icon' => $this->getIcon()?->value,
		]);
	}

}
