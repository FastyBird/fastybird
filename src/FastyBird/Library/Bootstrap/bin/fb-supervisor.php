<?php
/**
 * supervisor.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Bootstrap!
 * @subpackage     bin
 * @since          0.1.0
 *
 * @date           08.03.20
 */

declare(strict_types = 1);

$stdIn = STDIN;
$stdOut = STDOUT;

fwrite($stdOut, "READY\n");

while (true) {
	if (($line = trim(strval(fgets($stdIn)))) === false) {
		continue;
	}

	$match = null;

	if (preg_match('/eventname:(.*?) /', $line, $match)) {
		if (in_array($match[1], ['PROCESS_STATE_EXITED', 'PROCESS_STATE_STOPPED', 'PROCESS_STATE_FATAL'])) {
			exec('kill -15 ' . file_get_contents('/run/supervisord.pid'));
		}
	}

	fwrite($stdOut, "RESULT 2\nOK");

	sleep(1);

	fwrite($stdOut, "READY\n");
}
