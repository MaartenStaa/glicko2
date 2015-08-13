<?php namespace MaartenStaa\Glicko2;

/**
 * Simple integration tests.
 *
 * @author Maarten Staa
 */
class IntegrationTest extends \PHPUnit_Framework_TestCase
{
    private $ratingSystem;
    private $results;
    private $player1;
    private $player2;
    private $player3;
    private $player4;
    private $player5;

    public function setUp()
    {
        parent::setUp();

        $this->ratingSystem = new RatingCalculator(0.06, 0.5);
        $this->results = new RatingPeriodResults();
        $this->player1 = new Rating($this->ratingSystem); // the main player of Glickman's example
        $this->player2 = new Rating($this->ratingSystem);
        $this->player3 = new Rating($this->ratingSystem);
        $this->player4 = new Rating($this->ratingSystem);
        $this->player5 = new Rating($this->ratingSystem); // this player won't compete during the test

        $this->player1->setRating(1500);
        $this->player2->setRating(1400);
        $this->player3->setRating(1550);
        $this->player4->setRating(1700);
        
        $this->player1->setRatingDeviation(200);
        $this->player2->setRatingDeviation(30);
        $this->player3->setRatingDeviation(100);
        $this->player4->setRatingDeviation(300);

        $this->results->addParticipants($this->player5);  // the other players will be added to the participants list automatically
    }
    
    /**
     * This test uses the values from the example towards the end of Glickman's paper as a simple test of the calculation engine
     * In addition, we have another player who doesn't compete, in order to test that their deviation will have increased over time.
     */
    public function testGlicko()
    {
        // test that the scaling works
        $this->assertEquals(0, $this->player1->getGlicko2Rating(), '', 0.00001);
        $this->assertEquals(1.1513, $this->player1->getGlicko2RatingDeviation(), '', 0.00001);

        $this->results->addResult($this->player1, $this->player2); // player1 beats player 2
        $this->results->addResult($this->player3, $this->player1); // player3 beats player 1
        $this->results->addResult($this->player4, $this->player1); // player4 beats player 1
        
        $this->ratingSystem->updateRatings($this->results);
        
        // test that the player1's new rating and deviation have been calculated correctly
        $this->assertEquals(1464.06, $this->player1->getRating(), '', 0.01);
        $this->assertEquals(151.52, $this->player1->getRatingDeviation(), '', 0.01);
        $this->assertEquals(0.05999, $this->player1->getVolatility(), '', 0.01);

        // test that opponent 4 has had appropriate calculations applied
        $this->assertEquals($this->ratingSystem->getDefaultRating(), $this->player5->getRating(), 'rating should be unaffected');
        $this->assertTrue($this->ratingSystem->getDefaultRatingDeviation() < $this->player5->getRatingDeviation(), 'rating deviation should have grown');
        $this->assertEquals($this->ratingSystem->getDefaultVolatility(), $this->player5->getVolatility(), 'volatility should be unaffected');
    }
}
