<?php

namespace tests\Unit;

use App\Models\Contest;
use App\Models\Contestant;
use App\Models\ContestJudge;
use App\Models\ContestRound;
use App\Models\Genre;
use App\Models\GenreStreangth;
use App\Models\Judge;
use App\Models\JudgeScore;
use App\Models\RoundScore;
use App\Services\SingingContest\Contestant\ContestantService;
use App\Services\SingingContest\ContestService;
use App\Services\SingingContest\Genre\GenreService;
use App\Services\SingingContest\ServiceManager;
use tests\TestCase;
use App\Core\{Router, Request, App};

class UnitTest extends TestCase
{
    /**
     * @test
     */
    public function config_is_not_empty()
    {
        $this->assertNotEmpty(App::get('config'));
    }

    /**
     * @test
     */
    public function database_is_not_empty()
    {
        $this->assertNotEmpty(App::DB());
    }

    /**
     * @test
     */
    public function testCreateContest(): int
    {
        /**
         * if there is a contest going on,
         * update the remaining contest rounds first
         */
        $contestService = new ContestService();
        $contestId = $contestService->getContestGoingOn();
        if($contestId){
            $serviceManager = new ServiceManager();
            $contestRound   = new ContestRound();

            $contestRoundData = $contestRound
                ->where(
                    [
                        ['contest_id', '=', $contestId],
                        ['finished', '=', 0]
                    ]
                )
                ->get();
            /**
             * update remaining rounds
             */
            for($y = 1; $y <= count($contestRoundData); $y++){
                $contestRoundIdUpdated = $serviceManager->calculateAndUpdateRound();
                $this->assertIsInt((int)$contestRoundIdUpdated);
            }
        }

        /**
         * create contest
         */
        $contest = new Contest();
        $contestColumns = [
            'finished' => 0
        ];

        /**
         * insert data in db
         */
        $createdContest = $contest->add($contestColumns);
        $contestId = $createdContest->rows[0]->id;

        $this->assertNotEmpty($createdContest);
        return (int)$contestId;
    }

    /**
     * @depends testCreateContest
     */
    public function testCreateContestants(int $contestId): array
    {
        $contestant         = new Contestant();
        $genreService       = new GenreService();
        $contestantService  = new ContestantService();

        /**
         * get random contestants names from service
         */
        $registeredContestants = $contestantService->contestantGenerator(10);
        $this->assertNotEmpty($registeredContestants);
        $this->assertEquals(10, count($registeredContestants));

        /**
         * create data in db for every contestant
         */
        $insertedContestants = [];
        foreach ($registeredContestants as $key => $contestantName){
            /**
             * create db columns
             */
            $contestantColumns = [
                'contest_id' => $contestId,
                'name'       => $contestantName,
                'score'      => 0
            ];

            /**
             * create data in db
             */
            $insertedContestantData = $contestant->add($contestantColumns);
            $this->assertNotEmpty($insertedContestantData);

            /**
             * inserted data to return
             */
            $insertedContestants[$key]['id']     = $insertedContestantData->id;
            $insertedContestants[$key]['name']   = $contestantName;

            /**
             * get genre strangth randomly for every contestant
             * and insert to genre streangth to identify fo every contestant
             */
            $genresStreangth = new GenreStreangth();
            $genresStreangthCollection = $genreService->getRandomGenresStreangth();
            foreach ($genresStreangthCollection as $genreId => $genresStreangthScore) {
                $genresStreangthColumns = [
                    'contest_id'    => $contestId,
                    'contestant_id' => $insertedContestantData->rows[0]->id,
                    'genre_id'      => $genreId,
                    'streangth'     => $genresStreangthScore,
                ];
                $genresStreangths = $genresStreangth->add($genresStreangthColumns);
                $this->assertNotEmpty($genresStreangths);
            }
        }

        $insertedDataContestants['contestId'] = $contestId;
        $insertedDataContestants['insertedContestants'] = $insertedContestants;

        return $insertedDataContestants;
    }

