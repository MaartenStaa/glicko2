<?php namespace MaartenStaa\Glicko2;

use InvalidArgumentException;

/**
 * Represents the result of a match between two players.
 *
 * @author Maarten Staa
 */
class Result
{
    const POINTS_FOR_WIN = 1.0;
    const POINTS_FOR_LOSS = 0.0;
    const POINTS_FOR_DRAW = 0.5;
    
    private $isDraw = false;
    private $winner;
    private $loser;
    
    /**
     * Record a new result from a match between two players.
     *
     * @param  Rating $winner
     * @param  Rating $loser
     * @param  bool   $isDraw
     * @throws InvalidArgumentException
     */
    public function __construct(Rating $winner, Rating $loser, $isDraw = false)
    {
        if (!$this->validPlayers($winner, $loser)) {
            throw new InvalidArgumentException;
        }

        $this->winner = $winner;
        $this->loser = $loser;
        $this->isDraw = $isDraw;
    }

    /**
     * Check that we're not doing anything silly like recording a match with only one player.
     *
     * @param  Rating $player1
     * @param  Rating $player2
     * @return bool
     */
    private function validPlayers(Rating $player1, Rating $player2)
    {
        return $player1 !== $player2;
    }

    /**
     * Test whether a particular player participated in the match represented by this result.
     *
     * @param  Rating $player
     * @return bool           True if player participated in the match
     */
    public function participated(Rating $player)
    {
        return $this->winner === $player || $this->loser === $player;
    }

    /**
     * Returns the "score" for a match.
     *
     * @param  Rating $player The player to get a score for.
     * @return float          1 for a win, 0.5 for a draw and 0 for a loss
     * @throws InvalidArgumentException
     */
    public function getScore(Rating $player)
    {
        $score;
        
        if ($this->winner === $player) {
            $score = static::POINTS_FOR_WIN;
        } elseif ($this->loser === $player) {
            $score = static::POINTS_FOR_LOSS;
        } else {
            throw new InvalidArgumentException('Player did not participate in match');
        }

        if ($this->isDraw) {
            $score = static::POINTS_FOR_DRAW;
        }

        return $score;
    }

    /**
     * Given a particular player, returns the opponent.
     *
     * @param  Rating $player The player to get the opponent for.
     * @return Rating         The player in this Result who is not the given player.
     * @throws InvalidArgumentException
     */
    public function getOpponent(Rating $player)
    {
        $opponent = null;
        
        if ($this->winner === $player) {
            $opponent = $this->loser;
        } elseif ($this->loser === $player) {
            $opponent = $this->winner;
        } else {
            throw new InvalidArgumentException('Player did not participate in match');
        }
        
        return $opponent;
    }

    public function getWinner()
    {
        return $this->winner;
    }

    public function getLoser()
    {
        return $this->loser;
    }
}
