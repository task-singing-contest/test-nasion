<?php
declare(strict_types = 1);

namespace App\Services\SingingContest\Contestant;

use App\Core\App;
use App\Models\Contestant;
use App\Models\GenreStreangth;
use App\Models\RoundScore;
use App\Services\SingingContest\Genre\GenreService;
use App\Services\SingingContest\Judge\JudgeService;

class ContestantService{

    private $contestantNumber;

    public function __construct()
    {
        $this->contestantNumber = App::get('config')['singing_contest_options']['contestant_number'];
    }

    /**
     * @param int $contestantsNumber
     * @return array
     * @throws \Exception
     * get 10 random contesant names from api
     */
    public function contestantGenerator(int $contestantsNumber) : array
    {
        $names = getApiRequest("http://names.drycodes.com/".$contestantsNumber);

        return $names;
    }

    /**
     * @param int $createdContestId
     * @return array
     * @throws \Exception
     * create contestants
     */
    public function createContestants(int $createdContestId): array
    {
        $contestant     = new Contestant();
        $genreService   = new GenreService();

        /**
         * get random contestants names from service
         */
        $registeredContestants = $this->contestantGenerator($this->contestantNumber);

        /**
         * create data in db for every contestant
         */
        $insertedContestants = [];
        foreach ($registeredContestants as $key => $contestantName){
            /**
             * create db columns
             */
            $contestantColumns = [
                'contest_id' => $createdContestId,
                'name'       => $contestantName,
                'score'      => 0
            ];

            /**
             * create data in db
             */
            $insertedContestantData = $contestant->add($contestantColumns);

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
                    'contest_id'    => $createdContestId,
                    'contestant_id' => $insertedContestantData->rows[0]->id,
                    'genre_id'      => $genreId,
                    'streangth'     => $genresStreangthScore,
                ];
                $genresStreangth->add($genresStreangthColumns);
            }
        }

        return $insertedContestants;
    }

    /**
     * @return int
     * There is a 5% chance that a contestant will become sick during any round.
     */
    public function contestantIsSick(): int
    {
        return rand(1, 20) == 1 ? 1 : 0;
    }

    /**
     * @return array
     * @throws \Exception
     * Get contestant data
     */
    public function getContestantData(int $contestId, int $genreId, int $roundId): array
    {
        $contestantDataCollection = [];
        $genreService = new GenreService();

        /**
         * collect contestant score and add to the roundData from where should start the contest
         */
        $contestantsData = @App::DB()->raw(
            'SELECT id, name, score 
             FROM contestants 
             WHERE contest_id = "'.$contestId.'"
             ORDER BY score DESC;'
        );

        $judgeService = new JudgeService();

        foreach ($contestantsData as $key => $contestant) {

            /** @var $genreStreangth get contestant genre streangth */
            $genreStreangth = $genreService->getGenreSreangth((int)$contestId, (int)$contestant->id, (int)$genreId);
            /** @var $contestantRoundScoreData get contestant round score data */
            $contestantRoundScoreData   = $this->getContestantScoreRoundData((int)$contestant->id, (int)$roundId);
            $contestantScore            = $contestantRoundScoreData[0]->contestant_score;
            $judgeRoundScore            = $contestantRoundScoreData[0]->judge_score;
            $contestantIsSick           = $contestantRoundScoreData[0]->is_sick == '1' ? 'Yes' : 'No';
            /** @var $judgeScore get every judges score for this contestant round */
            $judgeScore = $judgeService->getJudgeScore((int)$contestId, (int)$contestant->id, (int)$roundId);

            $contestantDataCollection[$contestant->id]['name']                  = $contestant->name;
            $contestantDataCollection[$contestant->id]['total_score']           = $contestant->score;
            $contestantDataCollection[$contestant->id]['genre_streangth']       = $genreStreangth;
            $contestantDataCollection[$contestant->id]['contestant_score']      = $contestantScore;
            $contestantDataCollection[$contestant->id]['judge_round_score']     = $judgeRoundScore;
            $contestantDataCollection[$contestant->id]['contestant_is_sick']    = $contestantIsSick;
            $contestantDataCollection[$contestant->id]['judges_score']          = $judgeScore;
        }

        return $contestantDataCollection;
    }

    /**
     * @param int $contestantId
     * @param $roundId
     * @return int
     * @throws \Exception
     * get if contestant was sisk in this round
     */
    private function getContestantScoreRoundData(int $contestantId, $roundId): array
    {
        $roundScore  = new RoundScore();
        $roundsScore = $roundScore->where(
            [
                ['contestant_id', '=', $contestantId],
                ['round_id', '=', $roundId]
            ],
            1
        )->get();

        return $roundsScore;
    }
}

?>