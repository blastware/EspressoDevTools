<?php

namespace Blastware\EspressoDevTools\Domain\Services;

use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidInterfaceException;
use InvalidArgumentException;

/**
 * Class EspressoDevTools
 * Generates Sample Data for Event Espresso
 *
 * @package Blastware\EspressoDevTools
 * @author  Brent Christensen
 * @since   1.0.0
 */
class EspressoDevTools
{

    const VERSION              = '1.0.0';

    const OPTION_KEY_ACTIVATED = 'EspressoDevTools-Activated';


    /**
     * @var Benchmarking $benchmarking
     */
    protected $benchmarking;


    /**
     * SampleDataGenerator constructor.
     */
    public function __construct()
    {
        register_activation_hook(__FILE__, array($this, 'activation'));
        add_action('AHEE__EE_System__initialize', array($this, 'initialize'));
        add_action(
            'AHEE__EventEspresso_core_services_bootstrap_BootstrapCore___construct',
            array($this, 'loadBenchmarking')
        );
    }


    /**
     * @return void
     */
    public function loadBenchmarking()
    {
        $this->benchmarking = new Benchmarking();
    }


    /**
     * @return void
     */
    public function activation()
    {
        if (get_option(EspressoDevTools::OPTION_KEY_ACTIVATED) === EspressoDevTools::VERSION) {
            return;
        }
        // then update the edt-activated option to make sure this code only runs once
        update_option(EspressoDevTools::OPTION_KEY_ACTIVATED, EspressoDevTools::VERSION);
    }


    /**
     * @return void
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws InvalidArgumentException
     */
    public function initialize()
    {
        if (isset($_REQUEST['ee-dev-action'])) {
            switch ($_REQUEST['ee-dev-action']) {
                case 'benchmark':
                    $this->benchmarking->setBenchmarkOptions();
                    break;
                case 'sample-data-generator':
                    SampleDataGenerator::load();
                    break;
                case 'database-roll-back-point':
                    DatabaseRollBackPoint::load();
                    break;
            }
        }
        add_action('admin_bar_init', array($this, 'initializeToolbar'));
    }


    /**
     * @return void
     */
    public function initializeToolbar()
    {
        if (defined('DOING_AJAX') || ! current_user_can('ee_read_ee')) {
            return;
        }
        new Toolbar();
    }

}

