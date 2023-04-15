<?php declare(strict_types = 1);

/**
 * SmsNotification.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           04.04.20
 */

namespace FastyBird\Module\Triggers\Schemas\Notifications;

use FastyBird\Library\Metadata\Types\ModuleSource;
use FastyBird\Module\Triggers\Entities;
use Neomerx\JsonApi;
use function array_merge;

/**
 * SMS notification entity schema
 *
 * @extends Notification<Entities\Notifications\SmsNotification>
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class SmsNotification extends Notification
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = ModuleSource::SOURCE_MODULE_TRIGGERS . '/notification/sms';

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

	public function getEntityClass(): string
	{
		return Entities\Notifications\SmsNotification::class;
	}

	/**
	 * @return iterable<string, string|bool|null>
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getAttributes(
		$resource,
		JsonApi\Contracts\Schema\ContextInterface $context,
	): iterable
	{
		return array_merge((array) parent::getAttributes($resource, $context), [
			'phone' => $resource->getPhone()->getInternationalNumber(),
		]);
	}

}
