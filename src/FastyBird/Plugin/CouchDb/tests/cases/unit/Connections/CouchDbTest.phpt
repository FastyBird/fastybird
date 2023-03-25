<?php declare(strict_types = 1);

namespace Tests\Cases;

use FastyBird\CouchDbPlugin\Connections;
use Mockery;
use Ninjify\Nunjuck\TestCase\BaseMockeryTestCase;
use Psr\Log;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';

/**
 * @testCase
 */
final class CouchDbTest extends BaseMockeryTestCase
{

	public function testDefaultValues(): void
	{
		$log = Mockery::mock(Log\LoggerInterface::class);

		$config = new Connections\CouchDbConnection('db.name', '127.0.0.1', 5984, null, null, $log);

		Assert::same('127.0.0.1', $config->getHost());
		Assert::same(5984, $config->getPort());
		Assert::null($config->getUsername());
		Assert::null($config->getPassword());
		Assert::same('db.name', $config->getDatabase());
	}

}

$test_case = new CouchDbTest();
$test_case->run();
