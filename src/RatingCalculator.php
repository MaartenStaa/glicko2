<?php namespace MaartenStaa\Glicko2;

/**
 * This is the main calculation engine based on the contents of Glickman's paper.
 * http://www.glicko.net/glicko/glicko2.pdf
 *
 * @author Maarten Staa
 */
class RatingCalculator
{
    const DEFAULT_RATING = 1500.0;
    const DEFAULT_DEVIATION = 350;
    const DEFAULT_VOLATILITY = 0.06;
    const DEFAULT_TAU = 0.75;
    const MULTIPLIER = 173.7178;
    const CONVERGENCE_TOLERANCE = 0.000001;
    
    protected $tau; // constrains volatility over time
    protected $defaultVolatility;

    /**
     * Constructor to create a new rating calculator.
     *
     * @param float $initVolatility Initial volatility for new ratings
     * @param float $tau            How volatility changes over time
     */
    public function __construct($initVolatility = self::DEFAULT_VOLATILITY, $tau = self::DEFAULT_TAU)
    {
        $this->defaultVolatility = $initVolatility;
        $this->tau = $tau;
    }

    /**
     * Run through all players within a result set and calculate their new ratings.
     * Players within the result set who did not compete during the rating period
     * will have see their deviation increase (in line with Prof Glickman's paper).
     * Note that this method will clear the results held in the association result set.
     *
     * @param RatingPeriodResults $results
     */
    public function updateRatings(RatingPeriodResults $results)
    {
        $participants = $results->getParticipants();

        foreach ($participants as $player) {
            if (count($results->getResults($player)) > 0) {
                $this->calculateNewRating($player, $results->getResults($player));
            } else {
                // If a player does not compete during the rating period, then only Step 6 applies.
                // The player's rating and volatility parameters remain the same but deviation increases.
                $player->setWorkingRating($player->getGlicko2Rating());
                $player->setWorkingRatingDeviation($this->calculateNewRatingDeviation(
                    $player->getGlicko2RatingDeviation(),
                    $player->getVolatility()
                ));
                $player->setWorkingVolatility($player->getVolatility());
            }
        }
        
        // Now iterate through the participants and confirm their new ratings.
        foreach ($participants as $player) {
            $player->finaliseRating();
        }

        // Lastly, clear the result set down in anticipation of the next rating period.
        $results->clear();
    }

    /**
     * This is the function processing described in step 5 of Glickman's paper.
     *
     * @param Rating $player
     * @param array  $results
     */
    protected function calculateNewRating(Rating $player, array $results)
    {
        $phi = $player->getGlicko2RatingDeviation();
        $sigma = $player->getVolatility();
        $a = log(pow($sigma, 2));
        $delta = $this->delta($player, $results);
        $v = $this->v($player, $results);
        
        // step 5.2 - set the initial values of the iterative algorithm to come in step 5.4
        $A = $a;
        $B = 0;
        if (pow($delta, 2) > pow($phi, 2) + $v) {
            $B = log(pow($delta, 2) - pow($phi, 2) - $v);
        } else {
            $k = 1;
            $B = $a - ($k * abs($this->tau));
            
            while ($this->f($B, $delta, $phi, $v, $a, $this->tau) < 0) {
                $k++;
                $B = $a - ($k * abs($this->tau));
            }
        }

        // step 5.3
        $fA = $this->f($A, $delta, $phi, $v, $a, $this->tau);
        $fB = $this->f($B, $delta, $phi, $v, $a, $this->tau);

        // step 5.4
        while (abs($B - $A) > self::CONVERGENCE_TOLERANCE) {
            $C = $A + ((($A - $B) * $fA) / ($fB - $fA));
            $fC = $this->f($C, $delta, $phi, $v, $a, $this->tau);
            
            if ($fC * $fB < 0) {
                $A = $B;
                $fA = $fB;
            } else {
                $fA = $fA / 2;
            }
            
            $B = $C;
            $fB = $fC;
        }
        
        $newSigma = exp($A / 2);
        
        $player->setWorkingVolatility($newSigma);

        // Step 6
        $phiStar = $this->calculateNewRatingDeviation($phi, $newSigma);
        
        // Step 7
        $newPhi = 1 / sqrt((1 / pow($phiStar, 2)) + (1 / $v));

        // note that the newly calculated rating values are stored in a "working" area in the Rating object
        // this avoids us attempting to calculate subsequent participants' ratings against a moving target
        $player->setWorkingRating(
            $player->getGlicko2Rating() +
            (pow($newPhi, 2) * $this->outcomeBasedRating($player, $results))
        );
        $player->setWorkingRatingDeviation($newPhi);
        $player->incrementNumberOfResults(count($results));
    }
    
    protected function f($x, $delta, $phi, $v, $a, $tau)
    {
        return (exp($x) * (pow($delta, 2) - pow($phi, 2) - $v - exp($x)) /
            (2 * pow(pow($phi, 2) + $v + exp($x), 2))) -
            (($x - $a) / pow($tau, 2));
    }

