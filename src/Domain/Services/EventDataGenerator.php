<?php

namespace Blastware\EspressoDevTools\Domain\Services;

use DomainException;
use EE_Datetime;
use EE_Error;
use EE_Event;
use EE_Price;
use EE_Price_Type;
use EE_Question_Group;
use EE_Ticket;
use EEM_Datetime_Ticket;
use EEM_Price;
use EEM_Price_Type;
use EEM_Question_Group;
use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidInterfaceException;
use InvalidArgumentException;
use ReflectionException;

/**
 * Class EventDataGenerator
 * Generates Sample Event Data for Event Espresso
 *
 * @package aBrCa\SampleDataGenerator
 * @author  Brent R Christensen
 * @since   $VID:$
 */
class EventDataGenerator extends DataGenerator
{

    /**
     * @var EE_Price_Type[] $price_types
     */
    protected $price_types;

    /**
     * @var EE_Question_Group[] $question_groups
     */
    protected $question_groups;


    /**
     * EventDataGenerator constructor.
     *
     * @param DataTracker $data_tracker
     * @throws DomainException
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws ReflectionException
     */
    public function __construct(DataTracker $data_tracker)
    {
        parent::__construct($data_tracker);
        $this->setupPriceTypes();
        $this->setupTaxes();
        $this->getQuestionGroups();
    }


    /**
     * @param int $max
     * @throws DomainException
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws ReflectionException
     */
    public function addSampleEventData($max = 10)
    {
        $events = $this->createEvents(mt_rand(1, $max));
        $created = count($events);
        if($created){
            EE_Error::add_success("Generated data for {$created} Events");
        }
    }


    /**
     * @throws DomainException
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     */
    protected function setupPriceTypes()
    {
        $price_types = EEM_Price_Type::instance()->get_all();
        foreach ($price_types as $price_type) {
            if (! $price_type instanceof EE_Price_Type) {
                throw new DomainException('Invalid EE_Price_Type');
            }
            $this->price_types[ $price_type->name() ] = $price_type;
        }
    }


    /**
     * @throws DomainException
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws ReflectionException
     */
    protected function setupTaxes()
    {
        EEM_Price::instance()->delete(array(array('PRC_ID' => 2)));
        $taxes = EEM_Price::instance()->get_all(array(array('PRT_ID' => array('IN', array(6,7)))));
        if(!empty($taxes)){
            return;
        }
        $taxes = array(
            0 => 'Regional Tax',
            1 => 'Federal Tax',
        );
        foreach ($taxes as $tax) {
            if (mt_rand(0, 1)) {
                $this->getPriceModifier($tax);
                EE_Error::add_success("Added {$tax} Price Modifier");
            }
        }
    }


    /**
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     */
    protected function getQuestionGroups()
    {
        $this->question_groups = EEM_Question_Group::instance()->get_all();
    }


    /**
     * @param int $event_count
     * @return EE_Event[]
     * @throws DomainException
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws ReflectionException
     */
    public function createEvents($event_count = 0)
    {
        $events = array();
        for ($x = 0; $x < $event_count; $x++) {
            $events[] = $this->createEvent();
        }
        return $events;
    }


    /**
     * @return EE_Event
     * @throws DomainException
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws ReflectionException
     */
    public function createEvent()
    {
        $name = 'Event ' . $this->getObjectSeed('Event') . ' - ';
        $name .= $this->randomSentenceGenerator(6, true);
        $desc = $this->randomSentenceGenerator();
        /** @type EE_Event $event */
        $event = $this->createObject(
            'Event',
            // EVTM_ID 	EVT_ID 	EVT_display_desc 	EVT_display_ticket_selector 	EVT_visible_on
            // EVT_default_registration_status 	EVT_phone 	EVT_additional_limit 	EVT_member_only
            // EVT_allow_overflow 	EVT_timezone_string 	EVT_external_URL 	EVT_donations
            array(
                'EVT_name'                    => $name,
                'EVT_desc'                    => $desc,
                'EVT_short_desc'              => implode(' ', array_slice(explode(' ', $desc), 0, 10)),
                'EVT_wp_user'                 => $this->currentUserID(),
                'EVT_display_desc'            => 1,
                'EVT_display_ticket_selector' => 1,
                'EVT_visible_on'              => 1,
                'EVT_phone'                   => '',
                'EVT_additional_limit'        => 10,
                'EVT_external_URL'            => '',
                'status'                      => 'publish',
            )
        );
        if (! $event instanceof EE_Event) {
            throw new DomainException('Invalid EE_Event');
        }
        $this->log("<br />creating event {$event->name()}<br />");
        $this->addTicketsToDatetimes(
            $event,
            $this->createTickets(),
            $this->addDatetimesToEvent($event)
        );
        $this->addQuestionGroupsToEvent($event);
        return $event;
    }


