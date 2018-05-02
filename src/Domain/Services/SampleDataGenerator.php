<?php

namespace Blastware\EspressoDevTools\Domain\Services;

use DomainException;
use EE_Dependency_Map;
use EE_Error;
use EEH_URL;
use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidInterfaceException;
use EventEspresso\core\services\loaders\LoaderFactory;
use InvalidArgumentException;
use ReflectionException;

/**
 * Class SampleDataGenerator
 * Generates Sample Data for Event Espresso
 *
 * @package aBrCa\SampleDataGenerator
 * @author  Brent R Christensen
 * @since   $VID:$
 */
class SampleDataGenerator
{

    /**
     * @var EventDataGenerator $event_data_generator
     */
    protected $event_data_generator;

    /**
     * @var CartDataGenerator $cart_data_generator
     */
    protected $cart_data_generator;


    /**
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     */
    public static function load()
    {
        EE_Dependency_Map::register_dependencies(
            'Blastware\EspressoDevTools\Domain\Services\SampleDataGenerator',
            array(
                'Blastware\EspressoDevTools\Domain\Services\CartDataGenerator'  => EE_Dependency_Map::load_from_cache,
                'Blastware\EspressoDevTools\Domain\Services\EventDataGenerator' => EE_Dependency_Map::load_from_cache,
            )
        );
        EE_Dependency_Map::register_dependencies(
            'Blastware\EspressoDevTools\Domain\Services\CartDataGenerator',
            array(
                'EE_Cart'                                                  => EE_Dependency_Map::load_from_cache,
                'Blastware\EspressoDevTools\Domain\Services\DataTracker'    => EE_Dependency_Map::load_from_cache,
                'EventEspresso\core\domain\values\session\SessionLifespan' => EE_Dependency_Map::load_from_cache,
            )
        );
        EE_Dependency_Map::register_dependencies(
            'Blastware\EspressoDevTools\Domain\Services\EventDataGenerator',
            array(
                'Blastware\EspressoDevTools\Domain\Services\DataTracker' => EE_Dependency_Map::load_from_cache,
            )
        );
        LoaderFactory::getLoader()->getShared('Blastware\EspressoDevTools\Domain\Services\SampleDataGenerator');
    }


    /**
     * SampleDataGenerator constructor.
     *
     * @param CartDataGenerator $cart_data_generator
     * @param EventDataGenerator $event_data_generator
     */
    public function __construct(CartDataGenerator $cart_data_generator, EventDataGenerator $event_data_generator)
    {
        $this->cart_data_generator  = $cart_data_generator;
        $this->event_data_generator = $event_data_generator;
        add_action('AHEE__EE_System__initialize_last', array($this, 'addSampleData'));
    }


    /**
     * @throws DomainException
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws ReflectionException
     */
    public function addSampleData()
    {
        $add_sample_data = sanitize_text_field($_REQUEST['ee-dev-data-type']);
        switch ($add_sample_data) {
            case 'event' :
                $this->event_data_generator->addSampleEventData();
                break;
            case 'cart' :
                $this->cart_data_generator->addSampleCartData();
                break;
        }
        EE_Error::stashNoticesBeforeRedirect();
        EEH_URL::safeRedirectAndExit(home_url('/events/'));
    }


}
