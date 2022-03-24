<?php
Use App\Core\App;

    $router->get('', 'SingingContestController@indexAction');
    $router->get('show', 'SingingContestController@showAction');
    $router->get('final-round', 'SingingContestController@finalRoundAction');
    $router->get('history', 'SingingContestController@historyAction');

    $router->post('create', 'SingingContestController@createAction');
    $router->post('rounds', 'SingingContestController@roundsAction');

?>
