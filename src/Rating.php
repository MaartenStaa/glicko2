<?php namespace MaartenStaa\Glicko2;

/**
 * Holds an individual's Glicko-2 rating.
 *
 * Glicko-2 ratings are an average skill value, a standard deviation and a
 * volatility (how consistent the player is). Prof Glickman's paper on the
 * algorithm allows scaling of these values to be more directly comparable with
 * existing rating systems such as Elo or USCF's derivation thereof. This
 * implementation outputs ratings at this larger scale.
 *
 * @author Maarten Staa
 */
class Rating
{
    protected $ratingSystem;

    protected $rating;
    protected $ratingDeviation;
    protected $volatility;

    protected $numberOfResults = 0;

    // The following variables are used to hold values temporarily whilst running calculations.
    protected $workingRating;
    protected $workingRatingDeviation;
    protected $workingVolatility;

    public function __construct(
        RatingCalculator $ratingSystem,
        $initRating = null,
        $initRatingDeviation = null,
        $initVolatility = null
    ) {
        $this->ratingSystem = $ratingSystem;

        $this->rating = $initRating !== null ?
            $initRating : $ratingSystem->getDefaultRating();
        $this->ratingDeviation = $initRatingDeviation !== null ?
            $initRatingDeviation : $ratingSystem->getDefaultRatingDeviation();
        $this->volatility = $initVolatility !== null ?
            $initVolatility : $ratingSystem->getDefaultVolatility();
    }

    /**
     * Return the average skill value of the player.
     *
     * @return float
     */
    public function getRating()
    {
        return $this->rating;
    }

    /**
     * Set the average skill value of the player.
     *
     * @param float $rating
     */
    public function setRating($rating)
    {
        $this->rating = $rating;
    }

    /**
     * Return the average skill value of the player scaled down
     * to the scale used by the algorithm's internal workings.
     *
     * @return float
     */
    public function getGlicko2Rating()
    {
        return $this->ratingSystem->convertRatingToGlicko2Scale($this->rating);
    }

    /**
     * Set the average skill value, taking in a value in Glicko2 scale.
     *
     * @param float $rating
     */
    public function setGlicko2Rating($rating)
    {
        $this->rating = $this->ratingSystem->convertRatingToOriginalGlickoScale($rating);
    }

    /**
     * Get the player's volatility.
     *
     * @return float
     */
    public function getVolatility()
    {
        return $this->volatility;
    }

    /**
     * Set the player's volatility.
     *
     * @param float $volatility
     */
    public function setVolatility($volatility)
    {
        $this->volatility = $volatility;
    }

    public function getRatingDeviation()
    {
        return $this->ratingDeviation;
    }

    public function setRatingDeviation($ratingDeviation)
    {
        $this->ratingDeviation = $ratingDeviation;
    }

    /**
     * Return the rating deviation of the player scaled down to the scale used
     * by the algorithm's internal workings.
     *
     * @return float
     */
    public function getGlicko2RatingDeviation()
    {
        return $this->ratingSystem->convertRatingDeviationToGlicko2Scale($this->ratingDeviation);
    }

    /**
     * Set the rating deviation, taking in a value in Glicko2 scale.
     *
     * @param float $ratingDeviation
     */
    public function setGlicko2RatingDeviation($ratingDeviation)
    {
        $this->ratingDeviation =
            $this->ratingSystem->convertRatingDeviationToOriginalGlickoScale($ratingDeviation);
    }

    /**
     * Used by the calculation engine, to move interim calculations into their
     * "proper" places.
     */
    public function finaliseRating()
    {
        $this->setGlicko2Rating($this->workingRating);
        $this->setGlicko2RatingDeviation($this->workingRatingDeviation);
        $this->setVolatility($this->workingVolatility);
        
        $this->setWorkingRatingDeviation(0);
        $this->setWorkingRating(0);
        $this->setWorkingVolatility(0);
    }
    
    public function getNumberOfResults()
    {
        return $this->numberOfResults;
    }

    public function incrementNumberOfResults($increment)
    {
        $this->numberOfResults = $this->numberOfResults + $increment;
    }

    public function setWorkingVolatility($workingVolatility)
    {
        $this->workingVolatility = $workingVolatility;
    }

    public function setWorkingRating($workingRating)
    {
        $this->workingRating = $workingRating;
    }

    public function setWorkingRatingDeviation($workingRatingDeviation)
    {
        $this->workingRatingDeviation = $workingRatingDeviation;
    }
}