    /**
     * @param EE_Event $event
     * @return void
     * @throws EE_Error
     */
    protected function addQuestionGroupsToEvent(EE_Event $event)
    {
        $primary = array();
        $additional = array();
        $others_have_personal = false;
        // add question groups
        foreach ($this->question_groups as $question_group) {
            if($question_group instanceof EE_Question_Group) {
                switch($question_group->system_group()){
                    case EEM_Question_Group::system_personal :
                        // always add personal question group for primary registrant
                        $event->add_question_group($question_group, true);
                        $primary[] = $question_group->name(true);
                        if(mt_rand(0,1)) {
                            // 1 in 2 chance of adding QG
                            $event->add_question_group($question_group);
                            $additional[] = $question_group->name(true);
                            $others_have_personal = true;
                        }
                        break;
                    case EEM_Question_Group::system_address :
                        if(mt_rand(0,1)) {
                            // 1 in 2 chance of adding QG
                            $event->add_question_group($question_group, true);
                            $primary[] = $question_group->name(true);
                        }
                        if($others_have_personal && mt_rand(0,1)) {
                            // 1 in 2 chance of adding QG
                            $event->add_question_group($question_group);
                            $additional[] = $question_group->name(true);
                        }
                        break;
                    default :
                        if(mt_rand(0,3) === 3) {
                            // 1 in 4 chance of adding QG
                            $event->add_question_group($question_group, true);
                            $primary[] = $question_group->name(true);
                        }
                        if($others_have_personal && mt_rand(0,3) === 3) {
                            // 1 in 4 chance of adding QG
                            $event->add_question_group($question_group);
                            $additional[] = $question_group->name(true);
                        }
                        break;
                }
            }
        }
        $this->log('&nbsp; . added the following Question Groups for the Primary Registrant:<br />&nbsp; . . ');
        $this->log(implode(', ', $primary) . '<br />');
        if($additional !== array()) {
            $this->log('&nbsp; . added the following Question Groups for additional Registrants:<br />&nbsp; . . ');
            $this->log(implode(', ', $additional) . '<br />');

        }
    }


    /**
     * @param EE_Event $event
     * @return EE_Datetime[]
     * @throws DomainException
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws ReflectionException
     */
    protected function addDatetimesToEvent(EE_Event $event)
    {
        $datetimes           = array();
        $number_of_datetimes = mt_rand(0, 1)
            ? mt_rand(1, 4)
            : mt_rand(1, 8);
        for ($x = 0; $x < $number_of_datetimes; $x++) {
            $datetimes[] = $this->createDatetime($event, $x);
        }
        return $datetimes;
    }


    /**
     * @param EE_Event $event
     * @param int      $order
     * @return EE_Datetime
     * @throws DomainException
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws ReflectionException
     */
    protected function createDatetime(EE_Event $event, $order)
    {
        $date      = $this->getStartDate();
        $reg_limit = mt_rand(0, 1)
            ? mt_rand(1, 10)
            : ceil(mt_rand(1, 25) / 10) * 10;
        $name      = 'D' . $this->getObjectSeed('Datetime') . ' : ';
        $name      .= date($this->dateTimeFormat(), $date);
        /** @type EE_Datetime $datetime */
        $datetime = $this->createObject(
            'Datetime',
            // EVT_ID 	    DTT_name 	    DTT_description 	DTT_EVT_start 	DTT_EVT_end 	DTT_reg_limit
            // DTT_sold 	DTT_reserved 	DTT_is_primary 	    DTT_order 	    DTT_parent 	    DTT_deleted
            array(
                'EVT_ID'          => $event->ID(),
                'DTT_name'        => $name,
                'DTT_description' => $this->randomSentenceGenerator(6),
                'DTT_EVT_start'   => $date,
                'DTT_EVT_end'     => $this->getEndDate($date),
                'DTT_reg_limit'   => $reg_limit,
                'DTT_sold'        => 0,
                'DTT_reserved'    => 0,
                'DTT_is_primary'  => 0,
                'DTT_parent'      => 0,
                'DTT_deleted'     => 0,
                'DTT_order'       => $order,
            )
        );
        if (! $datetime instanceof EE_Datetime) {
            throw new DomainException('Invalid EE_Datetime');
        }
        $this->log("&nbsp; . creating datetime {$datetime->name()}<br />");
        return $datetime;
    }


