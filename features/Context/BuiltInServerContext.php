<?php

namespace Ice\Features\Context;

use Behat\Behat\Context\BehatContext;
use Behat\Behat\Event\SuiteEvent;

class BuiltInServerContext extends BehatContext
{
    /**
     * Pid (unix) or unique window title (windows) for the web server
     *
     * @var mixed
     */
    private static $processIdentifier;

    /**
     * Start up the web server
     *
     * @BeforeSuite
     */
    public static function setUp(SuiteEvent $event) {
        // Fetch config
        $params = $event->getContextParameters();
        $url = parse_url($params['url']);
        $port = !empty($url['port']) ? $url['port'] : 80;

        if (self::canConnectToHttpd($url['host'], $port)) {
            throw new \RuntimeException('Something is already running on ' . $params['url'] . '. Aborting tests.');
        }

        // Try to start the web server
        self::$processIdentifier = self::startBuiltInHttpd(
            $url['host'],
            $port,
            $params['documentRoot']
        );

        if (!self::$processIdentifier) {
            throw new \RuntimeException('Could not start the web server');
        }

        $start = microtime(true);
        $connected = false;

        // Try to connect until the time spent exceeds the timeout specified in the configuration
        while (microtime(true) - $start <= (int) $params['timeout']) {
            if (self::canConnectToHttpd($url['host'], $port)) {
                $connected = true;
                break;
            }
        }

        if (!$connected) {
            self::killProcess(self::$processIdentifier);
            throw new \RuntimeException(
                sprintf(
                    'Could not connect to the web server within the given timeframe (%d second(s))',
                    $params['timeout']
                )
            );
        }
    }

    /**
     * Kill the httpd process if it has been started when the tests have finished
     *
     * @AfterSuite
     */
    public static function tearDown(SuiteEvent $event) {
        if (self::$processIdentifier) {
            self::killProcess(self::$processIdentifier);
        }
    }

    /**
     * Kill a process
     *
     * @param mixed $processIdentifier
     */
    private static function killProcess($processIdentifier) {
        if (self::isWindows()) {
            exec(sprintf('taskkill /FI "WINDOWTITLE eq %s"', $processIdentifier));
        } else {
            exec('kill ' . (int) $processIdentifier);
        }
    }

    /**
     * See if we can connect to the httpd
     *
     * @param string $host The hostname to connect to
     * @param int $port The port to use
     * @return boolean
     */
    private static function canConnectToHttpd($host, $port) {
        // Disable error handler for now
        set_error_handler(function() { return true; });

        // Try to open a connection
        $sp = fsockopen($host, $port);

        // Restore the handler
        restore_error_handler();

        if ($sp === false) {
            return false;
        }

        fclose($sp);

        return true;
    }

    /**
     * Start the built in httpd
     *
     * @param string $host The hostname to use
     * @param int $port The port to use
     * @param string $documentRoot The document root
     * @return int Returns the PID of the httpd
     */
    private static function startBuiltInHttpd($host, $port, $documentRoot) {
        if (self::isWindows()) {
            $windowTitle = uniqid();
            $command = sprintf('start "%s" php -S %s:%d -t %s',
                $windowTitle,
                $host,
                $port,
                $documentRoot
            );

            pclose(popen($command, "r"));

            return $windowTitle;
        } else {
            $command = sprintf('%s -S %s:%d -t %s >/dev/null 2>&1 & echo $!',
                PHP_BINARY,
                $host,
                $port,
                $documentRoot);

            $output = array();
            exec($command, $output);

            return (int) $output[0];
        }
    }

    private static function isWindows()
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }
}
