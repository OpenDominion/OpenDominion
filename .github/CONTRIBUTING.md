# Contributing to OpenDominion

Thank you for considering contributing to OpenDominion! This document contains some guidelines to explain the contributing process and hopefully answer some common questions.

#### Table of Contents

- [Before getting started](#before-getting-started)
  - [Prerequisites](#prerequisites)
  - [Vision](#vision)
- [How can I contribute?](#how-can-i-contribute)
  - [Join the community](#join-the-community)
  - [Participate in the beta](#participate-in-the-beta)
  - [Provide info](#provide-info)
  - [Report bugs](#report-bugs)
  - [Collaborate with development](#collaborate-with-development)
- [Local development](#local-development)
  - [Setting up](#setting-up)
  - [Directory structure](#directory-structure)
  - [Deviation from Laravel](#deviation-from-laravel)
  - [Things to keep in mind](#things-to-keep-in-mind)
  - [How to run tests](#how-to-run-tests)
  - [How to update](#how-to-update)
  - [How to reset](#how-to-reset)
  - [Style guide and standards](#style-guide-and-standards)
- [Resources](#resources)

## Before getting started

### Prerequisites

- For code contributions, make sure you have a [GitHub account](https://github.com/signup/free).
- Make sure you read, understand and agree to the [Code of Conduct](CODE_OF_CONDUCT.md).
- Understand that the project's development is governed BDFL-style. See [Governance](GOVERNANCE.md) for more details.
- Finally, join the [OpenDominion Discord server](https://discord.gg/mFk2wZT).

The Discord is used for both for the playerbase community, as well as development-related communication, community announcements and game updates.

### Vision

OpenDominion is an open source clone of Dominion (round 70-74 ruleset), with a few key changes and improvements:

- An open source project with (hopefully) well-written, documented and (unit-)tested code.
- A modern and responsive design, developed and tested for desktop, tablet and mobile.
- Free to play forever. No premium accounts, no microtransactions, no lootboxes, and no advertisements. The project is financed through voluntary Patreon donations.
- Lifetime accounts by default. Meaning you can use the same user account across multiple rounds, instead of having to re-register for each round.
- Additional gameplay changes for balance purposes, as decided by OpenDominion's Gameplay Committee.
 
## How can I contribute?

### Join the community

While the original Dominion community back in the day was quite sizable, the OpenDominion project started a few years after Dominion closed down and the when the original community had already dwindled to a fraction of its former size. When starting OpenDominion, the goal was to code and work on the project as much as possible, before trying to gain popularity and build a community.

By time of writing, a few years of hard work have passed and significant progress has been made. Though still incomplete and under development, OpenDominion is slowly gaining players, code contributions, and people on Discord (players, developers, and people otherwise interested in the project).

As authors of the OpenDominion project, we'd like to personally invite **you** (yes you, you're reading this, after all) to join us in the journey if you're interested in the project! Which we assume you somewhat are, since you're reading this! 

It doesn't matter if you're a Dominion veteran or if you're new, feel free to:

- [Participate in the beta](#participate-in-the-beta)!
- Join the [Discord](https://discord.gg/mFk2wZT) 
- Spread the word to those you know might be interested.
- Praise the project by starring it on GitHub.

### Participate in the beta

The beta of OpenDominion is currently running at [https://beta.opendominion.net](https://beta.opendominion.net).

Feel free to register and play around! On the website homepage are links to help you get started if you're new to the game.

### Provide info

At time of creating this contributing document, quite some key information was still missing about the game. Notably how invasions worked, amongst other miscellaneous things. Nowadays most information has been recovered bit by bit, and reconstructed into either working features implemented in OpenDominion, or issues on the issue tracker.

There's still a few open issues which require investigation or are up for discussion. If possible, browse through the [issue tracker](https://github.com/WaveHack/OpenDominion/issues), look for issues with the label ["help wanted"](https://github.com/WaveHack/OpenDominion/issues?q=is%3Aissue+is%3Aopen+label%3A%22help+wanted%22) or ["discussion"](https://github.com/WaveHack/OpenDominion/issues?q=is%3Aissue+is%3Aopen+label%3A"discussion") and feel free to reply with your thoughts. Or hop onto the Discord and communicate with us there.

Not every issue that we require help with is related to coding, like [design](https://github.com/WaveHack/OpenDominion/issues/63), and some legal stuff ([terms and conditions](https://github.com/WaveHack/OpenDominion/issues/38) and [privacy policy](https://github.com/WaveHack/OpenDominion/issues/37)).

Additionally, if want to bring up a topic that you think should get looked into that's not on the issue list, don't hesitate to poke us on Discord or [create a new issue](https://github.com/WaveHack/OpenDominion/issues/new) on the issue tracker.

### Report bugs

You can report bugs to the [issue tracker](https://github.com/WaveHack/OpenDominion/issues) or on Discord in the #bug-reports channel. Since you're reading this on GitHub (probably), it's preferred to raise an issue on the tracker instead, as the Discord bug report channel is used primarily by players and most don't have a GitHub account.

Please search the issue tracker first if the particular bug already has an open issue. If it does, add your message to the existing issue instead of opening a new issue.

If a closed or resolved issue exists for your particular bug, reopen it. If in doubt, ask us on Discord, or just feel free to open a new issue anyway.

### Collaborate with development

First, make sure the changes you're going to do adhere to the [vision of OpenDominion](#vision).

Fork the repository on GitHub, make a new branch off develop and start from there. When making changes, try to add or modify relevant tests with your changes if it involves game mechanic-related code (like calculators or services).

Once you're satisfied with your modifications, raise a pull request and we will review it, edit it as needed, and merge it with the develop branch.

Most of the stuff that needs coding is domain logic, meaning you have to know the ins and outs of the game (except if you're someone who played Dominion in the past). If the project seems interesting to you and you want to help with the project, play the game and join us on Discord. If you want to learn, we (both devs and players) will gladly help you out to get accustomed to things and explain how Dominion worked.

There's also some non-domain coding issues open from time to time. We'll try to label these as "[good first issue](https://github.com/WaveHack/OpenDominion/issues?q=is%3Aissue+is%3Aopen+sort%3Aupdated-desc+label%3A%22good+first+issue%22)". This mostly consists of refactoring and UI stuff, however. If you need more info or something, feel free to request more information on the issue. Or as always, poke us on Discord.

## Local development
 
### Setting up

##### Tech stack

OpenDominion is built on the Laravel 5.7 PHP framework, using PHP 7.3 as the main programming language.

The frontend is based off the [AdminLTE](https://adminlte.io/themes/AdminLTE/index2.html) open source dashboard theme, using [Font Awesome](https://fontawesome.com/) and [RPG Awesome](https://nagoshiashumari.github.io/Rpg-Awesome/) as font icons, alongside some additional NPM packages like Select2 for improved UX. It's built with Laravel's Blade templating language to serve static HTML responses.

If you're going to use PhpStorm to tinker around in the code, there's a partially gitignored `.idea` directory in Git with some sensible project configuration.

##### Assumptions:

- You have [Docker](https://www.docker.com/) installed and in your path.
- You are on a *nix-like shell. On Windows I recommend [Git for Windows](https://gitforwindows.org/), since this process is untested on WSL is at time of updating this document.
- You have a basic understanding of the [Laravel framework](https://laravel.com/docs). See sections [deviation from Laravel](#deviation-from-laravel) and [directory structure](#directory-structure) for the current architectural setup, which slightly differs from a traditional Laravel project. 

Notes:
- Previously the installation guide required things like PHP, Composer etc, Homestead, and also Sqlite support. These have all been deprecated in favor for a Dockerized approach.

##### Steps

Clone the repository:

```bash
$ git clone https://github.com/WaveHack/OpenDominion
$ cd OpenDominion
$ git submodule update --init
```

*Note:* If you've forked the repository on GitHub, use `git clone git@github.com:YOURPROFILE/OpenDominion` instead.

*Note:* Shell scripts for the next few steps have been made for convenience. I recommend you inspect them first, so you know which commands will be ran in the next few steps.

Copy the Docker configuration files and start the Docker containers:

```bash
$ bin/01-start.sh
```

Login into the workspace container:

```bash
$ bin/02-login.sh
```

Your shell prompt should now change to: `laradock@8ea9bd9c9c56:/var/www$`, indicating you're inside the workspace container. The hostname (after `@` can vary on your system). Subsequent commands should be run inside the container. If you restart development and need to get back into the container, run `bin/02-login.sh` again.

First-time setup script:

```bash
laradock@8ea9bd9c9c56:/var/www$ bin/03-setup.sh
``` 

You're now done. Navigate to [localhost](http://localhost) and you should see the homepage of your local OpenDominion instance. You can login with the credentials provided to you after the last step.

If you want to tinker with stuff through the command-line with an interactive shell (i.e. a [REPL](https://en.wikipedia.org/wiki/Read%E2%80%93eval%E2%80%93print_loop)), you can run `php artisan tinker` from within the workspace container. Note that you need to restart the tinker process every time you make a change in the code.

For more info about Artisan and Tinker, consult the [documentation](https://laravel.com/docs/5.8/artisan#introduction).

**Note:** If you want to use an SMTP server like Mailtrap.io for testing emails, change the `MAIL_*` fields accordingly in `.env`. By default emails are logged in the Laravel log file at `storage/logs/laravel*.log`. 

### Directory structure

OpenDominion uses an experimental Laravel project structure. This *might* be reverted back in the future to use the standard Laravel project boilerplate layout (PHP source in app/, resources in the project root etc).

```bash
.
+-- app
|   +-- config # Laravel config
|   +-- data # Custom folder with JSON/YAML data files. Most notably units, races, perks and round leagues
|   +-- database # Laravel database folder
|   +-- resources # Laravel resources folder
|   |   +-- assets # Application Sass, JavaScript, images etc
|   |   +-- lang # Language files. Currently unused
|   |   +-- views # Blade template views
|   |       +-- errors # Laravel errors
|   |       +-- layouts # Layouts
|   |       +-- pages # Page contents. Subdirectories by route segments (e.g. route('foo.bar.baz') => foo/bar/baz.blade.php)
|   |       +-- partials # Partial views to split up layouts or to reuse template blocks
|   |       +-- vendor # Vendor views. Currently unused
|   +-- routes # Laravel route config
+-- bootstrap # Laravel bootstrap
+-- public # Web root
|   +-- assets # Generated assets folder. Don't put your resources here, put them in app/resources/ instead and update webpack.mix.js to copy them
|       +-- app # Application assets, compiled and/or copied from app/resources/
|       +-- vendor # Vendor assets, usually copied from node_modules/$library/dist using Webpack during 'npm run dev'
+-- src # Source files. These are pretty Laravel generic, with the addition of:
|   +-- Calculators # Calculator classes which just do calculations. No touching database or session or anything. Just input-output
|   |   +-- Dominion # Calculator classes which operate on a Dominion instance
|   +-- Factories # DominionFactory and RealmFactory
|   +-- Helpers # Helper classes which contains like building types and land types
|   +-- Models # Eloquent models
|   +-- Services # Misc business logic classes which can touch sessions, database etc
|   +-- Traits # Traits to use in other classes
|   +-- Application.php # Custom application class to overwrite Laravel's default paths
|   +-- helpers.php # Custom helper function that live in the global scope
+-- storage # Laravel storage folder. Contains an additional databases directory with local.sqlite for local development
+-- tests # Unit and feature tests
```

The rest should be pretty self-explanatory, assuming you're at least somewhat comfortable with the Laravel framework.

### Deviation from Laravel

OpenDominion is built with the Laravel [*framework*](https://github.com/laravel/framework), but doesn't necessarily follow the Laravel [boiler plate](https://github.com/laravel/laravel) layout.

With that said, here are some things to keep in mind if you're used to the Laravel boiler plate project code:

- Source code is in `src` instead of `app`.
- Config, database, resources and routes are in `app`.
- As a result of this, the `$app` instance is our custom application class, residing at `src/Application.php`, to override all the paths that Laravel uses by default. 

This is experimental and is subject to change. Please keep an eye on the #dev-announcements channel in the Discord server.

### Things to keep in mind

- The most exciting game-related code are in calculator classes (`src/Calculators`), most of which operate on a Dominion instance without any interactions with database, sessions etc, and service classes (`src/Services`).
- Classes for actions that a user takes with a dominion (e.g. exploring, invading) are called 'action services' and reside in `src/Services/Actions`.
- Misc code that doesn't belong in a calculator or factory should generally go in a service class.
- This project heavily relies on Laravel's [service container](https://laravel.com/docs/5.8/container) for all the calculator and service classes.
- Also see `AppServiceProvider.php` for this.
- There's a concept of a 'selected Dominion', which is the Dominion instance the user is currently 'playing'. A user can have multiple Dominions, but he/she can play only one at a time. It's initialized and shared to the views in the `ShareSelectedDominion` middleware.
- The `Game\TickCommand` command is executed every hour at xx:00 from the console kernel.
- Slim controllers, slim models, many slim services.

### How to run tests

Tests are ran in an in-memory Sqlite database. You need to have the `php-sqlite3` extension installed for this.

You can run the full test suite with:

```bash
laradock@8ea9bd9c9c56:/var/www$ vendor/bin/phpunit
```

**Note: The rest of this section is largely out of date as tests need refactoring. This section will be updated later.**

There are two test suites, named as follows:

- Feature Tests
- Unit Tests

The term "should (not) touch the database" below refers to the inclusion of the Laravel `DatabaseMigrations` trait in a testcase. Using this trait allows the test to communicate with a in-memory Sqlite database. This will increase the time the test takes to run significantly and should be used with caution.

Feature tests can be seen as user stories if you're familiar with Agile. These **should** generally touch the database because of their nature.

Unit test classes are tests that generally correspond to a single source class to test the implementation of the business logic. Unit tests **may** touch the database if the class under test is interacting with the database (such as factory classes), but generally **should** not do so otherwise. Unit tests methods **should** correspond to a matching source class method under test using a `testNameOfMethodUnderTest` naming convention.

Consult [PHPUnit's manual](https://phpunit.de/manual/5.7/en/index.html) for running specific test suites or individual files.

### How to update

For updating your local development environment, do a `git pull`, followed by a `bin/04-update.sh` from within the workspace container to update dependencies and rebuild frontend assets.

### How to reset

If you want to reset the database, run the following:

```bash
laradock@8ea9bd9c9c56:/var/www$ php artisan migrate:fresh --seed
```

If that doesn't work, empty your database which you use for OpenDominion, and then rerun the migrations and seeders:

```bash
laradock@8ea9bd9c9c56:/var/www$ php artisan migrate --seed
```

**Note:** Any additionally registered user accounts and dominions next to the ones provided by the database seeding process will have to be re-registered (and activated in the case of a user account).

You can activate newly created users by either:

1. Inspecting the registration mail sent (either through a SMTP server like Mailtrap.io, or fishing the activation link out of the logged email if your MAIL_DRIVER is set to 'log' in .env),
2. Use a database client and set `users.activated = 1` on the relevant user account,
3. Using `php artisan tinker` to manually set the activated field to 1:

```php
>>> $u = User::find(2);
>>> $u->activated = 1;
>>> $u->save();
```

### Style guide and standards

PHP code should be in PSR2-style with a few additional rules. See [.styleci.yml](https://github.com/WaveHack/OpenDominion/blob/master/.styleci.yml) for the defined [preset](https://styleci.readme.io/docs/presets#section-psr2) and [additional rules](https://styleci.readme.io/docs/fixers).

Please add relevant unit tests or feature tests if possible.

## Resources

You can find resources that we use to help us develop OpenDominion in the [resources branch](https://github.com/WaveHack/OpenDominion/tree/resources). See its readme for more info.
