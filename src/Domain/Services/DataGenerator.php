<?php

namespace Blastware\EspressoDevTools\Domain\Services;

use DomainException;
use EE_Base_Class;
use EE_Belongs_To_Any_Relation;
use EE_Belongs_To_Relation;
use EE_Boolean_Field;
use EE_Email_Field;
use EE_Enum_Integer_Field;
use EE_Enum_Text_Field;
use EE_Error;
use EE_Float_Field;
use EE_Foreign_Key_Field_Base;
use EE_Integer_Field;
use EE_Primary_Key_String_Field;
use EE_Registry;
use EE_Text_Field_Base;
use EEM_WP_User;
use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidInterfaceException;
use InvalidArgumentException;
use ReflectionException;
use WP_User;




/**
 * Class DataGenerator
 * Description
 *
 * @package Blastware\EspressoDevTools\Domain\Services
 * @author  Brent Christensen
 * @since   $VID:$
 */
abstract class DataGenerator
{

    /**
     * @var DataTracker $data_tracker
     */
    private $data_tracker;


    /**
     * DataGenerator constructor.
     *
     * @param DataTracker $data_tracker
     */
    public function __construct(DataTracker $data_tracker)
    {
        $this->data_tracker = $data_tracker;
    }


    /**
     * @return WP_User
     */
    protected function currentUser()
    {
        return $this->data_tracker->currentUser();
    }


    /**
     * @return int
     */
    protected function currentUserID()
    {
        return $this->data_tracker->currentUserID();
    }


    /**
     * @return string
     */
    protected function dateTimeFormat()
    {
        return $this->data_tracker->getDateTimeFormat();
    }


    /**
     * @param string $model_name
     * @param mixed  $ID
     * @return EE_Base_Class|null
     */
    public function getObject($model_name, $ID)
    {
        return $this->data_tracker->getObject($model_name, $ID);
    }


    /**
     * @param string $model_name
     * @return EE_Base_Class
     */
    public function getAnyObject($model_name)
    {
        return $this->data_tracker->getAnyObject($model_name);
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
        $this->data_tracker->populateObjects($model_name);
    }


    /**
     * @param string $model_name
     * @return int
     */
    protected function getObjectSeed($model_name)
    {
        return $this->data_tracker->getObjectSeed($model_name);
    }


    /**
     * @param string $model_name
     */
    protected function bumpObjectSeed($model_name)
    {
        $this->data_tracker->bumpObjectSeed($model_name);
    }


    /**
     * @param string $data
     */
    protected function log($data)
    {
        $this->data_tracker->log($data);
    }


    /**
     * @param int $start
     * @param int $end
     * @return float|int
     */
    protected function getStartDate($start = -3, $end = 15)
    {
        // date is somewhere between 6 months ago and a year from now
        $date = time() + mt_rand($start, $end) * MONTH_IN_SECONDS;
        // round value down to number of days to remove odd time values
        $date = floor($date / DAY_IN_SECONDS);
        // convert back to seconds
        $date *= DAY_IN_SECONDS;
        // add 8 to 20 hours for time portion
        $hours = array(8, 12, 16, 20);
        $date  += $hours[ array_rand($hours) ] * HOUR_IN_SECONDS;
        return $date;
    }


    /**
     * @param float|int $date
     * @param int       $start
     * @param int       $end
     * @return float|int
     */
    protected function getEndDate($date, $start = 1, $end = 7)
    {
        // add 0 to 7 days
        $date += mt_rand($start, $end) * DAY_IN_SECONDS;
        // round value down to number of days to remove odd time values
        $date = floor($date / DAY_IN_SECONDS);
        // convert back to seconds
        $date *= DAY_IN_SECONDS;
        // add 8 to 20 hours
        $hours = array(8, 12, 16, 20);
        $date  += $hours[ array_rand($hours) ] * HOUR_IN_SECONDS;
        return $date;
    }


