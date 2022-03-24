<?php

namespace App\Services\SingingContest;

use App\Services\SingingContest\Contestant\ContestantService;
use App\Services\SingingContest\Judge\JudgeService;
use App\Services\SingingContest\Round\RoundService;

class ServiceManager{

    private $roundService;
    private $contestService;
    private $contestantService;
    private $judgeService;

    public function __construct()
    {
        $this->roundService         = new RoundService();
        $this->contestService       = new ContestService();
        $this->contestantService    = new ContestantService();
        $this->judgeService         = new JudgeService();
    }

    public function createContest(): void
    {
        /**
         * first step
         * create contest
         */
        $createdContestId = $this->contestService->createContest();

        /**
         * second step
         * create contestans
         */
        $insertedContestants = $this->contestantService->createContestants($createdContestId);

        /**
         * third step
         * create rounds
         */
        $roundsCreated = $this->roundService->createRounds($createdContestId, $insertedContestants);

        /**
         * fourth step
         * choose distinct judges randomly, and add them to contest
         */
        $this->judgeService->chooseJudges($createdContestId, $insertedContestants, $roundsCreated);
    }

    /**
     * @throws \Exception
     * calculate and update round
     */
    public function calculateAndUpdateRound(): int
    {
        /**
         * get calculated array score
         */
        $calculatedScore    = $this->roundService->calculateRound();
        $updatedRound       = $this->roundService->updateCalculateRound($calculatedScore);

        return $updatedRound;
    }
}

?>