    /**
     * @param EE_Event      $event
     * @param EE_Ticket[]   $tickets
     * @param EE_Datetime[] $datetimes
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws ReflectionException
     */
    protected function addTicketsToDatetimes(EE_Event $event, array $tickets, array $datetimes)
    {
        $tickets_count    = count($tickets);
        $datetime_count   = count($datetimes);
        $ticket_relations = array();
        foreach ($datetimes as $datetime) {
            EEM_Datetime_Ticket::instance()->delete(array(array('DTT_ID' => $datetime->ID())));
            $this->log("&nbsp; . adding {$datetime->name()} to {$event->name()}<br />");
            $ticket_indexes = array_keys($tickets);
            $tickets_to_add = mt_rand(0, 1)
                ? mt_rand(1, $tickets_count)
                : 1;
            $tickets_to_add = $datetime_count === 1
                ? $tickets_count
                : $tickets_to_add;
            for ($x = 0; $x < $tickets_to_add; $x++) {
                $ticket_index = array_rand($ticket_indexes);
                if (isset($tickets[ $ticket_index ]) && $tickets[ $ticket_index ] instanceof EE_Ticket) {
                    $ticket = $tickets[ $ticket_index ];
                    $this->log("&nbsp; . . adding {$ticket->name()} to {$datetime->name()}<br />");
                    $datetime->_add_relation_to($ticket, 'Ticket');
                    unset($ticket_indexes[ $ticket_index ]);
                    if (! isset($ticket_relations[ $ticket->ID() ])) {
                        $ticket_relations[ $ticket->ID() ] = array(
                            'ticket'    => $ticket,
                            'datetimes' => array($datetime->ID() => $datetime),
                        );
                    } else {
                        $ticket_relations[ $ticket->ID() ]['datetimes'][ $datetime->ID() ] = $datetime;
                    }
                }
            }
            $datetime->save();
        }
        foreach ($tickets as $ticket) {
            if ($ticket instanceof EE_Ticket && ! isset($ticket_relations[ $ticket->ID() ])) {
                $random_datetime = $datetimes[ array_rand($datetimes) ];
                if ($random_datetime instanceof EE_Datetime) {
                    $this->log("&nbsp; ! ! adding {$ticket->name()} to random datetime{$random_datetime->name()}<br />");
                    $random_datetime->_add_relation_to($ticket, 'Ticket');
                    $ticket_relations[ $ticket->ID() ] = array(
                        'ticket'    => $ticket,
                        'datetimes' => array($random_datetime->ID() => $random_datetime),
                    );
                }

            }
        }
        foreach ($ticket_relations as $ticket_relation) {
            /** @var array[] $ticket_relation */
            if ($ticket_relation['ticket'] instanceof EE_Ticket) {
                /** @var EE_Ticket $ticket */
                $ticket          = $ticket_relation['ticket'];
                $datetime_count  = count($ticket_relation['datetimes']);
                $latest_end_date = 0;
                foreach ($ticket_relation['datetimes'] as $datetime) {
                    if ($datetime instanceof EE_Datetime) {
                        // if ticket is only related to one datetime
                        // set ticket's name to that of datetime
                        if ($datetime_count === 1) {
                            $this->log("&nbsp; . . set {$ticket->name()} name to {$datetime->name()}<br />");
                            $ticket->set_name($datetime->name());
                        }
                        // ensure ticket sale start is at least one month before earliest datetime start date
                        if (($datetime->start_date('U') - MONTH_IN_SECONDS) < $ticket->start_date('U')) {
                            $this->log("&nbsp; . . set {$ticket->name()} ticket start date to {$datetime->start_date('Y-m-d H:i a')}<br />");
                            $ticket->set_start_date($datetime->start_date('U') - MONTH_IN_SECONDS);
                        }
                        // find latest end date
                        if ($datetime->end_date('U') > $latest_end_date) {
                            $latest_end_date = $datetime->end_date('U');
                        }
                    }
                }
                $this->log("&nbsp; . . set {$ticket->name()} ticket end date to ");
                $this->log(date('Y-m-d H:i a', $latest_end_date) . '<br/>');
                $ticket->set_end_date($latest_end_date);
                $ticket->save();
            }
        }
    }


    /**
     * @return EE_Ticket[]
     * @throws DomainException
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws ReflectionException
     */
    protected function createTickets()
    {
        $tickets           = array();
        $number_of_tickets = mt_rand(0, 1)
            ? mt_rand(1, 4)
            : mt_rand(1, 8);
        for ($x = 0; $x < $number_of_tickets; $x++) {
            $tickets[] = $this->createTicket($x);
        }
        return $tickets;
    }