    /**
     * Creates a model object and its required dependencies
     * basically a copy of EE_UnitTestCase::new_model_obj_with_dependencies()
     * which was created by Michael Nelson
     *
     * @param string  $model_name
     * @param array   $args array of arguments to supply when constructing the model object
     * @param boolean $save
     * @throws InvalidInterfaceException
     * @throws ReflectionException
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws EE_Error
     * @throws DomainException
     * @return EE_Base_Class
     */
    protected function createObject($model_name, array $args = array(), $save = true)
    {
        $object_seed = $this->getObjectSeed($model_name);
        $model       = EE_Registry::instance()->load_model($model_name);
        //set the related model foreign keys
        foreach ($model->relation_settings() as $related_model_name => $relation) {
            if ($relation instanceof EE_Belongs_To_Any_Relation || $related_model_name === 'Price_Type') {
                continue;
            }
            if ($related_model_name === 'WP_User' && $this->data_tracker->currentUserID()) {
                $fk = $model->get_foreign_key_to('WP_User');
                if (! isset($args[ $fk->get_name() ])) {
                    $obj                     = EEM_WP_User::instance()
                                                          ->get_one_by_ID($this->data_tracker->currentUserID());
                    $args[ $fk->get_name() ] = $obj->ID();
                }
            } elseif ($related_model_name === 'Country' && ! isset($args['CNT_ISO'])) {
                //we already have lots of countries. lets not make any more
                //what's more making them is tricky: the primary key needs to be a unique
                //2-character string but not an integer (else it confuses the country
                //form input validation)
                $args['CNT_ISO'] = 'US';
            } elseif ($related_model_name === 'Status') {
                $fk = $model->get_foreign_key_to($related_model_name);
                if (! isset($args[ $fk->get_name() ])) {
                    //only set the default if they haven't specified anything
                    $args[ $fk->get_name() ] = $fk->get_default_value();
                }
            } elseif ($relation instanceof EE_Belongs_To_Relation) {
                $fk = $model->get_foreign_key_to($related_model_name);
                if (! isset($args[ $fk->get_name() ])) {
                    $obj                     = $this->createObject($related_model_name);
                    $args[ $fk->get_name() ] = $obj->ID();
                }
            }
        }
        //set any other fields which haven't yet been set
        foreach ($model->field_settings() as $field_name => $field) {
            $value = null;
            if (
            in_array(
                $field_name,
                array(
                    'EVT_timezone_string',
                    'PAY_redirect_url',
                    'PAY_redirect_args',
                    'TKT_reserved',
                    'DTT_reserved',
                    'parent',
                    //don't make system questions etc
                    'QST_system',
                    'QSG_system',
                    'QSO_system',
                ),
                true
            )
            ) {
                $value = null;
            } elseif (
                $field_name === 'TKT_start_date'
                || $field_name === 'DTT_EVT_start'
            ) {
                $value = time() + MONTH_IN_SECONDS;
            } elseif (
                $field_name === 'TKT_end_date'
                || $field_name === 'DTT_EVT_end'
            ) {
                $value = time() + MONTH_IN_SECONDS + DAY_IN_SECONDS;
            } elseif (
                $field instanceof EE_Enum_Integer_Field
                || $field instanceof EE_Enum_Text_Field
                || $field instanceof EE_Boolean_Field
                || $field_name === 'PMD_type'
                || $field_name === 'CNT_cur_dec_mrk'
                || $field_name === 'CNT_cur_thsnds'
                || $field_name === 'CNT_tel_code'
            ) {
                $value = $field->get_default_value();
            } elseif (
                $field instanceof EE_Integer_Field
                || $field instanceof EE_Float_Field
                || $field instanceof EE_Foreign_Key_Field_Base
                || $field_name === 'STA_abbrev'
                || $field_name === 'CNT_ISO3'
                || $field_name === 'CNT_cur_code'
            ) {
                $value = $object_seed;
            } elseif ($field instanceof EE_Primary_Key_String_Field) {
                $value = (string) $object_seed;
            } elseif ($field instanceof EE_Email_Field) {
                $value = "email{$object_seed}@" . site_url('', 'relative');
            } elseif ($field instanceof EE_Text_Field_Base) {
                $value = str_replace('_', ' ', "{$model_name} {$object_seed}");
            }
            if (! array_key_exists($field_name, $args) && $value !== null) {
                $args[ $field_name ] = $value;
            }
        }
        //and finally make the model obj
        $classname = 'EE_' . $model_name;
        /** @var EE_Base_Class $model_obj */
        $model_obj = $classname::new_instance($args);
        if ($save) {
            $success = $model_obj->save();
            if (! $success) {
                global $wpdb;
                throw new DomainException(
                    sprintf(
                        __('Could not save %1$s using %2$s. Error was %3$s', 'event_espresso'),
                        $model_name,
                        wp_json_encode($args),
                        $wpdb->last_error
                    )
                );
            }
        }
        $this->bumpObjectSeed($model_name);
        $this->data_tracker->addObject($model_name, $model_obj);
        return $model_obj;
    }