    /**
     * @depends testCreateContestants
     */
    public function testCreateRounds(array $insertedDataContestants): array
    {
        $roundScore     = new RoundScore();
        $contestRount   = new ContestRound();

        /**
         * get genres and change positions randomly
         */
        $genre = new Genre();
        $genres = $genre->all();
        $this->assertNotEmpty($genres);
        $this->assertEquals(6, count($genres));
        shuffle($genres);

        /**
         * create data round foreach round
         */
        $contestId = $insertedDataContestants['contestId'];
        $roundsCreated = [];
        for ($i = 0; $i < 6; $i++) {
            $roundData = array_merge(
                ['contest_round'    => $i + 1],
                ['round_genre'      => $genres[$i]->id],
                ['contest_id'       => $contestId],
                ['finished'         => 0]
            );
            $roundData = $contestRount->add($roundData);
            $this->assertNotEmpty($roundData);
            $roundsCreated[] = $roundData->id;

            /**
             * create data roundScore foreach contestant
             */
            foreach ($insertedDataContestants['insertedContestants'] as $insertedContestant) {
                $roundInsertedScore = $roundScore->add([
                    'contest_round'     => $i + 1,
                    'contest_id'        => $contestId,
                    'round_id'          => $roundData->id,
                    'contestant_id'     => $insertedContestant['id'],
                    'contestant_score'  => 0,
                    'judge_score'       => 0,
                    'is_sick'           => 0
                ]);
                $this->assertNotEmpty($roundInsertedScore);
            }
        }

        $roundAndContestants['insertedContestants'] = $insertedDataContestants['insertedContestants'];
        $roundAndContestants['roundsCreated']       = $roundsCreated;
        $roundAndContestants['contestId']           = $contestId;

        return $roundAndContestants;
    }

    /**
     * @depends testCreateRounds
     */
    public function testChooseJudges(array $roundAndContestants): int
    {
        /**
         * get all judges
         */
        $judge = new Judge();
        $judges = $judge->all();
        $this->assertNotEmpty($judges);
        $this->assertEquals(5, count($judges));
        $judgesScore = new JudgeScore();

        /**
         * choose judges ramdomly,
         * until we have a unique array with count of three judges
         */
        $judgesChoosed = [];
        $judgesNumber = 3;
        $contestJudgesNumber = 3;
        while (count($judgesChoosed) < $judgesNumber) {
            $judgeIdChoosed = $judges[rand(0, 4)]->id;
            $judgeNameChoosed = $judges[rand(0, 4)]->name;

            if (!in_array($judgeNameChoosed, $judgesChoosed)) {
                $judgesChoosed[$judgeIdChoosed] = $judgeNameChoosed;
                $contestJudgesNumber--;
            }
        }
        $this->assertNotEmpty($judgesChoosed);
        $this->assertEquals(3, count($judgesChoosed));

        /**
         * create judges contest choosed data in db
         */
        $contestJudge = new ContestJudge();
        foreach ($judgesChoosed as $judgeId => $judgeName) {
            $contestJudges = $contestJudge->add([
                'judge_id'   => $judgeId,
                'judge_name' => $judgeName,
                'contest_id' => $roundAndContestants['contestId']
            ]);
            $this->assertNotEmpty($contestJudges);

            /**
             * create judge score foreach round and contestant in db
             */
            foreach ($roundAndContestants['roundsCreated'] as $roundId) {
                foreach ($roundAndContestants['insertedContestants'] as $contestantId){
                    $judgesScores = $judgesScore->add([
                        'contest_id'    => $roundAndContestants['contestId'],
                        'round_id'      => $roundId,
                        'contestant_id' => $contestantId['id'],
                        'judge_id'      => $judgeId,
                        'judge_score'   => 0
                    ]);
                    $this->assertNotEmpty($judgesScores);
                }
            }
        }

        return (int)$roundAndContestants['contestId'];
    }

    /**
     * @depends testChooseJudges
     */
    public function testUpdateCalculatedRound($contestId): void
    {
        $contestRound   = new ContestRound();
        $serviceManager = new ServiceManager();

        $contestRoundData = $contestRound
            ->where(
                [
                    ['contest_id', '=', $contestId],
                    ['finished', '=', 0]
                ]
            )
            ->get();
        /**
         * update remaining rounds
         */
        for($y = 1; $y <= count($contestRoundData); $y++){
            $contestRoundIdUpdated = $serviceManager->calculateAndUpdateRound();
            $this->assertIsInt((int)$contestRoundIdUpdated);
        }
    }
}

?>