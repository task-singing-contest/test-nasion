<?php require ('partials/header.php'); ?>

<h1 style="margin-left: 20px;">History Score</h1>

<table id="contestant">
    <tr>
        <th>Name</th>
        <th>Points</th>
        <th>Nr</th>
    </tr>

<?php $i = 0; ?>
<?php foreach ($bestContestants as $key => $bestContestant){ ?>
    <tr id="<?=$key?>">
        <td><?=++$i?></td>
        <td><?=$bestContestant->name?></td>
        <td><?=$bestContestant->score?></td>
    </tr>
<?php } ?>
</table>


<?php require ('partials/footer.php'); ?>
