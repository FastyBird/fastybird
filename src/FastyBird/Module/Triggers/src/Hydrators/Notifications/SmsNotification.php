<?php declare(strict_types = 1);

/**
 * SmsNotificationHydrator.php
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

use Contributte\Translation;
use Doctrine\Persistence;
use FastyBird\JsonApi\Exceptions as JsonApiExceptions;
use FastyBird\Module\Triggers\Entities;
use Fig\Http\Message\StatusCodeInterface;
use IPub\JsonAPIDocument;
use IPub\Phone;

/**
 * SMS notification entity hydrator
 *
 * @package         FastyBird:TriggersModule!
 * @subpackage      Hydrators
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @phpstan-extends NotificationHydrator<Entities\Notifications\ISmsNotification>
 */
final class SmsNotificationHydrator extends NotificationHydrator
{

	/** @var string[] */
	protected array $attributes = [
		'phone',
		'enabled',
	];

	/** @var Phone\Phone */
	private Phone\Phone $phone;

	public function __construct(
		Phone\Phone $phone,
		Persistence\ManagerRegistry $managerRegistry,
		Translation\Translator $translator
	) {
		parent::__construct($managerRegistry, $translator);

		$this->phone = $phone;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getEntityName(): string
	{
		return Entities\Notifications\SmsNotification::class;
	}

	/**
	 * @param JsonAPIDocument\Objects\IStandardObject $attributes
	 *
	 * @return Phone\Entities\Phone
	 *
	 * @throws JsonApiExceptions\IJsonApiException
	 * @throws Phone\Exceptions\NoValidCountryException
	 * @throws Phone\Exceptions\NoValidPhoneException
	 * @throws Phone\Exceptions\NoValidTypeException
	 */
	protected function hydratePhoneAttribute(
		JsonAPIDocument\Objects\IStandardObject $attributes
	): Phone\Entities\Phone {
		// Condition operator have to be set
		if (
			!is_scalar($attributes->get('phone'))
			|| !$attributes->has('phone')
			|| !$this->phone->isValid((string) $attributes->get('phone'), 'CZ')
		) {
			throw new JsonApiExceptions\JsonApiErrorException(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				$this->translator->translate('//triggers-module.notifications.messages.invalidPhone.heading'),
				$this->translator->translate('//triggers-module.notifications.messages.invalidPhone.message'),
				[
					'pointer' => '/data/attributes/phone',
				]
			);
		}

		return $this->phone->parse((string) $attributes->get('phone'), 'CZ');
	}

}
