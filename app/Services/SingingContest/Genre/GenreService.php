<?php
declare(strict_types = 1);

namespace App\Services\SingingContest\Genre;

use App\Core\App;
use App\Models\Contestant;
use App\Models\Genre;
use App\Models\GenreStreangth;

class GenreService{

    private $genreMinStreanght;
    private $genreMaxStreanght;
    private $minMultiple;
    private $maxMultiple;
    private $decimalMultiple;

    public function __construct()
    {
        $singingContestConfig = App::get('config')['singing_contest_options'];
        $this->genreMinStreanght = $singingContestConfig['genre_min_streanght'];
        $this->genreMaxStreanght = $singingContestConfig['genre_max_streanght'];
        $this->minMultiple = $singingContestConfig['min_multiple'];
        $this->maxMultiple = $singingContestConfig['max_multiple'];
        $this->decimalMultiple = $singingContestConfig['decimal_multiple'];
    }

    /**
     * @return array
     * @throws \Exception
     * get random genre strangth
     */
    public function getRandomGenresStreangth(): array
    {
        $genre  = new Genre();
        $genres = $genre->all();

        /**
         * string to snack case
         */
        $genresStreangth = [];
        foreach ($genres as $genre) {
            $genresStreangth[$genre->id] = rand($this->genreMinStreanght, $this->genreMaxStreanght);
        }

        return $genresStreangth;
    }

    /**
     * @param Contestant $contestantData
     * @return float
     * calculate genre score
     * @throws \Exception
     */
    public function genreCalculation(Contestant $contestantData, int $roundGenre): float
    {
        /**
         * get genre strangth for contestant
         * and return score strangth
         */
        $genreStreangth = new GenreStreangth();
        $genreStreangths = $genreStreangth
            ->where(
            [
                ['contestant_id', '=', $contestantData->id],
                ['contest_id', '=', $contestantData->contest_id],
                ['genre_id', '=', $roundGenre]
            ],
            1
        )
        ->get();

        $score = $genreStreangths[0]->streangth * frand($this->minMultiple, $this->maxMultiple, $this->decimalMultiple);
        return (float)$score;
    }

    /**
     * @param int $contestId
     * @param int $contestantId
     * @param int $genreId
     * @return array
     * @throws \Exception
     * get genre streangth
     */
    public function getGenreSreangth(int $contestId, int $contestantId, int $genreId): int
    {
        $genreStreangth = new GenreStreangth();
        $genre = $genreStreangth->where(
            [
                ['contestant_id', '=', $contestantId],
                ['contest_id', '=', $contestId],
                ['genre_id', '=', $genreId]
            ],
            1
        )->get();

        return (int)$genre[0]->streangth;
    }
}
