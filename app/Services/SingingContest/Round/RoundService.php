<?php
declare(strict_types = 1);

namespace App\Services\SingingContest\Round;

use App\Core\App;
use App\Models\Contestant;
use App\Models\ContestRound;
use App\Models\Genre;
use App\Models\RoundScore;
use App\Services\SingingContest\Contestant\ContestantService;
use App\Services\SingingContest\ContestService;
use App\Services\SingingContest\Genre\GenreService;
use App\Services\SingingContest\Judge\JudgeService;

class RoundService
{
    private $numberOfRounds;
    private $contestRound;
    private $contestantService;

    public function __construct()
    {
        $this->numberOfRounds = App::get('config')['singing_contest_options']['number_of_rounds'];

        $this->contestRound      = new ContestRound();
        $this->contestant        = new Contestant();
        $this->contestantService = new ContestantService();
    }

    /**
     * @param int $createdContestId
     * @param array $insertedContestants
     * @return array
     * @throws \Exception
     * create round
     */
    public function createRounds(int $createdContestId, array $insertedContestants): array
    {
        $roundScore = new RoundScore();

        /**
         * get genres and change positions randomly
         */
        $genre = new Genre();
        $genres = $genre->all();
        shuffle($genres);

        /**
         * create data round foreach contest
         */
        $roundsCreated = [];
        for ($i = 0; $i < $this->numberOfRounds; $i++) {
            $roundData = array_merge(
                ['contest_round'    => $i + 1],
                ['round_genre'      => $genres[$i]->id],
                ['contest_id'       => $createdContestId],
                ['finished'         => 0]
            );
            $roundData = $this->contestRound->add($roundData);
            $roundsCreated[] = $roundData->id;

            /**
             * create data roundScore foreach contestant
             */
            foreach ($insertedContestants as $insertedContestant) {
                $roundScore->add([
                    'contest_round'     => $i + 1,
                    'contest_id'        => $createdContestId,
                    'round_id'          => $roundData->id,
                    'contestant_id'     => $insertedContestant['id'],
                    'contestant_score'  => 0,
                    'judge_score'       => 0,
                    'is_sick'           => 0
                ]);
            }
        }

        return $roundsCreated;
    }

    /**
     * @param int $contestGoingOn
     * @param int $finished
     * @return array
     * @throws \Exception
     * get round data from where to start
     */
    public function getRoundData(int $contestGoingOn): array
    {
        /**
         * get data in wich round should start the contest,
         */
        $contestRoundGoingOn = @App::DB()->raw(
            'SELECT id, contest_id, round_genre, contest_round
             FROM contest_rounds 
             WHERE contest_id = "'.$contestGoingOn.'"
             AND finished = "1"
             ORDER BY id DESC LIMIT 1;'
        );

        if (!$contestRoundGoingOn) {
            return [];
        }
        $contestantDataCollection = $this->contestantService->getContestantData(
            (int)$contestRoundGoingOn[0]->contest_id,
            (int)$contestRoundGoingOn[0]->round_genre,
            (int)$contestRoundGoingOn[0]->id
        );

        /**
         * get genre name
         */
        $genre = new Genre();
        $genreData = $genre->where([['id', '=', $contestRoundGoingOn[0]->round_genre]],1)->get();

        /**
         * get contest judges
         */
        $contestService = new ContestService();
        $contestJudges = $contestService->getContestJudges($contestRoundGoingOn[0]->contest_id);

        $roundDataGoingOn = [
            'roundId'                   => $contestRoundGoingOn[0]->id,
            'round'                     => $contestRoundGoingOn[0]->contest_round,
            'contestId'                 => $contestRoundGoingOn[0]->contest_id,
            'roundGenre'                => $contestRoundGoingOn[0]->round_genre,
            'roundGenreName'            => $genreData[0]->genre,
            'contestJudges'             => $contestJudges,
            'contestantDataCollection'  => $contestantDataCollection
        ];

        return $roundDataGoingOn;
    }

