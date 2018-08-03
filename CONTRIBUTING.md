# Contributing to OpenDominion

Thank you for considering contributing to OpenDominion. This document contains some guidelines to explain the contributing process and hopefully answer some common questions.

Do note that almost nothing is set in stone. Feel free to even contribute to this document!


#### Table of Contents

- [Before getting started](#before-getting-started)
  - [Prerequisites](#prerequisites)
  - [Vision](#vision)
- [How can I contribute?](#how-can-i-contribute)
  - [Joining the community](#joining-the-community)
  - [Participating in the beta](#participating-in-the-beta)
  - [Providing info](#providing-info)
  - [Reporting bugs](#reporting-bugs)
  - [Collaborating with development](#collaborating-with-development)
- [Local development](#local-development)
  - [Setting up](#setting-up)
  - [Directory structure](#directory-structure)
  - [Deviation from Laravel](#deviation-from-laravel)
  - [Things to keep in mind](#things-to-keep-in-mind)
  - [How to run OpenDominion](#how-to-run-opendominion)
  - [How to run tests](#how-to-run-tests)
  - [How to update](#how-to-update)
  - [How to reset](#how-to-reset)
  - [Style guide and standards](#style-guide-and-standards)
- [Resources](#resources)


## Before getting started

### Prerequisites

- For code contributions, make sure you have a [GitHub account](https://github.com/signup/free)
- Make sure you read, understand and agree to the [Code of Conduct](CODE_OF_CONDUCT.md)

Collaboration and contributing will be primarily done through GitHub.

If possible, join the [OpenDominion Discord server](https://discord.gg/mFk2wZT) for asking questions and a general easy method of communication.


### Vision

OpenDominion aims to be an open source clone of Dominion (round 70 ruleset), with a few changes and improvements:

- An open source project with well-written, documented and (unit-)tested code.
- A modern and responsive design, developed and tested for desktop, tablet and mobile.
- Free to play. No premium accounts or features.
- No advertisements.
- Lifetime accounts by default.

This means that pretty much all of vanilla Dominion's content (land, buildings, races, units, spells, wonders etc) and mechanics *are* set in stone.
 
 
## How can I contribute?

### Joining the community

While the original Dominion community back in the day was quite sizable, the OpenDominion project started a few years after Dominion closed down and the when the original community had already dwindled to a fraction of its former size. When starting OpenDominion, the goal was to code and work on the project as much as possible, before trying to gain popularity and build a community.

By time of writing, a few years of hard work have passed and significant progress has been made. Though still incomplete and under development, OpenDominion is slowly gaining GitHub followers, people on Discord and beta testers.

As author of the OpenDominion project, I'd like to personally invite **you** (yes you, you're reading this, after all) to join us in the journey if you're interested in the project. Which I assume you somewhat are, since you're reading this! 

It doesn't matter if you're a Dominion veteran or if you're new, feel free and be welcomed to:

- [Participate in the beta](#participating-in-the-beta)!
- Joining the [Discord](https://discord.gg/mFk2wZT) 
- Spread the word of those you know might be interested.
- Keeping an eye on the project by watching it on GitHub.
- Praise the project by starring it on GitHub.


### Participating in the beta

The beta of OpenDominion is currently running at [https://beta.opendominion.net](https://beta.opendominion.net).

Feel free to register and play around!


### Providing info

The original Dominion has been dead for quite a while now. Even links and resources like [The Dominion Encyclopedia](http://dominion.lykanthropos.com/wiki/) and [RedFox Dominion Protection Simulator](http://dominion.lykanthropos.com/DomSim/) are broken and slowly deteriorating over time. The IRC channels are empty and owners and maintainers of the aforementioned resources are nowhere to be found.

Even though I've played Dominion myself, I can't remember how everything looked or worked. If you're a veteran player, please get in contact with me and share any knowledge, screenshots/drawings, info or anything relevant to the original Dominion that you want to share! I started this project alone, but I need **your** help to make it the great game it once was.

Feel free to browse through the [issue tracker](https://github.com/WaveHack/OpenDominion/issues), look for issues with the label ["help wanted"](https://github.com/WaveHack/OpenDominion/issues?q=is%3Aissue+is%3Aopen+label%3A%22help+wanted%22) or ["discussion"](https://github.com/WaveHack/OpenDominion/issues?q=is%3Aissue+is%3Aopen+label%3A"discussion") and reply with your thoughts if you feel like it. Not everything is related to the original Dominion gameplay and I could use some help with other things, like [design](https://github.com/WaveHack/OpenDominion/issues/63) and some legal stuff ([terms and conditions](https://github.com/WaveHack/OpenDominion/issues/38) and [privacy policy](https://github.com/WaveHack/OpenDominion/issues/37)) at time of writing.

Also if want to bring up a topic that you think should get looked into, don't hesitate to poke me on Discord or [create a new issue](https://github.com/WaveHack/OpenDominion/issues/new) on the issue tracker.


### Reporting bugs

You can report bugs to the [issue tracker](https://github.com/WaveHack/OpenDominion/issues).

Please search the issue tracker first if the particular bug already has an open issue. If it does, add your message to the existing issue instead of opening a new issue.

If a closed or resolved issue exists for your particular bug, reopen it. If in doubt, just open a new issue.


### Collaborating with development

First, make sure the changes you're going to do adhere to the [vision of OpenDominion](#vision).

Fork the repository on GitHub, make a new branch off develop and start from there. Separate features isolated from each other should go in their own branch. Branch names should preferably adhere to the Git Flow workflow using a `feature/FeatureName` or `hotfix/HotfixName` notation. 

When making changes, add or modify relevant tests with your changes if it involves game mechanic-related code (like calculators or services).

Once you're satisfied with your modifications, send me a pull request. I will review it, edit it as needed and merge it with the develop branch.


## Local development
 
### Setting up

##### Assumptions:

- You have [PHP](http://www.php.net/) 7.1 or higher installed and in your path.
- Your PHP has the extensions: curl, fileinfo, gd OR imagick, mbstring, openssl, pdo_sqlite and optionally [xdebug](https://xdebug.org/).
- You have [Composer](https://getcomposer.org/) installed and in your path.
- You have [NPM](https://nodejs.org/en/) 5 or higher installed and in your path.
- You have a basic understanding of the [Laravel framework](https://laravel.com/docs). See sections [deviation from Laravel](#deviation-from-laravel) and [directory structure](#directory-structure) for the current architectural setup, which slightly differs from a traditional Laravel project. 

In addition:

- If you want to use MySQL as your database engine, you have a server setup. In in doubt, just follow the instructions for Sqlite below.
- If not going to use the internal PHP webserver, you need to have a webserver like Nginx or Apache setup according to the [Laravel documentation](https://laravel.com/docs/5.6/installation#pretty-urls).

As a replacement for both of these there's Docker Compose and [Homestead](https://laravel.com/docs/5.6/homestead) configuration files available. 

##### Languages, frameworks, libraries and tools

OpenDominion is built on the Laravel 5.6 PHP framework, using PHP 7.1 as language and Laravel's Blade as view/templating language.

Composer production packages include Haikunator (to generate random realm names) and Guzzle. For development I'm using PHPUnit with Mockery for testing, Laravel Debugbar and Laravel IDE Helper as debug helper packages.

Node packages include Laravel Mix, AdminLTE dashboard theme, Font Awesome and RPG Awesome.

I'm developing OpenDominion in PhpStorm myself, but you're of course free to use whatever you see fit. But there's a partially gitignored `.idea` directory for you if you do use PhpStorm.


##### Cloning the repository:

```bash
$ git clone https://github.com/WaveHack/OpenDominion.git OpenDominion
$ cd OpenDominion
```


##### Setting up after cloning:

**Note:** The `bin/init.sh` script that was previously available has been removed. These commands will now have to be entered manually.

Install PHP dependencies:

```bash
$ composer install
```

Copy the provided .env example file and generate a fresh application encryption key.

```bash
$ cp .env.example .env
$ php artisan key:generate
```

Now is the time to decide if you want to setup a MySQL database, or use Sqlite instead.

Edit the `.env` file and set the correct `DB_*` fields. If you want to use Sqlite, set `DB_CONNECTION=local`, comment out `DB_DATABASE=` and run `touch storage/databases/local.sqlite`.

After this, migrate the database and seed development testing data:

```bash
$ php artisan migrate --seed
```

If your database is setup correctly then the migrations and seeders will run without errors, and you will receive user credentials for an automatically generated user account and dominion. 

Now [link the storage directory](https://laravel.com/docs/5.6/filesystem#the-public-disk):

```bash
$ php artisan storage:link
```

Optional: If your editor or IDE supports code inspection and autocompletion, there are some additional Artisan commands you can run to generate helper files:

```bash
$ php artisan ide-helper:generate
$ php artisan ide-helper:models -N
$ php artisan ide-helper:meta
```

Now install the frontend dependencies:

```bash
$ npm install # Optionally with --no-bin-links on mounted drives, like with Vagrant
# If using Vagrant, node-sass might fail to install properly.
# If so, run: npm rebuild node-sass --no-bin-links
```

And build the frontend:

```bash
$ npm run dev
```

Optional: You can run a self-diagnostic check to see if everything was setup correctly.

```bash
$ php artisan self-diagnosis
```

It should pass most checks and you're good to go! Note that the following checks might fail in certain conditions, which you can safely ignore:

- Locale check on Windows, which are not supported there.
- If using Sqlite, the example environmental variables not being set. Most notably `DB_DATABASE` (and optionally any other `DB_*` that are not `DB_CONNECTION`).

Run the internal PHP webserver with a helper command through Artisan:

```bash
$ php artisan serve
```

Open your web browser, navigate to [localhost:8000](http://localhost:8000) and login with the credentials provided to you after migrating and seeding the database.

If you want to tinker with stuff through the command-line with an interactive shell (i.e. a [REPL](https://en.wikipedia.org/wiki/Read%E2%80%93eval%E2%80%93print_loop)), you can run `php artisan tinker`. Note that you need to restart the tinker process every time you make a change in the code.

For more info about Artisan and Tinker, consult the [documentation](https://laravel.com/docs/5.6/artisan#introduction).

**Note:** If you want to use an SMTP server like Mailtrap.io for testing emails, change the `MAIL_*` fields accordingly in `.env`. By default emails are logged in the Laravel log file at `storage/logs/laravel*.log`. 


### Directory structure

```bash
.
+-- app
|   +-- config # Laravel config
|   +-- data # Custom folder with static data JSON files. Units, races, perks etc
|   +-- database # Laravel database
|   +-- resources # Laravel resources
|   |   +-- assets # Application Sass, JavaScript, images etc
|   |   +-- lang # Language files. Currently unused
|   |   +-- views # Blade template views
|   |       +-- emails # Email templates
|   |       +-- errors # Laravel errors
|   |       +-- layouts # Layouts
|   |       +-- pages # Page contents. Subdirectories by route segments (e.g. route('foo.bar.baz') => foo/bar/baz.blade.php)
|   |       +-- partials # Partial views to split up layouts or to reuse template blocks
|   |       +-- vendor # Vendor views. Currently unused
|   +-- routes # Laravel route config
+-- bin # init.sh and deploy.sh scripts
+-- bootstrap # Laravel bootstrap
+-- public # Web root
|   +-- assets # Generated assets folder. Don't put your resources here, put them in app/resources/ instead and update webpack.mix.js to copy them
|       +-- app # Application assets, compiled and/or copied from app/resources/
|       +-- vendor # Vendor assets, usually copied from node_modules/$library/dist
+-- src # Source files. These are pretty Laravel generic, with the addition of:
|   +-- Calculators # Calculator classes which just do calculations. No touching database or session or anything. Just input-output
|   |   +-- Dominion # Calculator classes which operate on a Dominion instance
|   +-- Factories # DominionFactory and RealmFactory
|   +-- Helpers # Helper classes which contains like building types and land types
|   +-- Models # Eloquent models
|   +-- Repositories # L5-Repositories repositories
|   +-- Services # Misc business logic classes which can touch sessions, database etc
|   +-- Traits # DominionAwareTrait
|   +-- Application.php # Custom application class to overwrite Laravel's default paths
+-- storage # Laravel storage folder. Contains an additional databases directory with local.sqlite for local development
+-- tests # Test files. Note that tests are namespaced!
    +-- Feature # Feature tests
    +-- Unit # Unit tests
        +-- Calculators # Unit tests which test a single method per test method. Don't do database testing in here, just mock everything except the test method
        |   +-- Dominion # Same as above, but with a Dominion
        +-- Factories # Factory tests, must touch database
        +-- Services # Service tests, may touch database
```

The rest should be pretty self-explanatory, assuming you're at least somewhat comfortable with the Laravel framework.


### Deviation from Laravel

OpenDominion is built with the Laravel [*framework*](https://github.com/laravel/framework), but doesn't necessarily follow the Laravel [boiler plate](https://github.com/laravel/laravel) layout.

With that said, here are some things to keep in mind if you're used to the Laravel boiler plate project code:

- Source code is in `src` instead of `app`.
- Config, database, resources and routes are in `app`.
- As a result of this, the `$app` instance is our custom application class, residing at `src/Application.php`, to override all the paths that Laravel uses by default. 


### Things to keep in mind

- The most exciting game-related code are in calculator classes (`src/Calculators`), most of which operate on a Dominion instance without any interactions with database, sessions etc, and service classes (`src/Services`).
- Classes for actions that a user takes with a dominion (e.g. exploring, invading) are called 'action services' and reside in `src/Services/Actions`.
- Misc code that doesn't belong in a calculator or factory should generally go in a service class.
- This project heavily relies on Laravel's [service container](https://laravel.com/docs/5.6/container) for all the calculator and service classes.
- Also see `AppServiceProvider.php` for this.
- There's a concept of a 'selected Dominion', which is the Dominion instance the user is currently 'playing'. A user can have multiple Dominions, but he/she can play only one at a time. It's initialized and shared to the views in the `ShareSelectedDominion` middleware.
- The `Game\TickCommand` command is executed every hour at xx:00 from the console kernel.
- Slim controllers, slim models, many slim services.


### How to run OpenDominion

To run OpenDominion you need a webserver pointing a document root towards the 'public' directory.

What I like to do during development is to use PHP's internal webserver via Artisan serve:

```bash
$ php artisan serve
```

OpenDominion uses a SQLite database by default for development, so there's no need to setup MySQL or anything PDO-compatible unless you really want to. Using things like Apache/Nginx with MySQL/MariaDB is possible at your own discretion.

**Note:** Due to hardcoded SQL queries in the [GameTickCommand class](https://github.com/WaveHack/OpenDominion/blob/master/src/Console/Commands/GameTickCommand.php), database engines other than Sqlite and MySQL are **not** supported.

Make sure the directories `bootstrap/cache` and `storage` (and every directory under `storage`) are writable.

If you run into an 'application encryption error', run the following:

```bash
$ php artisan key:generate
```


### How to run tests

You can run tests with:

```bash
$ vendor/bin/phpunit
```

There are two test suites, named as follows:

- Feature Tests
- Unit Tests

The term "should (not) touch the database" below refers to the inclusion of the Laravel `DatabaseMigrations` trait in a testcase. Using this trait allows the test to communicate with a in-memory Sqlite database. This will increase the time the test takes to run significantly and should be used with caution.

Feature tests can be seen as user stories if you're familiar with Agile. These **should** generally touch the database because of their nature.

Unit test classes are tests that generally correspond to a single source class to test the implementation of the business logic. Unit tests **may** touch the database if the class under test is interacting with the database (such as factory classes), but generally **should** not do so otherwise. Unit tests methods **should** correspond to a matching source class method under test using a `testNameOfMethodUnderTest` naming convention.

Consult [PHPUnit's manual](https://phpunit.de/manual/5.7/en/index.html) for running specific test suites or individual files.


### How to update

For updating your local development environment, do a `git pull`, optionally followed by a `composer install`, `npm install` and/or `npm run dev`, depending on which files have changed.


### How to reset

If you want to reset the database, run the following:

```bash
$ php artisan migrate:refresh --seed
```

If that doesn't work, remove the `storage/databases/local.sqlite` file, create a new one and then run:

```bash
$ php artisan migrate --seed
```

**Note:** Any registered user accounts and dominions will have to be re-registered (and activated in the case of a user account).

Edit your database manually and set `users.activated = 1` or set `MAIL_DRIVER=log` in `.env` to get the user activation link in the log (`storage/logs/laravel.log`).


### Style guide and standards

PHP code should be in PSR2-style with a few additional rules. See [.styleci.yml](https://github.com/WaveHack/OpenDominion/blob/master/.styleci.yml) for the defined [preset](https://styleci.readme.io/docs/presets#section-psr2) and [additional rules](https://styleci.readme.io/docs/fixers).

Please add relevant unit tests or feature tests if possible.


## Resources

You can find resources that I use to help me develop OpenDominion in the [resources branch](https://github.com/WaveHack/OpenDominion/tree/resources). See its readme for more info.
