<?php namespace MaartenStaa\Glicko2;

/**
 * This class holds the results accumulated over a rating period.
 *
 * @author Maarten Staa
 */
class RatingPeriodResults
{
    private $results = array();
    private $participants = array();

    /**
     * Constructor that allows you to initialise the list of participants.
     *
     * @param participants (Set of Rating objects)
     */
    public function __construct(array $participants = array())
    {
        $this->participants = $participants;
    }

    /**
     * Add a result to the set.
     *
     * @param Rating winner
     * @param Rating loser
     */
    public function addResult(Rating $winner, Rating $loser)
    {
        $this->results[] = new Result($winner, $loser);
    }

    /**
     * Record a draw between two players and add to the set.
     *
     * @param Rating player1
     * @param Rating player2
     */
    public function addDraw(Rating $player1, Rating $player2)
    {
        $this->results[] = new Result($player1, $player2, true);
    }

    /**
     * Get a list of the results for a given player.
     *
     * @param player
     * @return List of results
     */
    public function getResults(Rating $player)
    {
        $filteredResults = array();
        
        foreach ($this->results as $result) {
            if ($result->participated($player)) {
                $filteredResults[] = $result;
            }
        }
        
        return $filteredResults;
    }

    /**
     * Get all the participants whose results are being tracked.
     *
     * @return set of all participants covered by the result set.
     */
    public function getParticipants()
    {
        // Run through the results and make sure all players have been pushed into the participants set.
        foreach ($this->results as $result) {
            if (in_array($result->getWinner(), $this->participants, true) === false) {
                $this->participants[] = $result->getWinner();
            }
            if (in_array($result->getLoser(), $this->participants, true) === false) {
                $this->participants[] = $result->getLoser();
            }
        }

        return $this->participants;
    }

    /**
     * Add a participant to the rating period, e.g. so that their rating will
     * still be calculated even if they don't actually compete.
     *
     * @param rating
     */
    public function addParticipant(Rating $rating)
    {
        $this->participants[] = $rating;
    }

    /**
     * Clear the result set.
     */
    public function clear()
    {
        $this->results = array();
    }
}
