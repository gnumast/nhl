<?php

namespace NHL\Events;

use NHL\Entities\Player;
use NHL\Entities\Team;
use NHL\Event;

/**
 * Class Give
 *
 * @package NHL\Events
 */
class Give extends Event
{
    const REGEX = "/([A-Z]{3})@(?:GIVE|TAKE)AWAY - #(\\d+) ([A-Z\\h-]+), ([A-Za-z\\h\\.]+)/";
    const DESCRIBE = "[P%s: %s] Giveaway: %s in %s";

    /** @var string $eventType */
    public $eventType = Types::GIVE;

    /**
     * @inheritdoc
     */
    public function parse()
    {
        $data = $this->toArray();
        if (empty($data)) {
            $this->parsed = false;
            return false;
        }

        $this->team = new Team($data['team']);
        $this->player = new Player($data['number'], $data['name'], $this->team);
        $this->location = $data['location'];

        $this->parsed = true;
        return true;
    }

    /**
     * @inheritdoc
     */
    public function toArray()
    {
        if (preg_match_all(self::REGEX, $this->line, $matches)) {
            return [
                'team' => $matches[1][0],
                'number' => $matches[2][0],
                'name' => $matches[3][0],
                'location' => $matches[4][0]
            ];
        } else {
            return [];
        }
    }
    /**
     * @inheritdoc
     */
    public function describe()
    {
        if ($this->parsed) {
            return sprintf(
                self::DESCRIBE,
                $this->eventPeriod,
                $this->eventTime,
                $this->player,
                $this->location
            );
        }
    }

}