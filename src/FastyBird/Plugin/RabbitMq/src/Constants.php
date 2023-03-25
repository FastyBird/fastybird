<?php declare(strict_types = 1);

/**
 * Constants.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RabbitMqPlugin!
 * @subpackage     common
 * @since          1.0.0
 *
 * @date           02.03.20
 */

namespace FastyBird\Plugin\RabbitMq;

/**
 * Libs constants
 *
 * @package        FastyBird:RabbitMqPlugin!
 * @subpackage     common
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Constants
{

	/**
	 * Message bus data exchange name
	 */
	public const RABBIT_MQ_MESSAGE_BUS_EXCHANGE_NAME = 'fb.exchange.bus';

}
