<?php
declare(strict_types = 1);

namespace App\Services\SingingContest\Judge;

use App\Core\App;
use App\Models\ContestJudge;
use App\Models\Genre;
use App\Models\Judge;
use App\Models\JudgeScore;
use App\Services\SingingContest\ContestService;

class JudgeService
{
    private $contestJudgesNumber;
    private $jugdesTotal;

    public function __construct()
    {
        $singingContestConfig = App::get('config')['singing_contest_options'];
        $this->contestJudgesNumber  = $singingContestConfig['contest_judges_number'];
        $this->jugdesTotal  = $singingContestConfig['jugdes_total'];
    }

    /**
     * @param int $createdContestId
     * @throws \Exception
     * choose 3 judges randomly out of total
     */
    public function chooseJudges(int $createdContestId, array $insertedContestants, array $roundsCreated): void
    {
        /**
         * get all judges
         */
        $judge          = new Judge();
        $judges         = $judge->all();
        $judgesScore    = new JudgeScore();

        /**
         * choose judges ramdomly,
         * until we have a unique array with count of three judges
         */
        $judgesChoosed = [];
        $judgesNumber = $this->contestJudgesNumber;
        while (count($judgesChoosed) < $judgesNumber) {
            $judgeIdChoosed = $judges[rand(0, $this->jugdesTotal - 1)]->id;
            $judgeNameChoosed = $judges[rand(0, $this->jugdesTotal - 1)]->name;

            if (!in_array($judgeNameChoosed, $judgesChoosed)) {
                $judgesChoosed[$judgeIdChoosed] = $judgeNameChoosed;
                $this->contestJudgesNumber--;
            }
        }

        /**
         * create judges contest choosed data in db
         */
        $contestJudge = new ContestJudge();
        foreach ($judgesChoosed as $judgeId => $judgeName) {
            $contestJudge->add([
                'judge_id'   => $judgeId,
                'judge_name' => $judgeName,
                'contest_id' => $createdContestId
            ]);

            /**
             * create judge score foreach round and contestant in db
             */
            foreach ($roundsCreated as $roundId) {
                foreach ($insertedContestants as $contestantId){
                    $judgesScore->add([
                        'contest_id'    => $createdContestId,
                        'round_id'      => $roundId,
                        'contestant_id' => $contestantId['id'],
                        'judge_id'      => $judgeId,
                        'judge_score'   => 0
                    ]);
                }
            }
        }
    }

    /**
     * @param float $score
     * @param int $isSick
     * @param string $roundGenre
     * @return int
     * @throws \Exception
     * calculate judge score
     */
    public function judgeCalculation(float $score, int $isSick, int $roundGenre): array
    {
        /**
         * get genre name with genreId
         */
        $genre      = new Genre();
        $genres     = $genre->where([['id', '=', $roundGenre]], 1)->get();
        $genreName  = $genres[0]->genre;

        /**
         * get choosed contest judges
         */
        $contest        = new ContestService();
        $contestId      = $contest->getContestGoingOn();
        $contestJudge   = new ContestJudge();
        $contestJudges  = $contestJudge->where([['contest_id', '=', $contestId]])->get();

        $judgeScores = 0;
        $judgesScoresCoolection = [];

        foreach ($contestJudges as $contestJudge) {
            if ($contestJudge->judge_name == 'RandomJudge') {
                $judgesVote = $this->randomJudge();
                $judgeScores += $judgesVote;
                $judgesScoresCoolection['judges_score'][$contestJudge->judge_id] = $judgesVote;
            } elseif ($contestJudge->judge_name == 'HonestJudge') {
                $judgesVote = $this->honestJudge($score);
                $judgeScores += $judgesVote;
                $judgesScoresCoolection['judges_score'][$contestJudge->judge_id] = $judgesVote;
            } elseif ($contestJudge->judge_name == 'MeanJudge') {
                $judgesVote = $this->meanJudge($score);
                $judgeScores += $judgesVote;
                $judgesScoresCoolection['judges_score'][$contestJudge->judge_id] = $judgesVote;
            } elseif ($contestJudge->judge_name == 'RockJudge') {
                $judgesVote = $this->rockJudge($score, $genreName);
                $judgeScores += $judgesVote;
                $judgesScoresCoolection['judges_score'][$contestJudge->judge_id] = $judgesVote;
            } else {
                $judgesVote = $this->friendlyJudge((float)$score, $isSick);
                $judgeScores += $judgesVote;
                $judgesScoresCoolection['judges_score'][$contestJudge->judge_id] = $judgesVote;
            }
        }

        $judgesScoresCoolection['round_score'] = $judgeScores;

        return $judgesScoresCoolection;
    }

