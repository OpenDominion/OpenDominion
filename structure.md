# Structure

## About

This is a document where I write my thoughts down on how to structure the application in several ways (entity relationships, database structure, URL schemas etc).

## Foreword

I've played a bit of Dominion in the past. I was by no means an expert or a veteran of any sorts back in the days. I just enjoyed playing it as a casual player and was sad to see it go.

My goal with this project is to make the digital world a better place by:
 
1. Sharing to the open source community my game/application built upon the [Laravel 5 framework](https://laravel.com/) for people (including me!) to learn, inspire and perhaps eventually collaborate,
2. Trying to re-create the original and now defunct Dominion, a unique game (especially in these days), which was enjoyed by many.

I am not a designer and for the time being I'll be throwing something relatively simple together in [Bootstrap](https://getbootstrap.com/) with [SB Admin 2](http://startbootstrap.com/template-overviews/sb-admin-2/). If anyone wants to collaborate for designing it, feel free to contact me.

## Entities / Models

I'm going the classical MVC approach and throw all these entities below as Eloquent models into an app/Models directory. I haven't quite grasped yet on how to do it differently (with Domain-Driven Design, probably?), so I'll stick to this solution which is comfortable to me.

### Dominion

A dominion is the user's kingdom in the game. Dominions tie together all game related data like land, buildings, resources, units etc. Only one dominion can exist per round per player.

Has one **Race**
Has one **Realm**
Has many (4) **Units** through **Race**

### Race

Dominions consist of a certain race. Races can be good, evil, neutral or other.

Has many **Dominions**
Has many **RacePerks**
Has many (4) **Units**

### RacePerk

Race perks give bonuses (both positive and negative) to a race. This makes certain races more suited to certain tasks than other races.

Has many **Races**
Has one **RacePerkType**

### RacePerkType

Normalization table for race perk types.

Has many **Races**

### Realm

Each dominion is placed in a single realm based on alignment. Realms can either be good or evil, consisting of dominions of that race alignment, along with neutral race dominions. Realms must work together to fight other realms.

Has many **Dominions**
Has one **Round**

### Round

A round consists of X amount of days (50 in vanilla Dominion) where users can participate with a newly created dominion to play the game.

No more than one round can be active at any given time per league.

Has many **Dominions** through **Realms**, all the dominions currently playing in this round
Has many **Realms**, all the realms where the playing dominions reside in
Has one **RoundLeague**

### RoundLeague

League type used to differentiate rounds. See below for explanation.

Has many **Rounds**

### Unit

Each race has four unique units, along with a few generic units. Generic units are hardcoded, while race-specific units are stored here.

Has one **Race**
Has one **UnitPerkType**

### UnitPerkType

Table to store different kinds of unit perk types.

Has many **UnitPerks**

### User

The entity representation of the human playing the game. Contains authorization data like login credentials and a public display name.

Has many **Dominions**, but only one per round

## My thoughts on ...

### User system

Vanilla Dominion had one single entity for both the user and dominion entities I described above. When a new round started, everyone had to re-register a whole new user account.

I think that's a bit redundant. Having one user account with login and a separate functionality to 'subscribe' to a new round with practically a single click of a button is much more convenient. Statistics of specific users across rounds would also be easier to gather this way.

This could also open the possibility to add something like an achievement system, where a user could get an achievement for attending sevaral rounds. Also things like social integration (purely optional) and profile badges.

### Round League system

This is an idea I have I'm going to work out on low priority.

Vanilla Dominion rounds had rulesets. Either base, tech or imp. These modified some aspects of the game, drastically changing the way that rount is played. A league is pretty much a ruleset which can have different game variables and logic.

For now, I'll just be adding a 'Standard' league, which I will try to replicate as close to the vanilla base ruleset as possible. Once everything is up and running, additional rulesets can be stored here.

Thinking out loud, eventually there could be support for things like:

- Tech and Imp leagues, based on the vanilla Dominion rulesets.
- User-made rulesets, with an interface to setup or generate such ruleset (based on input parameters), which could be ran based on a voting system, perhaps.
- An AI/bot ruleset, where a REST API would be available during the round to developers so they can write bots to control their Dominion and its actions. Because let's face it, with games like these there is always going to be notorious botting and scripting to automate actions to gain advantages. Why not condone these actions into their own league to see who can write the best bot? These leagues could also run alongside regular leagues, purely for people who would like a different approach to the game. Also building APIs is cool.

### Round duration

Vanilla Dominion rounds last for approximately 50 days. I'm thinking of a system to have rounds last 25 days, so that I can line them up with the start of the month. A 25-day round would consist of the following:

1. A round starts always on the first day of each month, lasting 25 days.
2. After the 25th day, scores will be calculated and added to their respective dominions and users. Sign-ups for the current round will close, but the round doesn't end yet and people can continue to do actions like invasion, espionage and magic to allow for some crazy mayhem like suicide missions. Optionally all exploration, construction and re-zoning is disabled after the 25th day.
3. Registration for the new round opens up on the last X days of the month, where X is three, probably. During this time, the round will close and the current round gameplay data will be archived.
4. Non-critical software updates could be deployed between the 25th day and the -3th day of the current and next month, allowing for a few days of live testing and fixing issues before the next round starts on the first day of the next month.
 
Obviously, vanilla Dominion is balanced for 50-day rounds. Once I implement a system like this, it will probably run alongside the standard/vanilla leagues for those looking for shorter games. Gameplay variables will also need to be adjusted due to balancing reasons.

I won't be building this just yet, of course. It will be on a low priority queue, along with different round leagues. 

## URLs

General:

- /
- /about

Authorization:

- /auth/login
- /auth/register
- /auth/logout
- /account *view/change account data and global site settings*

Social:

- /user/:user_id *user profile page*
- /board/ *global messaging board / forum index*
- /board/(:category_id)
- /board/thread/(:thread_id)

Gameplay:

Prefixed with `/game|play|round/(:round_id)`. URL round identifier could either be round->id or a combination of a roundleauge letter followed by the round number (e.g. 'S15' for standard league, round 15). 

- /status
- /advisors/(production|military|etc)
- /explore
- /construction
- /rezone-land
- /improvements
- /bank
- /military
- /invade
- /magic
- /espionage

todo

## Database Schema

todo

## Old stuff

User
    has many (but one active) Dominion
        belongs to one Race (has many dominions)
            has many RacePerk
                has one RacePerkType
            has many (4) Unit
                has one (0-1) UnitPerkType
        belongs to one Realm
            belongs to one Round
                has one RoundLeague



Dominion
- id
- user_id
- round_id?
- realm_id
- race_id
- name
- prestige

- peasants
- peasant_change_last_hour

- draft_rate
- morale

- resource_platinum
- resource_food
- resource_lumber
- resource_mana
- resource_ore
- resource_gems
- resource_tech
- resource_boats

- improvement_*

- military_draftees
- military_unit1-4
- military_spies
- military_wizards
- military_archmages
- wizard_strength

- land_plain
- land_*

- building_*

- daily_land
- daily_plat

- timestamps
* unique (user_id, round_id)

    Race
    - id
    - name
    - alignment (enum good,evil,neutral,other)
    - home_land_type (enum plain,mountain,swamp,cavern,forest,hill,water)
    - timestamps

    RacePerk (RaceRacePerkType?)
    - race_id
    - raceperktype_id
    - value
    - timestamps
    * unique (race_id, raceperktype_id)

    RacePerkType
    - id
    - key
    - description
    - timestamps
    * unique (key)

QueueConstruction
- id
- timestamps

QueueExploration
- id
- timestamps

Realm
- id
- monarch_dominion_id
- enum type (good, neutral, evil)
- number
- name
- timestamps

    Round
    - id
    - roundleague_id
    - timestamps

    RoundLeague
    - id
    - name (Standard, Bot (/w api access), other vanilla Dom rulesets)
    - timestamps

Unit
- id
- race_id
- slot (enum 1,2,3,4)
- name
- cost_platinum
- cost_ore
- power_offense
- power_defense
- unit_perk_id
- unit_perk_values
- timestamps
* unique (race_id, slot)

UnitPerkType
- id
- key
- description
- timestamps
* unique (key)

    User
    - id
    - email
    - password
    - display_name
    - remember_token
    - active (0,1)
    - activation_code
    - last_online
    - timestamps
    * unique (email)
