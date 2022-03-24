<?php require ('partials/header.php'); ?>

<h1 style="margin-left: 20px;">Round <?=$roundDataGoingOn['round']?></h1>
<h1 style="margin-left: 20px;">Genre <?=$roundDataGoingOn['roundGenreName']?></h1>

<form action="/rounds" method="post">
<?php if ($roundDataGoingOn['round'] == 6) { ?>
    <button class="btn-primary roundButton">Go To Final Round</button>
<?php } else {?>
    <button class="btn-primary roundButton">Go To Round <?=$roundDataGoingOn['round']+1?></button>
<?php } ?>
</form>

<table id="contestant">
    <tr>
        <th>Nr</th>
        <th>Name</th>
        <th>Score</th>
        <th>Streangth</th>
        <th>Genre Score</th>
        <th>Judge Score</th>
        <th>Is Sick</th>
        <?php foreach ($roundDataGoingOn['contestJudges'] as $judgeId => $contestJudge){ ?>
            <th><?=$contestJudge?></th>
        <?php } ?>
    </tr>

<?php $i = 0; ?>
<?php foreach ($roundDataGoingOn['contestantDataCollection'] as $key => $contestPoint){ ?>
    <tr id="<?=$key?>">
        <td><?=++$i?></td>
        <td><?=$contestPoint['name']?></td>
        <td><?=$contestPoint['total_score']?></td>
        <td><?=$contestPoint['genre_streangth']?></td>
        <td><?=$contestPoint['contestant_score']?></td>
        <td><?=$contestPoint['judge_round_score']?></td>
        <td><?=$contestPoint['contestant_is_sick']?></td>
        <?php foreach ($contestPoint['judges_score'] as $judgeId => $judgeScoreData){ ?>
            <td><?=$judgeScoreData['score']?></td>
        <?php } ?>
    </tr>
<?php } ?>
</table>


<?php require ('partials/footer.php'); ?>