    /**
     * @return int
     * This judge gives a random score out of 10, regardless of the calculated contestant score.
     */
    private function randomJudge(): int
    {
        return rand(1, 10);
    }

    /**
     * @param float $score
     * @return int
     * This judge converts the calculated contestant score.
     */
    private function honestJudge(float $score): int
    {

        if (compareFloats($score, '>', 0.0) && compareFloats($score, '<=', 10.0)) {
            return 1;
        } elseif (compareFloats($score, '>', 10.0) && compareFloats($score, '<=', 20.0)) {
            return 2;
        } elseif (compareFloats($score, '>', 20.0) && compareFloats($score, '<=', 30.0)) {
            return 3;
        } elseif (compareFloats($score, '>', 30.0) && compareFloats($score, '<=', 40.0)) {
            return 4;
        } elseif (compareFloats($score, '>', 40.0) && compareFloats($score, '<=', 50.0)) {
            return 5;
        } elseif (compareFloats($score, '>', 50.0) && compareFloats($score, '<=', 60.0)) {
            return 6;
        } elseif (compareFloats($score, '>', 60.0) && compareFloats($score, '<=', 70.0)) {
            return 7;
        } elseif (compareFloats($score, '>', 70.0) && compareFloats($score, '<=', 80.0)) {
            return 8;
        } elseif (compareFloats($score, '>', 80.0) && compareFloats($score, '<=', 90.0)) {
            return 9;
        } else {
            return 10;
        }
    }

    /**
     * @param float $score
     * @return int
     * This judge gives every contestant with a calculated contestant score less than 90.0 a judge score of 2.
     * Any contestant scoring 90.0 or more instead receives a 10.
     */
    private function meanJudge(float $score): int
    {
        if (compareFloats($score, '>=', 90.0)) {
            return 10;
        }

        return 2;
    }

    /**
     * @param float $score
     * @param string $genre
     * @return int
     * This judge's favourite genre is Rock. For any other genre, the RockJudge gives a random integer score out of 10,
     * regardless of the calculated contestant score.
     * For the Rock genre, this judge gives a score based on the calculated contestant score - less than 50.0
     * results in a judge score of 5, 50.0 to 74.9 results in an 8, while 75 and above results in a 10.
     */
    private function rockJudge(float $score, string $genre): int
    {
        if ($genre != 'Rock') {
            return rand(1, 10);
        }

        if (compareFloats($score, '<', 50.0)) {
            return 5;
        } elseif (compareFloats($score, '>=', 50.0) && compareFloats($score, '<', 75.0)) {
            return 8;
        }

        return 10;
    }

    /**
     * @param float $score
     * @param int $isSick
     * @return int
     * This judge gives every contestant a score of 8
     * unless they have a calculated contestant score of less than or equal to 3.0,
     * in which case the FriendlyJudge gives a 7.
     * If the contestant is sick, the FriendlyJudge awards a bonus point, regardless of calculated contestant score.
     */
    private function friendlyJudge(float $score, int $isSick): int
    {
        $awardedSickPoint = $isSick ? 1 : 0;

        if (compareFloats($score, '<=', 3.0)) {
            return 7 + $awardedSickPoint;
        }

        return 8 + $awardedSickPoint;
    }

    /**
     * @param int $contestId
     * @param int $contestantId
     * @param int $roundId
     * @return array
     * @throws \Exception
     * get judge score for round contestant
     */
    public function getJudgeScore(int $contestId, int $contestantId, int $roundId): array
    {
        $judgeScore  = new JudgeScore();
        $judgesScore = $judgeScore->where(
            [
                ['contest_id', '=', $contestId],
                ['contestant_id', '=', $contestantId],
                ['round_id', '=', $roundId]
            ]
        )->get();

        $score = [];
        foreach ($judgesScore as $judge) {
            $judgeName = $this->getJudgeName((int)$judge->judge_id);

            $score[$judge->judge_id]['score']   = $judge->judge_score;
            $score[$judge->judge_id]['name']    = $judgeName;
        }

        return $score;
    }

    /**
     * @param int $judgeId
     * @return string
     * @throws \Exception
     * get judge name with id
     */
    private function getJudgeName(int $judgeId): string
    {
        $judge  = new Judge();
        $judges = $judge->where([['id', '=', $judgeId]])->get();

        return $judges[0]->name;
    }
}

?>