# Requirements
* PHP >= 7.3.0
* MySQL >= 5.6.10
* Composer

# How to Install
Note: It is recommended that you install LAMP with my LAMP install script to ensure that you have all of the requirements.

My LAMP install script is located at

https://github.com/task-singing-contest/singign-contest

Clone the repository

```
git clone https://github.com/task-singing-contest/singign-contest
```

Change to the repository directory

```
cd singign-contest
```

Run composer to install any PHP dependencies

```
composer install
```

Setup database credentials in config.php file

```
Execute dump_db.sql
```

Change to the public directory

```
cd public
```

Start the PHP server (0.0.0.0 is the default route, this makes PHP listen on all IPv4 interfaces)

```
php -S 0.0.0.0:8000
```

Visit the IP address (127.0.0.1 if you are running Linux natively, or the IP address of your VM/VPS/etc) http://127.0.0.1:8000 in your web browser.

To run the included unit tests, make sure you are still in the public directory, and then type the following command

```
../vendor/bin/phpunit ../tests
```

# Project Outline
This is a small game that simulate a singing contest. The contest will be created by the click of a button, and this button will then change to progress the contest through a series of six rounds. When creating the contest, a set of ten contestants and three judges will be randomly generated. The progress button will then step through the set of rounds until one lucky contestant is crowned the winner. There should only ever be a single contest at any one time, until the six rounds of the current have been completed.

A second view in the project will show the player a history of the last five contest winners and their final scores. The top scoring contestant of all time should also be displayed in this view.


### Rounds

Each round will be a genre of music. The six to choose from are: `Rock`, `Country`, `Pop`, `Disco`, `Jazz` and `The Blues`. The order of rounds should be randomized during contest creation, but each genre should be in the contest exactly once (no duplicates). Each contestant will have different strengths in each genre that will affect their result. During each round, each contestant's genre rating is multiplied by a random single-decimal place float between 0.1 and 10.0, and this score is used by each judge to give each contestant a final judge's score for the round. After the six rounds are completed, the contestant with the highest total judge score is crowned the winner and recorded for historical purposes. In the event of a tie, each contestant is rewarded with this achievement.

### Contestants

The ten contestants are randomly generated during contest creation. They have a random integer strength score for each of the six genres that ranges from 1 to 10. During each round, the contestant's calculated score is therefore ranging from 0.1 to 100.0, as outlined in the *Rounds* section above. This calculated contestant score is converted by each of the three selected judges in order to determine the contestant's judge's score. The total of the three judges' scores across each of the six rounds is used to determine the winner of the contest.

There is also a 5% chance that a contestant will become sick during any round. If the contestant is flagged as being sick during the round, their calculated contestant score is halved before the judges calculate their round score. The calculated contestant score should be rounded to one significant decimal place (rounding up) so the minimum score (even when sick) should still be 0.1.

### Judges

There are a total of five judges, but only three are in each contest, randomly selected during contest creation. Each judge is unique and has their own method for scoring based on the calculated score from a contestant in any given round. All judge's scores are integer values from 0 to 10, thus each round a contestant gets a total judges' score out of 30 as an integer.

- `RandomJudge`: This judge gives a random score out of 10, regardless of the calculated contestant score.
- `HonestJudge`: This judge converts the calculated contestant score evenly using the following table:
		||Calculate Score Range||Judge Score||
		|     0.1 - 10.0        |      1     |
		|    10.1 - 20.0        |      2     |
		|    20.1 - 30.0        |      3     |
		|    30.1 - 40.0        |      4     |
		|    40.1 - 50.0        |      5     |
		|    50.1 - 60.0        |      6     |
		|    60.1 - 70.0        |      7     |
		|    70.1 - 80.0        |      8     |
		|    80.1 - 90.0        |      9     |
		|    90.1 - 100.0       |     10     |
- `MeanJudge`: This judge gives every contestant with a calculated contestant score less than 90.0 a judge score of 2. Any contestant scoring 90.0 or more instead receives a 10.
- `RockJudge`: This judge's favourite genre is `Rock`. For any other genre, the `RockJudge` gives a random integer score out of 10, regardless of the calculated contestant score. For the `Rock` genre, this judge gives a score based on the calculated contestant score - less than 50.0 results in a judge score of 5, 50.0 to 74.9 results in an 8, while 75 and above results in a 10.
- `FriendlyJudge`: This judge gives every contestant a score of 8 unless they have a calculated contestant score of less than or equal to 3.0, in which case the `FriendlyJudge` gives a 7. If the contestant is sick, the `FriendlyJudge` awards a bonus point, regardless of calculated contestant score.


### Architecture

This is a MVC project, where the logic is implemented in separated Services. The Controller direct orders to ServiceManager wich orchestrates the controller orders to services. Services deliver changes to the models.

When a request arrives, it is routed to a controller.

The controller is in charge of analyzing the request and calling the relevant services or DAOs (Data Access Objects, the classes in charge of communicating with the database).

Services are objects that perform some kind of computation. It can be on the "domain layer" or it can be purely technical services (like name generator)

Finally, the controller aggregates data received from various services and calls a "view" that will render the data in HTML

### Design Patterns
- MVC
- Singelton
- Factory
- Front Controller


### Principels
- Single Responsibility
- Opnen Close
- Dry
- Kiss


### Technologies
- PHP
- HTML
- CSS
- Bootstrap
- Javascript
- Jquery
