<?php declare(strict_types = 1);

/**
 * Icon.php
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

namespace FastyBird\Module\Ui\Entities\Widgets\Display\Parameters;

use FastyBird\Module\Ui\Types;

/**
 * Display icon parameter interface
 *
 * @package        FastyBird:UIModule!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface Icon
{

	public function setIcon(Types\WidgetIcon $icon): void;

	public function getIcon(): Types\WidgetIcon|null;

}
