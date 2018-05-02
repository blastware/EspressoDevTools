<?php

namespace Blastware\EspressoDevTools\Domain\Services;

use EEH_URL;
use WP_Admin_Bar;

defined('EVENT_ESPRESSO_VERSION') || exit;


/**
 * Class Toolbar
 * Description
 *
 * @package Blastware\EspressoDevTools\Domain\Services
 * @author  Brent Christensen
 * @since   $VID:$
 */
class Toolbar
{

    /**
     * Toolbar constructor.
     */
    public function __construct()
    {
        add_action('admin_bar_menu', array($this, 'espressoToolbarItems'), 999);
        add_action('wp_enqueue_scripts', array($this, 'enqueueToolbarStyle'), 999);
        add_action('admin_enqueue_scripts', array($this, 'enqueueToolbarStyle'), 999);
    }


    /**
     * @return void
     */
    public function enqueueToolbarStyle()
    {
        wp_register_style(
            'EspressoDevTools',
            EE_DEV_TOOLS_BASE_URL . '/src/assets/espresso-dev-tools.css',
            array('dashicons'),
            EspressoDevTools::VERSION
        );
        wp_enqueue_style('EspressoDevTools');
    }


    /**
     * @param WP_Admin_Bar $admin_bar
     * @return void
     */
    public function espressoToolbarItems(WP_Admin_Bar $admin_bar)
    {
        $admin_bar->add_menu(
            array(
                'id'     => 'ee-dev-Toolbar-EspressoDevTools',
                'parent' => 'espresso-toolbar',
                'title'  => '<span class="ee-toolbar-icon"></span> ' . esc_html__(
                        'Developer Tools',
                        'event_espresso'
                    ),
                'href'   => '',
            )
        );
        $admin_bar->add_menu(
            array(
                'id'     => 'ee-dev-Toolbar-SampleDataGenerator',
                'parent' => 'espresso-toolbar',
                'title'  => '<span class="ee-toolbar-icon"></span>'
                            . esc_html__('Sample Data Generator', 'event_espresso'),
                'href'   => '#',
                'meta'   => array(
                    'title'  => esc_html__('Sample Data Generator', 'event_espresso'),
                    'target' => '',
                    'class'  => 'espresso_menu_item_class',
                ),
            )
        );
        $admin_bar->add_menu(
            array(
                'id'     => 'ee-dev-Toolbar-SampleDataGenerator-AddSampleEventData',
                'parent' => 'ee-dev-Toolbar-SampleDataGenerator',
                'title'  => '<span class="ee-toolbar-icon"></span>'
                            . esc_html__('Add Sample Event Data', 'event_espresso'),
                'href'   => add_query_arg(
                    array(
                        'ee-dev-action'    => 'sample-data-generator',
                        'ee-dev-data-type' => 'event',
                    ),
                    home_url('/events/')
                ),
                'meta'   => array(
                    'title'  => esc_html__('Add Sample Event Data', 'event_espresso'),
                    'target' => '',
                    'class'  => 'espresso_menu_item_class',
                ),
            )
        );
        $admin_bar->add_menu(
            array(
                'id'     => 'ee-dev-Toolbar-SampleDataGenerator-AddSampleCartData',
                'parent' => 'ee-dev-Toolbar-SampleDataGenerator',
                'title'  => '<span class="ee-toolbar-icon"></span>'
                            . esc_html__('Add Sample Cart Data', 'event_espresso'),
                'href'   => add_query_arg(
                    array(
                        'ee-dev-action'    => 'sample-data-generator',
                        'ee-dev-data-type' => 'cart',
                    ),
                    home_url('/events/')
                ),
                'meta'   => array(
                    'title'  => esc_html__('Add Sample Cart Data', 'event_espresso'),
                    'target' => '',
                    'class'  => 'espresso_menu_item_class',
                ),
            )
        );
        $admin_bar->add_menu(
            array(
                'id'     => 'ee-dev-Toolbar-DatabaseRollBackPoint',
                'parent' => 'espresso-toolbar',
                'title'  => '<span class="ee-toolbar-icon"></span>'
                            . esc_html__('Database RollBack Point', 'event_espresso'),
                'href'   => '#',
                'meta'   => array(
                    'title'  => esc_html__('Database RollBack Point', 'event_espresso'),
                    'target' => '',
                    'class'  => 'espresso_menu_item_class',
                ),
            )
        );
        $admin_bar->add_menu(
            array(
                'id'     => 'ee-dev-Toolbar-DatabaseRollBackPoint-SetRollBackPoint',
                'parent' => 'ee-dev-Toolbar-DatabaseRollBackPoint',
                'title'  => '<span class="ee-toolbar-icon"></span>'
                            . esc_html__('Set Database RollBack Point', 'event_espresso'),
                'href'   => add_query_arg(
                    array(
                        'ee-dev-action'            => 'database-roll-back-point',
                        'database-roll-back-point' => 'set',
                    ),
                    EEH_URl::current_url()
                ),
                'meta'   => array(
                    'title'  => esc_html__('Set Database RollBack Point', 'event_espresso'),
                    'target' => '',
                    'class'  => 'espresso_menu_item_class',
                ),
            )
        );
        $admin_bar->add_menu(
            array(
                'id'     => 'ee-dev-Toolbar-DatabaseRollBackPoint-RestoreRollBackPoint',
                'parent' => 'ee-dev-Toolbar-DatabaseRollBackPoint',
                'title'  => '<span class="ee-toolbar-icon"></span>'
                            . esc_html__('Restore to last RollBack Point', 'event_espresso'),
                'href'   => add_query_arg(
                    array(
                        'ee-dev-action'            => 'database-roll-back-point',
                        'database-roll-back-point' => 'restore',
                    ),
                    EEH_URl::current_url()
                ),
                'meta'   => array(
                    'title'  => esc_html__('Restore to Last RollBack Point', 'event_espresso'),
                    'target' => '',
                    'class'  => 'espresso_menu_item_class',
                ),
            )
        );
        $admin_bar->add_menu(
            array(
                'id'     => 'ee-dev-Toolbar-Benchmarking',
                'parent' => 'espresso-toolbar',
                'title'  => '<span class="ee-toolbar-icon"></span>'
                            . esc_html__('Benchmarking', 'event_espresso'),
                'href'   => '#',
                'meta'   => array(
                    'title'  => esc_html__('Benchmarking', 'event_espresso'),
                    'target' => '',
                    'class'  => 'espresso_menu_item_class',
                ),
            )
        );
        $admin_bar->add_menu(
            array(
                'id'     => 'ee-dev-Toolbar-Benchmarking-Bootstrapping',
                'parent' => 'ee-dev-Toolbar-Benchmarking',
                'title'  => '<span class="ee-toolbar-icon"></span>'
                            . esc_html__('Benchmark Bootstrapping Only', 'event_espresso'),
                'href'   => add_query_arg(
                    array(
                        'ee-dev-action'       => 'benchmark',
                        'ee-dev-benchmarking' => 'bootstrapping',
                    ),
                    EEH_URl::current_url()
                ),
                'meta'   => array(
                    'title'  => esc_html__('Benchmark Bootstrapping', 'event_espresso'),
                    'target' => '',
                    'class'  => 'espresso_menu_item_class',
                ),
            )
        );
        $admin_bar->add_menu(
            array(
                'id'     => 'ee-dev-Toolbar-Benchmarking-Initialization',
                'parent' => 'ee-dev-Toolbar-Benchmarking',
                'title'  => '<span class="ee-toolbar-icon"></span>'
                            . esc_html__('Benchmark Initialization Only', 'event_espresso'),
                'href'   => add_query_arg(
                    array(
                        'ee-dev-action'       => 'benchmark',
                        'ee-dev-benchmarking' => 'initialization',
                    ),
                    EEH_URl::current_url()
                ),
                'meta'   => array(
                    'title'  => esc_html__('Benchmark Initialization', 'event_espresso'),
                    'target' => '',
                    'class'  => 'espresso_menu_item_class',
                ),
            )
        );
        $admin_bar->add_menu(
            array(
                'id'     => 'ee-dev-Toolbar-Benchmarking-Application',
                'parent' => 'ee-dev-Toolbar-Benchmarking',
                'title'  => '<span class="ee-toolbar-icon"></span>'
                            . esc_html__('Benchmark Application Only', 'event_espresso'),
                'href'   => add_query_arg(
                    array(
                        'ee-dev-action'       => 'benchmark',
                        'ee-dev-benchmarking' => 'application',
                    ),
                    EEH_URl::current_url()
                ),
                'meta'   => array(
                    'title'  => esc_html__('Benchmark Application', 'event_espresso'),
                    'target' => '',
                    'class'  => 'espresso_menu_item_class',
                ),
            )
        );
        $admin_bar->add_menu(
            array(
                'id'     => 'ee-dev-Toolbar-Benchmarking-Full-Request',
                'parent' => 'ee-dev-Toolbar-Benchmarking',
                'title'  => '<span class="ee-toolbar-icon"></span>'
                            . esc_html__('Benchmark Full Request', 'event_espresso'),
                'href'   => add_query_arg(
                    array(
                        'ee-dev-action'       => 'benchmark',
                        'ee-dev-benchmarking' => 'full-request',
                    ),
                    EEH_URl::current_url()
                ),
                'meta'   => array(
                    'title'  => esc_html__('Benchmark Full Request', 'event_espresso'),
                    'target' => '',
                    'class'  => 'espresso_menu_item_class',
                ),
            )
        );
        $admin_bar->add_menu(
            array(
                'id'     => 'ee-dev-Toolbar-Benchmarking-Log',
                'parent' => 'ee-dev-Toolbar-Benchmarking',
                'title'  => '<span class="ee-toolbar-icon"></span>'
                            . esc_html__('Write Benchmark Results to Log', 'event_espresso'),
                'href'   => add_query_arg(
                    array(
                        'ee-dev-action'       => 'benchmark',
                        'ee-dev-benchmarking' => 'log',
                    ),
                    EEH_URl::current_url()
                ),
                'meta'   => array(
                    'title'  => esc_html__('Write Benchmark Results to Log', 'event_espresso'),
                    'target' => '',
                    'class'  => 'espresso_menu_item_class',
                ),
            )
        );
        $admin_bar->add_menu(
            array(
                'id'     => 'ee-dev-Toolbar-Benchmarking-Delete-Log',
                'parent' => 'ee-dev-Toolbar-Benchmarking',
                'title'  => '<span class="ee-toolbar-icon"></span>'
                            . esc_html__('Delete Benchmarking Log', 'event_espresso'),
                'href'   => add_query_arg(
                    array(
                        'ee-dev-action'       => 'benchmark',
                        'ee-dev-benchmarking' => 'delete-log',
                    ),
                    EEH_URl::current_url()
                ),
                'meta'   => array(
                    'title'  => esc_html__('Delete Benchmarking Log', 'event_espresso'),
                    'target' => '',
                    'class'  => 'espresso_menu_item_class',
                ),
            )
        );
        $admin_bar->add_menu(
            array(
                'id'     => 'ee-dev-Toolbar-Benchmarking-Off',
                'parent' => 'ee-dev-Toolbar-Benchmarking',
                'title'  => '<span class="ee-toolbar-icon"></span>'
                            . esc_html__('Benchmarking Off', 'event_espresso'),
                'href'   => add_query_arg(
                    array(
                        'ee-dev-action'       => 'benchmark',
                        'ee-dev-benchmarking' => 'off',
                    ),
                    EEH_URl::current_url()
                ),
                'meta'   => array(
                    'title'  => esc_html__('Benchmarking Off', 'event_espresso'),
                    'target' => '',
                    'class'  => 'espresso_menu_item_class',
                ),
            )
        );

    }
}
