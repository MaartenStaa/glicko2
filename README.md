# Glicko2

PHP implementation of the Glicko-2 rating algorithm. This is a PHP port of [goochjs/glicko2](https://github.com/goochjs/glicko2).

[![Build Status](https://travis-ci.org/MaartenStaa/laravel-41-route-caching.svg)][1]
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/MaartenStaa/laravel-41-route-caching/badges/quality-score.png?b=master)][2]
[![Code Coverage](https://scrutinizer-ci.com/g/MaartenStaa/laravel-41-route-caching/badges/coverage.png?b=master)][3]

## Installation

Using [Composer](http://getcomposer.org/), add the package to your `require` section.

```json
{
	"require": {
		"maartenstaa/glicko2": "dev-master"
	}
}
```

Run `composer update` to fetch the new requirement.

## Usage

```php
use MaartenStaa\Glicko2\Rating;
use MaartenStaa\Glicko2\RatingCalculator;
use MaartenStaa\Glicko2\RatingPeriodResults;

// Instantiate a RatingCalculator object.
// At instantiation, you can set the default rating for a player's volatility and
// the system constant for your game ("τ", which constrains changes in volatility
// over time) or just accept the defaults.
$calculator = new RatingCalculator(/* $initVolatility, $tau */);

// Instantiate a Rating object for each player.
$player1 = new Rating($calculator/* , $rating, $ratingDeviation, $volatility */);
$player2 = new Rating($calculator/* , $rating, $ratingDeviation, $volatility */);
$player3 = new Rating($calculator/* , $rating, $ratingDeviation, $volatility */);

// Instantiate a RatingPeriodResults object.
$results = new RatingPeriodResults();

// Add game results to the RatingPeriodResults object until you reach the end of your rating period.
// Use addResult($winner, $loser) for games that had an outcome.
$results->addResult($player1, $player2);
// Use addDraw($player1, $player2) for games that resulted in a draw.
$results->addDraw($player1, $player2);
// Use addParticipant($player) to add players that played no games in the rating period.
$results->addParticipant($player3);

// Once you've reached the end of your rating period, call the updateRatings method
// against the RatingCalculator; this takes the RatingPeriodResults object as argument.
//  * Note that the RatingPeriodResults object is cleared down of game results once
//    the new ratings have been calculated.
//  * Participants remain within the RatingPeriodResults object, however, and will
//    have their rating deviations recalculated at the end of future rating periods
//    even if they don't play any games. This is in-line with Glickman's algorithm.
$calculator->updateRatings($results);

// Access the getRating, getRatingDeviation, and getVolatility methods of each
// player's Rating to see the new values.
foreach (array($player1, $player2, $player3) as $index => $player) {
	echo 'Player #', $index, ' values: ', $player->getRating(), ', ',
		$player->getRatingDeviation(), ', ', $player->getVolatility(), PHP_EOL;
}
```
