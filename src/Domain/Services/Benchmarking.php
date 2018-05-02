<?php

namespace Blastware\EspressoDevTools\Domain\Services;


use EE_Error;
use EEH_URL;
use EventEspresso\core\services\Benchmark;


/**
 * Class Benchmarking
 * Description
 *
 * @package Blastware\EspressoDevTools\Domain\Services
 * @author  Brent Christensen
 * @since   $VID:$
 */
class Benchmarking
{

    const OPTION_NAME = 'ee-dev-tools-benchmarking';

    /**
     * @var string $benchmark
     */
    protected $benchmark;

    /**
     * @var bool $log
     */
    protected $log = false;

    /**
     * @var string $log_file
     */
    protected $log_file;

    /**
     * @var string $start_hook
     */
    private $start_hook;

    /**
     * @var string $stop_hook
     */
    private $stop_hook;

    /**
     * Benchmarking constructor.
     */
    public function __construct()
    {
        $this->parseOptions();
        $this->benchmark();
    }

    /**
     * @since $VID:$
     */
    private function parseOptions()
    {
        $options = get_option(self::OPTION_NAME, array());
        $this->start_hook = isset($options['start'])
            ? $options['start']
            : '';
        $this->stop_hook = isset($options['stop'])
            ? $options['stop']
            : 'shutdown';
        $this->benchmark = isset($options['benchmark'])
            ? $options['benchmark']
            : '';
        $this->log = isset($options['log'])
            ? filter_var($options['log'], FILTER_VALIDATE_BOOLEAN)
            : false;
    }

    /**
     * @since $VID:$
     */
    private function benchmark()
    {
        if (!$this->start_hook) {
            return;
        }
        if ($this->log) {
            $this->log_file = EVENT_ESPRESSO_UPLOAD_DIR . 'logs/EspressoDevTools-Benchmarking.html';
            Benchmark::writeResultsAtShutdown($this->log_file);
            $log_file = $this->log_file;
            add_action(
                'shutdown',
                function () use ($log_file) {
                    if(isset($_REQUEST['ee-dev-benchmarking']) && $_REQUEST['ee-dev-benchmarking'] === 'delete-log'){
                        return;
                    }
                    $log_file = str_replace(EVENT_ESPRESSO_UPLOAD_DIR, EVENT_ESPRESSO_UPLOAD_URL, $log_file);
                    $log_file = '<a href="' . $log_file . '">view benchmarking log</a>';
                    echo '<div style="border:1px solid #dddddd; background-color:#ffffff;'
                         . (is_admin()
                            ? ' margin:2em 2em 2em 180px;'
                            : ' margin:2em;')
                         . ' padding:2em;">'
                         . '<h4>BENCHMARKING</h4>'
                         . '<p>' . $log_file . '</p>'
                         . '</div>';
                }
            );
        } else {
            Benchmark::displayResultsAtShutdown();
        }
        add_action($this->start_hook, array($this, 'startTimer'), 0);
        add_action($this->stop_hook, array($this, 'stopTimer'), 99999);
    }

    /**
     * @since $VID:$
     */
    public function startTimer()
    {
        Benchmark::startTimer(
            "Espresso Dev Tools Benchmarking {$this->benchmark} : " . wp_sanitize_redirect($_SERVER['REQUEST_URI'])
        );
    }

    /**
     * @since $VID:$
     */
    public function stopTimer()
    {
        Benchmark::stopTimer(
            "Espresso Dev Tools Benchmarking {$this->benchmark} : " . wp_sanitize_redirect($_SERVER['REQUEST_URI'])
        );
    }

    /**
     * @since $VID:$
     */
    public function setBenchmarkOptions()
    {
        $benchmarking = sanitize_text_field($_REQUEST['ee-dev-benchmarking']);
        $message = 'benchmarking activated - please scroll down to see results';
        $log = $this->log;
        $redirect = true;
        switch ($benchmarking) {
            case 'bootstrapping':
                $benchmark = 'Bootstrapping Only: plugins_loaded to EE_System::__construct()';
                $start = 'plugins_loaded';
                $stop = 'AHEE__EE_System__construct__begin';
                break;
            case 'initialization':
                $benchmark = 'Initialization Only: EE_System::__construct() to EE_System::initialize()';
                $start = 'AHEE__EE_System__construct__begin';
                $stop = 'AHEE__EE_System__initialize';
                break;
            case 'application':
                $benchmark = 'Application Only: EE_System::initialize() to shutdown';
                $start = 'AHEE__EE_System__initialize';
                $stop = 'shutdown';
                break;
            case 'full-request':
                $benchmark = 'Full Request: plugins_loaded to shutdown';
                $start = 'plugins_loaded';
                $stop = 'shutdown';
                break;
            case 'log':
                $benchmark = $this->benchmark;
                $start = $this->start_hook;
                $stop = $this->stop_hook;
                $log = true;
                if ($benchmark === '') {
                    $benchmark = 'Full Request: plugins_loaded to shutdown';
                    $start = 'plugins_loaded';
                    $stop = 'shutdown';
                }
                break;
            case 'delete-log':
                $benchmark = '';
                $start = '';
                $stop = 'shutdown';
                $log = false;
                $log_file = $this->log_file;
                $message = '';
                $redirect = false;
                add_action(
                    'shutdown',
                    function () use ($log_file) {
                        if(file_exists($log_file)) {
                            if (unlink($log_file)) {
                                EE_Error::add_success('benchmarking log successfully deleted');
                            } else {
                                EE_Error::add_error(
                                    'benchmarking log could not be deleted',
                                    __FILE__, __FUNCTION__, __LINE__
                                );
                            }
                        }
                        EEH_URL::safeRedirectAndExit(
                            EEH_URL::current_url_without_query_paramaters(
                                array('ee-dev-action', 'ee-dev-benchmarking')
                            )
                        );
                    },
                    999998
                );
                break;
            case 'off':
            default:
                $benchmark = '';
                $start = '';
                $stop = 'shutdown';
                $log = false;
                $message = 'benchmarking deactivated';
        }
        $updated = update_option(
            self::OPTION_NAME,
            array(
                'benchmark' => $benchmark,
                'start'     => $start,
                'stop'      => $stop,
                'log'       => $log,
            )
        );
        if ($redirect) {
            if ($updated) {
                EE_Error::add_success($message);
            }
            EEH_URL::safeRedirectAndExit(
                EEH_URL::current_url_without_query_paramaters(
                    array('ee-dev-action', 'ee-dev-benchmarking')
                )
            );
        }
        exit();
    }
}
