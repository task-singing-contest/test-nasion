<?php
declare(strict_types = 1);

namespace App\Services\SingingContest;

use App\Core\App;
use App\Models\Contest;
use App\Models\ContestJudge;

class ContestService{

    public $contest;

    public function __construct()
    {
        $this->contest = new Contest();
    }

    /**
     * create contest
     */
    public function createContest(): int
    {
        $contestColumns = [
            'finished' => 0
        ];

        /**
         * insert data in db
         */
        $createdContest = $this->contest->add($contestColumns);

        return (int)$createdContest->id;
    }

    /**
     * get contestId that is going on and not finished
     */
    public function getContestGoingOn(int $finished = 0): int
    {
        /**
         * get contestId
         */
        $contestGoingOn = $this->contest->where([['finished', '=', $finished]], 1)->get();

        if ($contestGoingOn) {
            return (int)$contestGoingOn[0]->id;
        }

        return 0;
    }

    /**
     * get contestId that is going on and not finished
     */
    public function getLasFinishedContest(): array
    {
        /**
         * get contestId
         */
        $contestId = App::DB()->raw(
            'SELECT id 
            FROM contests 
            WHERE finished = "1" 
            ORDER BY id DESC LIMIT 1'
        );

        return $contestId;
    }

    /**
     * @param $contestId
     * @return array
     * @throws \Exception
     */
    public function getContestJudges($contestId): array
    {
        $contestJudge = new ContestJudge();
        $contestJudge = $contestJudge->where([['contest_id', '=', $contestId]])->get();

        $collection = [];
        foreach ($contestJudge as $judge) {
            $collection[$judge->judge_id] = $judge->judge_name;
        }

        ksort($collection);
        return $collection;
    }
}
