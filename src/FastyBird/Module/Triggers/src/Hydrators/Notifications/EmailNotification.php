<?php declare(strict_types = 1);

/**
 * EmailNotificationHydrator.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Hydrators
 * @since          0.1.0
 *
 * @date           04.04.20
 */

namespace FastyBird\Module\Triggers\Hydrators\Notifications;

use FastyBird\JsonApi\Exceptions as JsonApiExceptions;
use FastyBird\Module\Triggers\Entities;
use Fig\Http\Message\StatusCodeInterface;
use IPub\JsonAPIDocument;
use Nette\Utils;

/**
 * Email notification entity hydrator
 *
 * @package         FastyBird:TriggersModule!
 * @subpackage      Hydrators
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @phpstan-extends NotificationHydrator<Entities\Notifications\IEmailNotification>
 */
final class EmailNotificationHydrator extends NotificationHydrator
{

	/** @var string[] */
	protected array $attributes = [
		'email',
		'enabled',
	];

	/**
	 * {@inheritDoc}
	 */
	public function getEntityName(): string
	{
		return Entities\Notifications\EmailNotification::class;
	}

	/**
	 * @param JsonAPIDocument\Objects\IStandardObject $attributes
	 *
	 * @return string
	 *
	 * @throws JsonApiExceptions\IJsonApiException
	 */
	protected function hydrateEmailAttribute(
		JsonAPIDocument\Objects\IStandardObject $attributes
	): string {
		// Condition operator have to be set
		if (
			!is_scalar($attributes->get('email'))
			|| !$attributes->has('email')
			|| !Utils\Validators::isEmail((string) $attributes->get('email'))
		) {
			throw new JsonApiExceptions\JsonApiErrorException(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				$this->translator->translate('//triggers-module.notifications.messages.invalidEmailAddress.heading'),
				$this->translator->translate('//triggers-module.notifications.messages.invalidEmailAddress.message'),
				[
					'pointer' => '/data/attributes/email',
				]
			);
		}

		return (string) $attributes->get('email');
	}

}