    /**
     * @param int  $words
     * @param bool $capitalize
     * @return string
     */
    protected function randomSentenceGenerator($words = 150, $capitalize = false)
    {
        $sentence = '';
        $length   = mt_rand(ceil($words / 10), $words);
        while ($length > 0) {
            $sentence .= $this->randomPronounceableWord() . ' ';
            $length--;
        }
        return $capitalize
        ? ucwords(trim($sentence))
        : trim($sentence);
    }


    /**
     * Generate random pronounceable words
     * if no good try: https://github.com/gnugat-legacy/PronounceableWord
     *
     * @see http://planetozh.com/blog/2012/10/generate-random-pronouceable-words/
     * @return string Random word
     */
    protected function randomPronounceableWord()
    {
        $length = mt_rand(0, 1)
            ? mt_rand(1, 6)
            : mt_rand(2, 12);
        // consonant sounds
        $cons = array(
            // single consonants. Beware of Q, it's often awkward in words
            'b',
            'c',
            'd',
            'f',
            'g',
            'h',
            'j',
            'k',
            'l',
            'm',
            'n',
            'p',
            'r',
            's',
            't',
            'v',
            'w',
            'x',
            'z',
            // possible combinations excluding those which cannot start a word
            'pt',
            'gl',
            'gr',
            'ch',
            'ph',
            'ps',
            'sh',
            'st',
            'th',
            'wh',
        );
        // consonant combinations that cannot start a word
        $cons_cant_start = array(
            'ck',
            'cm',
            'dr',
            'ds',
            'ft',
            'gh',
            'gn',
            'kr',
            'ks',
            'ls',
            'lt',
            'lr',
            'mp',
            'mt',
            'ms',
            'ng',
            'ns',
            'rd',
            'rg',
            'rs',
            'rt',
            'ss',
            'ts',
            'tch',
        );
        // vowels
        $vows = array(
            // single vowels
            'a',
            'e',
            'i',
            'o',
            'u',
            'y',
            // vowel combinations your language allows
            'ee',
            'oa',
            'oo',
        );
        if ($length === 1) {
            return $vows[ mt_rand(0, count($vows) - 5) ];
        }
        // start by vowel or consonant ?
        $current = mt_rand(0, 1) ? 'cons' : 'vows';
        $word    = '';
        while (strlen($word) < $length) {
            // After first letter, use all consonant combos
            if (strlen($word) === 2) {
                $cons = array_merge($cons, $cons_cant_start);
            }
            // random sign from either $cons or $vows
            $rnd = ${$current}[ mt_rand(0, count(${$current}) - 1) ];
            // check if random sign fits in word length
            if (strlen($word . $rnd) <= $length) {
                $word .= $rnd;
                // alternate sounds
                $current = ($current === 'cons' ? 'vows' : 'cons');
            }
        }
        return $word;
    }
}