    /**
     * This is the first sub-function of step 3 of Glickman's paper.
     *
     * @param  float $deviation
     * @return float
     */
    protected function g($deviation)
    {
        return 1 / (sqrt(1 + (3 * pow($deviation, 2) / pow(M_PI, 2))));
    }

    /**
     * This is the second sub-function of step 3 of Glickman's paper.
     *
     * @param  float $playerRating
     * @param  float $opponentRating
     * @param  float $opponentDeviation
     * @return float
     */
    protected function e($playerRating, $opponentRating, $opponentDeviation)
    {
        return 1 / (1 + exp(-1 * $this->g($opponentDeviation) * ($playerRating - $opponentRating)));
    }

    /**
     * This is the main function in step 3 of Glickman's paper.
     *
     * @param player
     * @param results
     * @return
     */
    protected function v(Rating $player, array $results)
    {
        $v = 0;
        
        foreach ($results as $result) {
            $opponent = $result->getOpponent($player);

            $v = $v + ((
                pow($this->g($opponent->getGlicko2RatingDeviation()), 2)) *
                $this->e(
                    $player->getGlicko2Rating(),
                    $opponent->getGlicko2Rating(),
                    $opponent->getGlicko2RatingDeviation()
                ) * (
                    1.0 - $this->e(
                        $player->getGlicko2Rating(),
                        $opponent->getGlicko2Rating(),
                        $opponent->getGlicko2RatingDeviation()
                    )
                ));
        }
        
        return pow($v, -1);
    }

    /**
     * This is a formula as per step 4 of Glickman's paper.
     *
     * @param player
     * @param results
     * @return delta
     */
    protected function delta(Rating $player, array $results)
    {
        return $this->v($player, $results) * $this->outcomeBasedRating($player, $results);
    }

    /**
     * This is a formula as per step 4 of Glickman's paper.
     *
     * @param  Rating $player
     * @param  array  $results
     * @return float           Expected rating based on game outcomes.
     */
    protected function outcomeBasedRating(Rating $player, array $results)
    {
        $outcomeBasedRating = 0;
        
        foreach ($results as $result) {
            $outcomeBasedRating = $outcomeBasedRating +
                ($this->g($result->getOpponent($player)->getGlicko2RatingDeviation()) * (
                    $result->getScore($player) - $this->e(
                        $player->getGlicko2Rating(),
                        $result->getOpponent($player)->getGlicko2Rating(),
                        $result->getOpponent($player)->getGlicko2RatingDeviation()
                    )
                ));
        }
        
        return $outcomeBasedRating;
    }

    /**
     * This is the formula defined in step 6. It is also used for players
     * who have not competed during the rating period.
     *
     * @param  float $phi
     * @param  float $sigma
     * @return float        New rating deviation.
     */
    protected function calculateNewRatingDeviation($phi, $sigma)
    {
        return sqrt(pow($phi, 2) + pow($sigma, 2));
    }

    /**
     * Converts from the value used within the algorithm to a rating in the same range as traditional Elo et al
     *
     * @param  float $rating Rating in Glicko2 scale.
     * @return float         Rating in Glicko scale.
     */
    public static function convertRatingToOriginalGlickoScale($rating)
    {
        return (($rating * self::MULTIPLIER) + self::DEFAULT_RATING);
    }

    /**
     * Converts from a rating in the same range as traditional Elo et al to the value used within the algorithm
     *
     * @param  float $rating Rating in Glicko scale.
     * @return float         Rating in Glicko2 scale.
     */
    public static function convertRatingToGlicko2Scale($rating)
    {
        return (($rating - self::DEFAULT_RATING) / self::MULTIPLIER) ;
    }

    /**
     * Converts from the value used within the algorithm to a rating deviation in the same range as traditional Elo et al
     *
     * @param  float $ratingDeviation Rating Deviation in Glicko2 scale.
     * @return float                  Rating Deviation in Glicko scale.
     */
    public static function convertRatingDeviationToOriginalGlickoScale($ratingDeviation)
    {
        return $ratingDeviation * self::MULTIPLIER;
    }

    /**
     * Converts from a rating deviation in the same range as traditional Elo et al to the value used within the algorithm
     *
     * @param  float $ratingDeviation Rating Deviation in Glicko scale.
     * @return float                  Rating Deviation in Glicko2 scale.
     */
    public function convertRatingDeviationToGlicko2Scale($ratingDeviation)
    {
        return $ratingDeviation / self::MULTIPLIER;
    }

    public function getDefaultRating()
    {
        return self::DEFAULT_RATING;
    }

    public function getDefaultVolatility()
    {
        return $this->defaultVolatility;
    }

    public function getDefaultRatingDeviation()
    {
        return self::DEFAULT_DEVIATION;
    }
}
