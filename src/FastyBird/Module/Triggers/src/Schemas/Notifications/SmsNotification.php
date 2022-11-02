<?php declare(strict_types = 1);

/**
 * SmsNotificationSchema.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Schemas
 * @since          0.1.0
 *
 * @date           04.04.20
 */

namespace FastyBird\Module\Triggers\Schemas\Notifications;

use FastyBird\Library\Metadata\Types\ModuleSourceType;
use FastyBird\Module\Triggers\Entities;
use Neomerx\JsonApi;

/**
 * SMS notification entity schema
 *
 * @package         FastyBird:TriggersModule!
 * @subpackage      Schemas
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @phpstan-extends NotificationSchema<Entities\Notifications\ISmsNotification>
 */
final class SmsNotificationSchema extends NotificationSchema
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = ModuleSourceType::SOURCE_MODULE_TRIGGERS . '/notification/sms';

	/**
	 * @return string
	 */
	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getEntityClass(): string
	{
		return Entities\Notifications\SmsNotification::class;
	}

	/**
	 * @param Entities\Notifications\ISmsNotification $notification
	 * @param JsonApi\Contracts\Schema\ContextInterface $context
	 *
	 * @return iterable<string, string|bool|null>
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getAttributes($notification, JsonApi\Contracts\Schema\ContextInterface $context): iterable
	{
		return array_merge((array) parent::getAttributes($notification, $context), [
			'phone' => $notification->getPhone()->getInternationalNumber(),
		]);
	}

}
