# Structure

## About

This is a document where I write my thoughts down on how to structure the application in several ways (entity relationships, database structure, URL schemas etc).

## Foreword

I've played a bit of Dominion in the past. I was by no means an expert or a veteran of any sorts back in the days.

I'm just a developer who tries to make the digital world a better place by:
 
1. Sharing to the open source community my game/application built upon the [Laravel 5 framework](https://laravel.com/) for people (including me!) to learn, inspire and perhaps eventually collaborate,
2. Trying to re-create the original and now defunct Dominion, a unique game (especially in these days), which was enjoyed by many.

## Entities / Models

I'm going the classical MVC approach and throw all these entities below as Eloquent models into an app/Models directory. I haven't quite grasped yet on how to do it differently (with Domain-Driven Design, probably?), so I'll stick to this solution which is comfortable to me.

- **User**
*This is the entity representation of the human playing the game. Contains authorization data like login credentials and a public dispay name.*
    - **Dominion** (has many / has one per round) 
    *A dominion is the user's kingdom in the game. Dominions tie together all game related data like land, building, resources, units etc
    One dominion can exist per user per round. Rounds happen sequentially, so while the user can technically have more than one dominions, there will always no more than one active dominion per user.*
        - **Race** (has one)
        *A dominion consists of a single race.*
            - **RacePerk** (has many)
            *Race perks give bonuses (both positive and negative) to a race. This makes certain races more suited for certain tasks.*
                - **RacePerkType** (has one)
                *Normalization table because I don't want to use an enum on RacePerk.*
            - **Unit** (has many)
            *Each race has four unique units, along with a few generic units. Uniqueness comes in production cost, stats (offensive and defensive powers) and unit perks (or a lack of).*
                - **UnitPerkType** (has zero or one)
                *Unit perks come in different flavors. Each different type goes in here.*
        - **Realm** (belongs to)
        *Each dominion is placed in single realm. Dominions have alignments (good, evil, possibly neutral and other), and realms will group dominions based on alignment. No more than 15 dominions can reside in the same realm. Realms must work together to fight and ward off other realms.*
            - **Round** (belongs to)
            *A round consists of X amount of days (50 in vanilla Dominion) where users can participate with a newly created dominion to play the game. No more than one round can be active at given time. Sign-ups will start a few days before the the round starts so that everyone can start at the same time.*
                - **RoundLeague** (has one)
                *This is something I'm introducing in OpenDominion, and I'll explain why in a section below.*

### My thoughts on the user system

Vanilla Dominion had integrated the user and dominion entities I described above. When a new round started, everyone had to re-register a whole new user account.

I think that's a bit redundant. Having one user account with login and a separate functionality to 'subscribe' to a new round with practically a single click of a button is much more convenient. Statistics of specific users across rounds would also be easier to gather this way.

This could also open the possibility to add something like an achievement system, where a user could get an achievement for attending sevaral rounds. Also things like social integration (purely optional) and profile badges.

### My thoughts on the round league system

This is an idea I have I'm going to work out on low priority.

Vanilla Dominion rounds had rulesets. Either base, tech or imp. These modified some aspects of the game, drastically changing the way that rount is played.

For now, I'll just be adding a 'Standard' league, which I will try to replicate as close to the vanilla base ruleset as possible. Once everything is up and running, additional rulesets can be stored here.

Thinking out loud, eventually there could be support for things like:

- User-made rulesets, with an interface to setup or generate such ruleset (based on input parameters), which could be ran based on a voting system, perhaps,
- An AI/bot ruleset, where a REST API would be available during the round to developers so they can write bots to control their Dominion and its actions. Because let's face it, with games like these there is always going to be notorious botting and scripting to automate actions to gain advantages. Why not condone these actions into their own league to see who can write the best bot? These leagues could also run alongside regular leaues, purely for people who would like a different approach to the game. Also building APIs is cool.

## URLs

General:

- /
- /about

Authorizationx:

- /auth/login
- /auth/register
- /auth/logout
- /account *view/change account data and global site settings*

Social:

- /user/:user_id *user profile page*
- /board/ **global messaging board / forum index*
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
