<?php

namespace Blastware\EspressoDevTools\Domain\Services;

use EE_Base_Class;
use EE_Error;
use EE_Registry;
use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidInterfaceException;
use InvalidArgumentException;
use ReflectionException;
use WP_User;




/**
 * Class DataTracker
 * Description
 *
 * @package Blastware\EspressoDevTools\Domain\Services
 * @author  Brent Christensen
 * @since   $VID:$
 */
class DataTracker
{

    const OPTION_KEY_OBJECT_SEEDS = 'aBrCa-sample-data-object-seeds';

    const OPTION_KEY_DATA_GEN_LOG = 'aBrCa-sample-data-generation-log';

    /**
     * @var WP_User $current_user
     */
    protected $current_user;

    /**
     * @var EE_Base_Class[][] $objects
     */
    protected $objects = array();

    /**
     * @var int[][] $object_seeds
     */
    protected $object_seeds = array();

    /**
     * @var string $date_time_format
     */
    protected $date_time_format = 'l F jS';

    /**
     * @var string $data_log
     */
    protected $data_log = '';

    /**
     * @var boolean $is_ajax
     */
    protected $is_ajax;

    /**
     * @var boolean $no_header
     */
    protected $no_header;


    /**
     * SampleDataGenerator constructor.
     */
    public function __construct()
    {
        $this->current_user = wp_get_current_user();
        $this->object_seeds = get_option(DataTracker::OPTION_KEY_OBJECT_SEEDS, array());
        $this->data_log     = get_option(DataTracker::OPTION_KEY_DATA_GEN_LOG, '');
        if ($this->data_log === '') {
            $this->log('<strong>Auto Generated Sample Data</strong><br />');
        }
        $this->is_ajax = defined('DOING_AJAX') && DOING_AJAX;
        $this->no_header = isset($_GET['noheader']);
        add_action('shutdown', array($this, 'saveObjectSeeds'));
    }


    /**
     * @return WP_User
     */
    public function currentUser()
    {
        return $this->current_user;
    }


    /**
     * @return int
     */
    public function currentUserID()
    {
        return $this->current_user->ID;
    }


    /**
     * @return string
     */
    public function getDateTimeFormat()
    {
        return $this->date_time_format;
    }


    /**
     * @return bool
     */
    public function isAjax()
    {
        return $this->is_ajax;
    }


    /**
     * @param string $model_name
     * @param mixed  $ID
     * @return bool
     */
    public function hasObject($model_name, $ID)
    {
        return isset($this->objects[ $model_name ][ $ID ]);
    }


    /**
     * @param string $model_name
     * @param mixed  $ID
     * @return EE_Base_Class|null
     */
    public function getObject($model_name, $ID)
    {
        return $this->hasObject($model_name, $ID)
            ? $this->objects[ $model_name ][ $ID ]
            : null;
    }


    /**
     * @param string $model_name
     * @return EE_Base_Class|null
     */
    public function getAnyObject($model_name)
    {
        if(empty($this->objects[ $model_name ])) {
            return null;
        }
        return $this->objects[ $model_name ][  array_rand($this->objects[ $model_name ]) ];
    }


    /**
     * @param string        $model_name
     * @param EE_Base_Class $object
     * @return void
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws ReflectionException
     */
    public function addObject($model_name, EE_Base_Class $object)
    {
        $this->objects[ $model_name ][ $object->ID() ] = $object;
    }


    /**
     * @param string $model_name
     * @return void
     * @throws InvalidArgumentException
     * @throws InvalidInterfaceException
     * @throws InvalidDataTypeException
     * @throws EE_Error
     * @throws ReflectionException
     */
    public function populateObjects($model_name)
    {
        if (empty($this->objects[ $model_name ])) {
            $model   = EE_Registry::instance()->load_model($model_name);
            $objects = $model->get_all();
            foreach ($objects as $object) {
                $this->addObject($model_name, $object);
            }
        }
    }


    /**
     * @param string $model_name
     * @return int
     */
    public function getObjectSeed($model_name)
    {
        if (! isset($this->object_seeds[ $model_name ])) {
            $this->object_seeds[ $model_name ] = 1;
        }
        return $this->object_seeds[ $model_name ];
    }


    /**
     * @param string $model_name
     */
    public function bumpObjectSeed($model_name)
    {
        $this->object_seeds[ $model_name ]++;
    }


    /**
     *
     */
    public function saveObjectSeeds()
    {
        update_option(DataTracker::OPTION_KEY_OBJECT_SEEDS, $this->object_seeds, false);
        update_option(DataTracker::OPTION_KEY_DATA_GEN_LOG, $this->data_log, false);
        if (! $this->is_ajax && ! $this->no_header) {
            echo '<div style="border:1px solid #ccc; background:#fff; clear: both; margin:2em 1em; padding:2em;">';
            echo $this->data_log;
            echo '</div>';
        }
    }


    /**
     * @param string $data
     */
    public function log($data)
    {
        if ($this->is_ajax) {
            echo $data;
        }
        $this->data_log .= $data;
    }
}