    /**
     * @return array
     * @throws \Exception
     * calculate round score
     */
    public function calculateRound(): array
    {
        $calculatedCollection = [];

        /**
         * get contestId
         */
        $contestService = new ContestService();
        $contestId      = $contestService->getContestGoingOn();

        /**
         * get contestRound data
         */
        $contestRoundGoingOn = @$this->contestRound
            ->where(
                [
                    ['finished', '=', '0'],
                    ['contest_id', '=', $contestId]
                ],
                1
            )
            ->get();
        $roundId        = $contestRoundGoingOn[0]->id;
        $contestRound   = $contestRoundGoingOn[0]->contest_round;
        $roundGenreId   = $contestRoundGoingOn[0]->round_genre;

        /**
         * prepare contestRound data to the calculated collection
         */
        $calculatedCollection['round_id']       = $roundId;
        $calculatedCollection['contest_round']  = $contestRound;
        $calculatedCollection['round_genre']    = $roundGenreId;
        $calculatedCollection['contest_id']     = $contestId;

        /**
         * get contestantsData
         */
        $contestantsData = $this->contestant->where([['contest_id', '=', $contestId]])->get();

        foreach ($contestantsData as $contestantData) {
            /**
             * calculate score randomly based on genre
             * and keep track of the contestant_score and score that will be calculated from the judges
             */
            $genreService = new GenreService();
            $genreScoreStreangth = $genreService->genreCalculation($contestantData, (int)$roundGenreId);
            $contestantData->contestantScore = $genreScoreStreangth;
            $score = $genreScoreStreangth;

            /**
             * if contestant is sick,
             * contestant score will be halved before the judges calculate
             */
            $contestantService = new ContestantService();
            $contestantIsSick = $contestantService->contestantIsSick();
            if($contestantIsSick){
                $score = round(($score / 2), 1);
            }

            /**
             * get contest judges and calculate judges score
             */
            $judgeService = new JudgeService();
            $judgeScores = $judgeService->judgeCalculation($score, $contestantIsSick, (int)$roundGenreId);

            /**
             * add judges score and calculated data to the calculated collection
             */
            $contestantData->judgeScore                 = $judgeScores;
            $contestantData->isSick                     = $contestantIsSick;
            $calculatedCollection['contestantData'][]   = $contestantData;
        }

        return $calculatedCollection;
    }

    /**
     * @return int
     * @throws \Exception
     * Update calculated round in db
     */
    public function updateCalculateRound(array $calculatedScore): int
    {
        /**
         * update contest_rounds
         */
        App::DB()->updateWhere('contest_rounds', [
            'finished' => 1
        ],
            [
                ['id', '=', $calculatedScore['round_id']]
            ]
        );

        /**
         * update round_score
         */
        $totalContestansScoreCollection = [];
        foreach ($calculatedScore['contestantData'] as $contestantData){

            App::DB()->updateWhere('round_score', [
                'contestant_score'  => $contestantData->contestantScore,
                'judge_score'       => $contestantData->judgeScore['round_score'],
                'is_sick'           => $contestantData->isSick
            ],
                [
                    ['round_id', '=', $calculatedScore['round_id']],
                    ['contestant_id', '=', $contestantData->id],
                ]
            );

            /**
             * update judges_score
             */
            foreach ($contestantData->judgeScore['judges_score'] as $judgeId => $judgeScore) {
                App::DB()->updateWhere('judges_score', [
                    'judge_score'   => $judgeScore
                ],
                    [
                        ['contest_id', '=', $calculatedScore['contest_id']],
                        ['round_id', '=', $calculatedScore['round_id']],
                        ['contestant_id', '=', $contestantData->id],
                        ['judge_id', '=', $judgeId],
                    ]
                );
            }

            $score = $contestantData->score + $contestantData->judgeScore['round_score'];
            $totalContestansScoreCollection[$contestantData->id] = $score;
            App::DB()->updateWhere('contestants', [
                'score'  => $score,
            ],
                [
                    ['id', '=', $contestantData->id],
                ]
            );
        }

        /**
         * if the contest is at the end, update also the contest table
         */
        if ($calculatedScore['contest_round'] == $this->numberOfRounds) {

            /**
             * update contestants
             */
            $winners = $this->getWinners($totalContestansScoreCollection);
            foreach ($winners as $idWinner => $score) {
                App::DB()->updateWhere('contestants', [
                    'winner' => 1
                ],
                    [
                        ['id', '=', $idWinner]
                    ]
                );
            }

            /**
             * update contests
             */
            App::DB()->updateWhere('contests', [
                'finished' => 1
            ],
                [
                    ['id', '=', $calculatedScore['contest_id']]
                ]
            );
        }

        /**
         * return updated contest round id
         */
        return (int)$calculatedScore['contest_round'];
    }

    /**
     * @param array $contestansPoints
     * @return array
     * Update calculated round in db
     */
    public function getWinners(array $contestansPoints): array
    {
        $maxPoints = max($contestansPoints);

        $winners = [];
        foreach ($contestansPoints as $key => $contestansPoint) {
            if ($contestansPoint == $maxPoints) {
                $winners[$key] = $contestansPoint;
            }
        }

        return $winners;
    }
}
