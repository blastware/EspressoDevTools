<?php

namespace Blastware\EspressoDevTools\Domain\Services;

use EE_Cart;
use EE_Datetime;
use EE_Error;
use EE_Line_Item;
use EE_Ticket;
use EEH_Line_Item;
use EventEspresso\core\domain\values\session\SessionLifespan;
use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidInterfaceException;
use InvalidArgumentException;
use ReflectionException;

/**
 * Class CartDataGenerator
 * Description
 *
 * @package Blastware\EspressoDevTools\Domain\Services
 * @author  Brent Christensen
 * @since   $VID:$
 */
class CartDataGenerator extends DataGenerator
{

    /**
     * @var EE_Cart $cart
     */
    protected $cart;

    /**
     * @var SessionLifespan
     */
    private $session_lifespan;


    /**
     * CartDataGenerator constructor.
     *
     * @param EE_Cart         $cart
     * @param DataTracker     $data_tracker
     * @param SessionLifespan $session_lifespan
     */
    public function __construct(EE_Cart $cart, DataTracker $data_tracker, SessionLifespan $session_lifespan)
    {
        parent::__construct($data_tracker);
        $this->cart = $cart;
        $this->session_lifespan = $session_lifespan;
    }


    /**
     * @param int $max
     * @throws ReflectionException
     * @throws InvalidArgumentException
     * @throws InvalidInterfaceException
     * @throws InvalidDataTypeException
     * @throws EE_Error
     */
    public function addSampleCartData($max = 100)
    {
        $created = $this->createCarts(mt_rand(1, $max));
        if ($created) {
            EE_Error::add_success("Generated {$created} line items");
        }
    }


    /**
     * @param int $cart_count
     * @return int
     * @throws ReflectionException
     * @throws InvalidArgumentException
     * @throws InvalidInterfaceException
     * @throws InvalidDataTypeException
     * @throws EE_Error
     */
    private function createCarts($cart_count = 0)
    {
        $created = 0;
        $now = time();
        $increment = WEEK_IN_SECONDS / $cart_count;
        $timestamp = $now - WEEK_IN_SECONDS - $increment;
        for ($x = 0; $x < $cart_count; $x++) {
            $created += $this->createCart($timestamp);
            $timestamp += $timestamp < $now
                ? $increment
                : 0;
        }
        return $created;
    }


    /**
     * @param int $timestamp
     * @return int
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws ReflectionException
     */
    private function createCart($timestamp = 0)
    {
        $this->populateObjects('Ticket');
        $added =  0;
        $tickets_added = 0;
        $total_line_item = EEH_Line_Item::create_total_line_item();
        $total_line_item->set_TXN_ID(0);
        $tickets_to_add = mt_rand(1,4);
        for($x = 0; $x < $tickets_to_add; $x++){
            /** @var EE_Ticket $ticket */
            $ticket = $this->getAnyObject('Ticket');
            if ($ticket instanceof EE_Ticket && $ticket->first_datetime() instanceof EE_Datetime) {
                $tickets_added++;
                $qty = mt_rand(1, 10);
                EEH_Line_Item::add_ticket_purchase(
                    $total_line_item,
                    $ticket,
                    $qty
                );
                if($this->session_lifespan->expiration() <= $timestamp) {
                    $ticket->increase_reserved($qty, 'CartDataGenerator:' . __LINE__);
                }
            }
        }
        if($tickets_added) {
            $this->adjustLineItemTimestamps(
                $total_line_item,
                $timestamp,
                "<br />creating Cart {$total_line_item->name()} {$total_line_item->total_no_code()}<br />"
            );
            $added += $total_line_item->save_this_and_descendants_to_txn(0);
        }
        return $added;
    }


    /**
     * @param EE_Line_Item $line_item
     * @param int          $timestamp
     * @param string       $log_note
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws ReflectionException
     */
    private function adjustLineItemTimestamps(EE_Line_Item $line_item, $timestamp, $log_note)
    {
        $this->log($log_note);
        $line_item->set('LIN_timestamp', $timestamp);
        foreach ($line_item->children() as $child_line_item) {
            $this->adjustLineItemTimestamps(
                $child_line_item,
                $timestamp,
                "&nbsp; . creating line item {$child_line_item->name()} {$child_line_item->total_no_code()}<br />"
            );
        }
    }
}