    /**
     * @param int $order
     * @return EE_Ticket
     * @throws DomainException
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws ReflectionException
     */
    protected function createTicket($order)
    {
        //  make sure ticket start and end dates are set else they will default to NOW !!!
        $date = $this->getStartDate(-12, 12);
        $qty  = mt_rand(0, 1)
            ? mt_rand(2, 10)
            : ceil(mt_rand(1, 25) / 10) * 10;
        // TKT_ID 	TTM_ID 	TKT_name 	TKT_description 	TKT_qty 	TKT_sold 	TKT_reserved 	TKT_uses
        // TKT_required 	TKT_min 	TKT_max 	TKT_price 	TKT_start_date 	TKT_end_date 	TKT_taxable
        // TKT_order 	TKT_row 	TKT_is_default 	TKT_wp_user 	TKT_parent 	TKT_deleted
        $ticket_args = array(
            'TTM_ID'         => 0,
            'TKT_qty'        => $qty,
            'TKT_sold'       => 0,
            'TKT_reserved'   => 0,
            'TKT_uses'       => -1,
            'TKT_required'   => 0,
            'TKT_min'        => 0,
            'TKT_max'        => -1,
            'TKT_price'      => 0,
            'TKT_start_date' => $date,
            'TKT_end_date'   => $this->getEndDate($date),
            'TKT_taxable'    => mt_rand(0, 1),
            'TKT_order'      => $order,
            'TKT_row'        => $order,
            'TKT_is_default' => 0,
            'TKT_parent'     => 0,
            'TKT_deleted'    => 0,
        );
        /** @type EE_Ticket $ticket */
        $ticket = $this->createObject('Ticket', $ticket_args);
        if (! $ticket instanceof EE_Ticket) {
            throw new DomainException('Invalid EE_Ticket');
        }
        $this->log("&nbsp; . . creating ticket {$ticket->name()}<br />");
        $this->addPricesToTicket($ticket);
        $ticket->ensure_TKT_Price_correct();
        return $ticket;
    }


    /**
     * @param EE_Ticket $ticket
     * @throws DomainException
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws ReflectionException
     */
    protected function addPricesToTicket(EE_Ticket $ticket)
    {
        // add base price
        $prices              = array($this->createPrice());
        $price_modifiers     = array(
            0 => 'Percent Discount',
            1 => 'Dollar Discount',
            2 => 'Percent Surcharge',
            3 => 'Dollar Surcharge',
        );
        $number_of_modifiers = mt_rand(1, 2);
        for ($x = 0; $x < $number_of_modifiers; $x++) {
            // 1 in 4 chance of adding a modifier
            if (mt_rand(1, 4) !== 1) {
                continue;
            }
            $modifier = mt_rand(0, 3);
            if (isset($price_modifiers[ $modifier ])) {
                $prices[] = $this->getPriceModifier($price_modifiers[ $modifier ]);
                // don't add same modifier twice
                unset($price_modifiers[ $modifier ]);
            }
        }
        foreach ($prices as $price) {
            $ticket->_add_relation_to($price, 'Price');
        }
        $ticket->save();
    }


    /**
     * @param EE_Price_Type|null $price_type
     * @return EE_Price
     * @throws DomainException
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws ReflectionException
     */
    protected function createPrice(EE_Price_Type $price_type = null)
    {
        $price_type = $price_type instanceof EE_Price_Type
            ? $price_type
            : $this->getPriceType();
        // is the price type a tax?
        $is_tax = $price_type->base_type() === 4;
        $amount     = $is_tax
            ? (float) (mt_rand(0, 24) . '.' . mt_rand(0, 99))
            : (float) (mt_rand(0, 100) . '.' . mt_rand(0, 99));
        $price      = $this->createObject(
            'Price',
            array(
                'PRT_ID'         => $price_type->ID(),
                'PRC_amount'     => $amount,
                'PRC_name'       => $price_type->name(),
                'PRC_is_default' => $is_tax,
                'PRC_overrides'  => null,
                'PRC_deleted'    => false,
                'PRC_order'      => $price_type->order(),
                'PRC_parent'     => null,
            )
        );
        if (! $price instanceof EE_Price) {
            throw new DomainException('Invalid EE_Price');
        }
        $this->log("&nbsp; . . . creating ticket price {$price->name()}<br />");
        return $price;
    }


    /**
     * @param string $name
     * @return EE_Price_Type
     * @throws DomainException
     */
    protected function getPriceType($name = 'Base Price')
    {
        $price_type = isset($this->price_types[ $name ])
            ? $this->price_types[ $name ]
            : $this->price_types['Base Price'];
        if (! $price_type instanceof EE_Price_Type) {
            throw new DomainException('Invalid EE_Price_Type');
        }
        return $price_type;
    }


    /**
     * @param string $price_modifier
     * @return EE_Price
     * @throws DomainException
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws ReflectionException
     */
    protected function getPriceModifier($price_modifier = '')
    {
        $price_modifiers = array(
            'Percent Discount'  => 2,
            'Dollar Discount'   => 3,
            'Percent Surcharge' => 4,
            'Dollar Surcharge'  => 5,
            'Regional Tax'      => 6,
            'Federal Tax'       => 7,
        );
        $price_modifier  = isset($price_modifiers[ $price_modifier ])
            ? $price_modifier
            : array_rand($price_modifiers);
        return $this->createPrice(
            $this->getPriceType($price_modifier)
        );
    }
}
