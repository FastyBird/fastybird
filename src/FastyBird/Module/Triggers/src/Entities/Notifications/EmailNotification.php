<?php declare(strict_types = 1);

/**
 * EmailNotification.php
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

namespace FastyBird\Module\Triggers\Entities\Notifications;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Library\Application\Doctrine\Mapping as ApplicationMapping;
use FastyBird\Module\Triggers\Entities;
use IPub\DoctrineCrud\Mapping\Attribute as IPubDoctrine;
use Ramsey\Uuid;
use function array_merge;
use function assert;
use function is_string;

#[ORM\Entity]
#[ORM\Table(
	name: 'fb_triggers_module_notifications_emails',
	options: [
		'collate' => 'utf8mb4_general_ci',
		'charset' => 'utf8mb4',
		'comment' => 'Emails notifications',
	],
)]
#[ApplicationMapping\DiscriminatorEntry(name: self::TYPE)]
class EmailNotification extends Notification
{

	public const TYPE = 'email';

	#[IPubDoctrine\Crud(required: true, writable: true)]
	#[ORM\Column(name: 'notification_email', type: 'string', nullable: false)]
	private string|null $email;

	public function __construct(
		string $email,
		Entities\Triggers\Trigger $trigger,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($trigger, $id);

		$this->email = $email;
	}

	public static function getType(): string
	{
		return self::TYPE;
	}

	public function getEmail(): string
	{
		assert(is_string($this->email));

		return $this->email;
	}

	public function setEmail(string $email): void
	{
		$this->email = $email;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'email' => $this->getEmail(),
		]);
	}

}
