<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Alexandre Melard <alexandre.melard@atos.net>
 * Date: 24/10/12
 * Time: 20:37
 * To change this template use File | Settings | File Templates.
 */
namespace Mylen\JQueryFileUploadSandbox\Service;

use Monolog\Logger;
use Mylen\JQueryFileUploadSandbox\DependencyInjection\CacheAware;
use Mylen\JQueryFileUploadSandbox\Entity\Parser;
use Mylen\JQueryFileUploadSandbox\Entity\Event;

class EventFlowService extends CacheAware
{
    /**
     * @var Logger
     */
    protected $logger;
    
    /**
     * @param Cache $c
     */
    public function __construct($c, $l)
    {
        parent::__construct($c);
        $this->logger = $l;
    }
    
    /**
     * @param $parsers Parser[]
     * @param $event Event
     * @return Event[]
     */
    public function parents($parsers, $event)
    {
        if ($buf = $this->cache->fetch('parents' . $event->type)) {
            $parents = unserialize($buf);
        } else {
            $parents = array();
            foreach ($parsers as $parser) {
                foreach ($parser->eventIns as $eventIn) {
                    foreach ($eventIn->eventOuts as $eventOut) {
                        if ($eventOut->type === $event->type) {
                            array_push($parents, ["event" => $eventIn, "file" => basename($parser->file, '.xml')]);
                        }
                    }
                }
            }
            $this->cache->save('parents' . $event->type, serialize($parents));
        }
        return $parents;
    }

    /**
     * @param $parsers Parser[]
     * @param $event Event
     * @return Event[]
     */
    public function children($parsers, $event)
    {
        if ($buf = $this->cache->fetch('children' . $event->type)) {
            $children = unserialize($buf);
        } else {
            $children = array();
            foreach ($parsers as $parser) {
                foreach ($parser->eventIns as $eventIn) {
                    if ($eventIn->type === $event->type) {
                        foreach ($eventIn->eventOuts as $eventOut) {
                            array_push($children, ["event" => $eventOut, "file" => basename($parser->file, '.xml')]);
                        }
                    }
                }
            }
            $this->cache->save('children' . $event->type, serialize($children));
        }
        return $children;
    }

    /**
     * @param $parsers Parser[]
     * @param $event Event
     * @return array
     */
    public function files($parsers, $event)
    {
        if ($buf = $this->cache->fetch('files' . $event->type)) {
            $files = unserialize($buf);
        } else {
            $files = array();
            foreach ($parsers as $parser) {
                foreach ($parser->eventIns as $eventIn) {
                    if ($eventIn->type === $event->type) {
                        array_push($files, ["direction" => "in", "name" => basename($parser->file, '.xml')]);
                    }
                    foreach ($eventIn->eventOuts as $eventOut) {
                        if ($eventOut->type === $event->type) {
                            array_push($files, ["direction" => "out", "name" => basename($parser->file, '.xml')]);
                        }
                    }
                }
            }
            $this->cache->save('files' . $event->type, serialize($files));
        }
        return $files;
    }

    /**
     * @param $soft
     * @param $parsers Parser[]
     * @return Event[]
     * @return array|mixed
     */
    public function uniqueEvents($soft, $parsers)
    {
        if ($buf = $this->cache->fetch('uniqueEvents' . $soft)) {
            $events = unserialize($buf);
        } else {
            $events = array();
            foreach ($parsers as $parser) {
                foreach ($parser->eventIns as $eventIn) {
                    foreach ($eventIn->eventOuts as $eventOut) {
                        array_push($events, $eventOut->type);
                    }
                    array_push($events, $eventIn->type);
                }
            }
            $events = array_unique($events);
            asort($events);
            $this->cache->save('uniqueEvents' . $soft, serialize($events));
        }
        return $events;
    }

    /**
     * @param $parsers Parser[]
     * @param $event Event
     * @return Parser[]
     */
    public function eventParsers($parsers, $event)
    {
        if ($buf = $this->cache->fetch('eventParsers' . $event->type)) {
            $resParsers = unserialize($buf);
        } else {
            /** @var $resParsers Parser[] */
            $resParsers = array();
            foreach ($parsers as $parser) {
                foreach ($parser->eventIns as $eventIn) {
                    foreach ($eventIn->eventOuts as $eventOut) {
                        if ($eventOut->type == $event->type) {
                            array_push($resParsers, ["file" => basename($parser->file, '.xml'), "type" => $eventOut->type, "direction" => "out"]);
                        }
                    }
                    if ($eventIn->type == $event->type) {
                        array_push($resParsers, ["file" => basename($parser->file, '.xml'), "type" => $eventIn->type, "direction" => "in"]);
                    }
                }
            }
            $this->cache->save('eventParsers' . $event->type, serialize($resParsers));
        }
        return $resParsers;
    }

    public function getShortEvent($event)
    {
        return str_replace("CORE_MSG_TYPE_", "", $event);
    }

}
