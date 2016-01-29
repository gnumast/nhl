<?php

namespace NHL\Exporters;

use NHL\Command;
use NHL\Contracts\Exporter;
use NHL\Entities\Game;

/**
 * Class MySQL
 *
 * @package NHL\Exporters
 */
class MySQL implements Exporter
{

    /** @var Game $game */
    private $game;

    /**
     * @inheritdoc
     */
    public function setGame(Game $game)
    {
        $this->game = $game;
    }

    /**
     * @inheritdoc
     */
    public function export()
    {
        return true;
    }
}