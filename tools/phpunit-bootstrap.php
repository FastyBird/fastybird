<?php declare(strict_types = 1);

// phpcs:ignoreFile

define('FB_APP_DIR', realpath(__DIR__ . '/..'));
define('FB_CONFIG_DIR', realpath(__DIR__ . '/../config'));
define('FB_VENDOR_DIR', realpath(__DIR__ . '/../vendor'));
is_string(getenv('TEST_TOKEN'))
	? define('FB_TEMP_DIR', __DIR__ . '/../var/tools/PHPUnit/tmp/' . getmypid() . '-' . getenv('TEST_TOKEN') ?? '')
	: define('FB_TEMP_DIR', __DIR__ . '/../var/tools/PHPUnit/tmp/' . getmypid());
is_string(getenv('TEST_TOKEN'))
	? define('FB_LOGS_DIR', __DIR__ . '/../var/tools/PHPUnit/logs/' . getmypid() . '-' . getenv('TEST_TOKEN') ?? '')
	: define('FB_LOGS_DIR', __DIR__ . '/../var/tools/PHPUnit/logs/' . getmypid());

if (@!include __DIR__ . '/../vendor/autoload.php') {
	echo 'Install Tester using `composer update --dev`';
	exit(1);
}

DG\BypassFinals::enable();
