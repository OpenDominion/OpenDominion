# Contributing to OpenDominion

Thank you for considering contributing to OpenDominion. This document contains some guidelines to explain the contributing process and hopefully answer some common questions.

Do note that almost nothing is set in stone. Feel free to even contribute to this document!


#### Table of Contents

- [Before getting started](#before-getting-started)
  - [Prerequisites](#prerequisites)
  - [Vision](#vision)
- [How can I contribute?](#how-can-i-contribute)
  - [Providing info](#providing-info)
  - [Reporting bugs](#reporting-bugs)
  - [Making changes](#making-changes)
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

- Make sure you have a [GitHub account](https://github.com/signup/free)
- Make sure you read, understand and agree to the [Code of Conduct](CODE_OF_CONDUCT.md)

Collaboration and contributing will be primarily done through GitHub.

If possible, join the [OpenDominion Gitter chat](https://gitter.im/opendominion/Lobby) for asking questions and a general easy method of communication.


### Vision

OpenDominion aims to be an open source clone of Dominion (round 70 ruleset), with a few changes and improvements:

- An open source project with well-written, documented and (unit-)tested code
- A modern and responsive design, developed and tested for desktop, tablet and mobile
- Free to play. No premium accounts or features
- No advertisements
- Lifetime accounts by default

This means that pretty much all of vanilla Dominion's content (land, buildings, races, units, spells, wonders etc) and mechanics *are* set in stone.
 
 
## How can I contribute?

### Providing info

The original Dominion has been dead for quite a while now. Even links and resources like [The Dominion Encyclopedia](http://dominion.lykanthropos.com/wiki/) and [RedFox Dominion Protection Simulator](http://dominion.lykanthropos.com/DomSim/) are broken and slowly deteriorating over time. The IRC channels are empty and owners and maintainers of the aforementioned resources are nowhere to be found.

Even though I've played Dominion myself, I can't remember how everything looked or worked. If you're a veteran player, please get in contact with me and share any knowledge, screenshots/drawings, info or anything relevant to the original Dominion that you want to share! I started this project alone, but I need **your** help to make it the great game it once was.


### Reporting bugs

You can report bugs to the [issue tracker](https://github.com/WaveHack/OpenDominion/issues).

Please search the issue tracker first if the particular bug already has an open issue. If it does, add your message to the existing issue instead of opening a new issue.

If a closed or resolved issue exists for your particular bug, reopen it. If in doubt, just open a new issue.


### Making changes

First, make sure the changes you're going to do adhere to the [vision of OpenDominion](#vision).

Fork the repository on GitHub. Then, based on the size of the change, either make your changes in the ~~develop~~ master branch (small changes) or create a new branch (big changes). If in doubt, just create a new branch. Branch names should highly preferably adhere to the Git Flow workflow using a `feature/FeatureName` or `hotfix/HotfixName` notation. 

When making changes, preferably add tests with your changes if it involves game mechanic-related code (like calculators or services).

Once you're satisfied with your modifications, send me a pull request. I will review it, edit it as needed and merge it with the development branch.


## Local development
 
### Setting up

##### Assumptions:

- You have PHP 5.6 (or greater) installed and in your path
- Your PHP has the extensions: curl, mbstring, openssl, pdo_sqlite and optionally xdebug
- You have composer installed and in your path
- You have npm and yarn installed and in your path
- You have a basic understanding of the [Laravel framework](https://laravel.com/docs). See sections [deviation from Laravel](#deviation-from-laravel) and [directory structure](#directory-structure) for the current architectural setup, which slightly differs from a traditional Laravel project. 


##### Languages, frameworks, libraries and tools

OpenDominion is built on the Laravel 5.4 PHP framework, using PHP 5.6 as language and Laravel's Blade as view/templating language.

Composer production packages include L5-Repository (for model repositories), Haikunator (to generate random realm names) and Guzzle. For development I'm using PHPUnit with Mockery for testing, Laravel Debugbar and Laravel IDE Helper as debug helper packages.

Node packages include Laravel Mix, AdminLTE dashboard theme, Font Awesome and RPG Awesome.

I'm developing OpenDominion in PhpStorm myself, but you're of course free to use whatever you see fit. But there's a `.idea` directory for you if you do use PhpStorm.


##### Cloning the repository:

```bash
$ git pull https://github.com/WaveHack/OpenDominion.git OpenDominion
$ cd OpenDominion
```


##### Init script

There's an [init script](https://github.com/WaveHack/OpenDominion/blob/master/bin/init.sh) available which will set up the rest: 

```bash
$ bash bin/init.sh local
```

If you don't want to use my awesome init script, you can enter these commands manually instead:

```bash
# Composer stuff
$ composer self-update
$ composer install --prefer-source

# Env file
$ cp .env.template.local .env
$ php bin/artisan key:generate

# Database
$ touch app/storage/local.sqlite
$ php bin/artisan migrate --seed

# Optional IDE helpers
$ php bin/artisan clear-compiled
$ php bin/artisan ide-helper:generate
$ php bin/artisan ide-helper:models -N --dir="src/Models"
$ php bin/artisan ide-helper:meta
$ php bin/artisan optimize

# Frontend stuff
$ yarn install
$ npm run dev
```

Make sure to change the `MAIL_*` settings in your `.env` if you want to use your own SMTP server (or just set `MAIL_DRIVER` to `log`). 


### Directory structure

```bash
.
+-- app
|   +-- bootstrap # Laravel bootstrap
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
|   +-- storage # Laravel storage folder. Contains an additional databases directory with local.sqlite for local development
+-- bin # Artisan, init.sh and deploy.sh scripts
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
+-- tests # Test files. Note that tests are namespaced!
    +-- Feature # Feature tests
    +-- Unit # Unit tests
        +-- Calculators # Unit tests which test a single method per test method. Don't do database testing in here, just mock everything except the test method
        |   +-- Dominion # Same as above, but with a Dominion
        +-- Factories # Factory tests, must touch database
        +-- Services # Service tests, may touch database
```

The rest should be pretty self-explanatory, assuming you're at least somewhat comfortable with Laravel.


### Deviation from Laravel

- Artisan is in `bin`: `$ php bin/artisan [command]`
- Source code is in `src` instead of `app`
- Bootstrap, config, database, resources, routes and storage are in `app`
- As a result of this, the `$app` instance is our custom application class, residing at `src/Application.php`, to override all the paths that Laravel uses by default. 


### Things to keep in mind

- The most exciting game-related code are in calculator classes (`src/Calculators`), most of which operate on a Dominion instance, and service classes (`src/Services`).
- Classes for actions that a user takes with a dominion (e.g. exploring, invading) are called 'action services' and reside in `src/Services/Actions`.
- Misc code that doesn't belong in a calculator or factory should generally go in a service class.
- This project heavily relies on Laravel's [service container](https://laravel.com/docs/5.4/container) for all the calculator and service classes. There's a circular dependency issue between calculator classes, which is circumvented with the `DependencyInitializableInterface` interface. Bad coding patterns, I know! If someone can educate me how to do this specific scenario better then I'll gladly refactor it.
- Also see `AppServiceProvider.php` for this.
- There's a concept of a 'selected Dominion', which is the Dominion instance the user is currently 'playing'. A user can have multiple Dominions, but he/she can play only one at a time. It's initialized and shared to the views in the `ShareSelectedDominion` middleware.
- The `GameTickCommand` command is executed every hour at xx:00 from the console kernel.
- Slim controllers, slim models, many slim services. 


### How to run OpenDominion

To run OpenDominion you need a webserver pointing a document root towards the 'public' directory.

What I like to do during development is to use PHP's internal webserver via Artisan serve:

```bash
$ php bin/artisan serve
```

OpenDominion uses a SQLite database by default for development, so there's no need to setup MySQL or anything PDO-compatible unless you really want to. Using things like Apache/Nginx with MySQL/MariaDB is possible at your own discretion.

Due to hardcoded SQL queries in the [GameTickCommand class](https://github.com/WaveHack/OpenDominion/blob/master/src/Console/Commands/GameTickCommand.php), database engines other than Sqlite and MySQL are **not** supported.

Make sure the directories `app/bootstrap/cache` and `app/storage` are writable.


### How to run tests

You can run tests with:

```bash
$ vendor/bin/phpunit
```

There are two test suites, named as follows:

- Feature Tests
- Unit Tests

The term "should (not) touch the database" below refers to the inclusion of the Laravel `DatabaseMigrations` trait in a testcase. Using this trait allows the test to communicate with a in-memory Sqlite database. This will increase the time the test takes to run significantly and should be used with caution.

Feature tests can be seen as user stories if you're familiar with Agile. These **should** generally touch the database because of its nature.

Unit tests are tests that generally correspond to a single source class to test the implementation of the business logic. Unit tests **may** touch the database if the class under test is interacting with the database (such as factory classes), but generally **should** not do so otherwise. Unit tests methods **should** correspond to a matching source class method under test using a `testNameOfMethodUnderTest` naming convention.

Consult PHPUnit's manual for running specific test suites or individual files.


### How to update

For updating your local development environment, do a `git pull`, optionally followed by a `composer install`, `yarn install` and/or `npm run dev`, depending on which files have changed.


### How to reset

If you want to reset the database, run the following:

```bash
$ php bin/artisan migrate:refresh --seed
```

If that doesn't work, remove the `app/storage/databases/local.sqlite` file, create a new one and then run:

```bash
$ php bin/artisan migrate --seed
```

Note that any registered user accounts and dominions will have to be re-registered (and activated in the case of a user account).

Edit your database manually and set `users.activated = 1` or set `MAIL_DRIVER=log` in `.env` to get the user activation link in the log (`app/storage/logs/laravel.log`).


### Style guide and standards

PHP code should be in PSR2-style with a few additional rules (todo: add and link to .styleci.yml - just look at existing files for now :^) ).

Please add relevant unit tests or feature tests if possible.


## Resources

You can find resources that I use to help me develop OpenDominion in the [resources branch](https://github.com/WaveHack/OpenDominion/tree/resources). See its readme for more info.
