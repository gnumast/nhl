<?php

namespace NHL\Events;

/**
 * Class Types
 *
 * @package NHL\Events
 */
class Types
{
    const NONE = 'NONE';
    const PERIODSTART = 'PSTR';
    const FACEOFF = 'FAC';
    const HIT = 'HIT';
    const SHOT = 'SHOT';
    const BLOCK = 'BLOCK';
    const MISS = 'MISS';
    const STOP = 'STOP';
    const GIVE = 'GIVE';
    const TAKE = 'TAKE';
    const GOAL = 'GOAL';
    const GAMEEND = 'GEND';
    const PERIODEND = 'PEND';
    const PENALTY = 'PENL';

    /**
     * Returns all the supported event types, only those will be parsed.
     *
     * @return array
     */
    public static function getSupported() {
        return [
            self::SHOT,
            self::MISS,
            self::HIT,
            self::FACEOFF,
            self::PENALTY,
            self::BLOCK,
            self::PERIODSTART,
            self::PERIODEND,
            self::GOAL,
            self::STOP,
            self::TAKE,
            self::GIVE,
            self::GAMEEND
        ];
    }

    /**
     * @param string $event_string Represents the event type taken from the HTM file
     * @param string $line         Entire line to parse
     *
     * @return mixed
     */
    public static function makeTypeFromString($event_string, $line)
    {
        switch ($event_string) {
            case self::FACEOFF:
                return new FaceOff($line);
            case self::HIT:
                return new Hit($line);
            case self::SHOT:
                return new Shot($line);
            case self::BLOCK:
                return new Block($line);
            case self::MISS:
                return new Miss($line);
            case self::PENALTY:
                return new Penalty($line);
            case self::PERIODEND:
            case self::PERIODSTART:
            case self::GAMEEND:
                return new Period($line);
            case self::GOAL:
                return new Goal($line);
            case self::STOP:
                return new Stop($line);
            case self::GIVE:
                return new Give($line);
            case self::TAKE:
                return new Take($line);
            default:
                return self::NONE;
        }
    }
}