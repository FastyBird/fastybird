<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Tests\Cases\Unit\Helpers;

use DateTimeImmutable;
use Exception;
use FastyBird\DateTimeFactory;
use FastyBird\Module\Accounts\Helpers;
use PHPUnit\Framework\TestCase;

final class SecurityHashTest extends TestCase
{

	/**
	 * @throws Exception
	 */
	public function testPassword(): void
	{
		$dateFactory = $this->createMock(DateTimeFactory\Factory::class);
		$dateFactory
			->method('getNow')
			->willReturn(new DateTimeImmutable('2020-04-01T12:00:00+00:00'));

		$hashHelper = new Helpers\SecurityHash($dateFactory);

		$hash = $hashHelper->createKey();

		self::assertTrue($hashHelper->isValid($hash));

		$dateFactory = $this->createMock(DateTimeFactory\Factory::class);
		$dateFactory
			->method('getNow')
			->willReturn(new DateTimeImmutable('2021-04-01T12:00:00+00:00'));

		$hashHelper = new Helpers\SecurityHash($dateFactory);

		self::assertFalse($hashHelper->isValid($hash));

		$dateFactory = $this->createMock(DateTimeFactory\Factory::class);
		$dateFactory
			->method('getNow')
			->willReturn(new DateTimeImmutable('2020-04-01T12:59:00+00:00'));

		$hashHelper = new Helpers\SecurityHash($dateFactory);

		self::assertTrue($hashHelper->isValid($hash));

		$dateFactory = $this->createMock(DateTimeFactory\Factory::class);
		$dateFactory
			->method('getNow')
			->willReturn(new DateTimeImmutable('2020-04-01T13:01:00+00:00'));

		$hashHelper = new Helpers\SecurityHash($dateFactory);

		self::assertFalse($hashHelper->isValid($hash));
	}

}
