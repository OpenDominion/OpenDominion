@extends('layouts.topnav')

@section('content')
    @include('partials.scribes.nav')
    <div class="row">
        <div class="col-md-3">
            <div class="box box-primary">
                <div class="box-body table-responsive no-padding">
                    <ul class="nav">
                            <li><a href="#game-overview">Game Overview</a></li>
                            <li><a href="#status">Status</a></li>
                            <li><a href="#advisors">Advisors</a></li>
                            <li><a href="#daily-bonus">Daily Bonus</a></li>
                            <li><a href="#explore">Explore</a></li>
                            <li><a href="#construct">Construct</a></li>
                            <li><a href="#rezone">Rezone</a></li>
                            <li><a href="#improvements">Improvements</a></li>
                            <li><a href="#national-bank">National Bank</a></li>
                            <li><a href="#technology">Technology</a></li>
                            <li><a href="#military">Military</a></li>
                            <li><a href="#government">Government</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border text-center">
                    <h3 class="box-title">Game Overview</h3>
                </div>
                <div class="box-body" id="scribes">
                    <div id="game-overview">
                        <p>Dominion is a medieval strategy persistent browser-based game. You take control of a plot of land, called a "Dominion", that is within a Realm, each with a unique identification number, called "Realm Number". The other Dominions in your realm are your allies, and the ultimate goal of Dominion is to be the biggest, strongest Realm in all of the land. Individually, you are working both towards that goal and to become the biggest, strongest Dominion. The better you coordinate your efforts with the rest of your Realm, the better of you are at completing both of those goals. In order to accomplish this, you must carefully balance the needs of your dominion and work closely with your realm mates.</p>
                        <p>Accomplishing those feats involve understanding all the facets of the game. When you create your account, you must select a race for you and your people. The good races include Humans, Dwarves, Wood Elves, Gnomes, Merfolk, Firewalkers, Sylvans and Halflings. The evil races span Icekin, Trolls, Goblins, Dark Elves, Kobolds, Lycanthropes, Lizardfolk, The Nox and the Undead. Each race has a unique set of bonuses and military units that are important to understand.</p>
                        <p>Your land does you little good until you have placed buildings on them. There are 7 unique land types (Plain, Mountain, Hill, Cavern, Water, Forest, and Swamp) and you may only place certain buildings on any given land type. You can, however, rezone your lands to convert one land type into any other land type. Once you have the right land for the buildings you would like to create, platinum (the currency of the world) and lumber may be spent to purchase the building.</p>
                        <p>Your castle, while not necessarily something you need to build up, is something that is integral to success later in the game. By improving your castle, you get added bonuses in a number of areas, including more population per acre, military strength, wizard strength, and income. These things can make or break a successful military operation or spell. Added income, on the other hand, is something you can always use, if not to build on your land, then to train your military.</p>
                        <p>Each race has a unique military. They all have an offensive specialist that provides no defense, and a defensive specialist unit that provides no offense. All races have 2 unique elite units that have special defensive and offensive powers, along with possibly other added special abilities. Each unit has different costs per race, and being able to calculate your offensive power versus a target's defensive power is key to succeeding in any battle and defending yourself from a possible invasion.</p>
                        <p>Magic and Espionage are incredibly useful within the confines of the game. While neither will directly gain you land, they will allow you to do 2 important things: 1) Gain a slew of information regarding your enemies, including detailed analysis of their lands, military, and castle & 2) Hurt your enemies in ways other than direct military force.</p>
                        <p>How Things Work: Production (Platinum, Lumber, Gems, Ore), Population Gain, Drafting of military, Training, and just about everything else in Dominion occurs on the hour, every hour. What does this mean, exactly? Things occur in your Dominion whether you log in every hour or not. It doesn't mean that you have to log in every hour. "Turns" occur whether you log in to play every hour, or if you log in to play once a day. While it is to your advantage to log in every hour to spend your Dominion's resources to grow at the fastest rate possible, it is not necessary to do so. Most major actions, such as construction, training, and attacking take 9 hours to 12 hours to complete. This means after you, for example, attack another Dominion, your troops will not be available to attack again for another 12 hours. Thus, playing the game multiple times a day is helpful, but not integral to success. Some players have great success and compete at the highest levels of dominion while only logging in only a few times a day, but most competitive players enjoy quick logins throughout the day to optimize their Dominion.</p>
                        <p>Your economy is the key to your success. Optimization of your economy is essential for growth. Platinum is primarily used to train troops to attack and defend, and to explore, two of the more important things you will be doing in Dominion. The two primary ways in which you generate platinum is through employment of your peasants and alchemies. Each employed peasant generates 2.7 platinum per hour for your dominion. Ore is used primarily in the training of certain types of troops, and sometimes for investment into your castle. You gain ore primarily through ore mines. Lumber is used primarily for construction of buildings, and also sometimes for investment into your castle. Lumber is gained primarily through lumberyards. Gems are used only for castle investment, and are generated from diamond mines. Food is essential for your military and peasantry to eat, and is generated by farms and docks.</p>
                        <p>Bonuses for production, military, magic and espionage depend on things like your castle, your race, and the buildings you put on your lands. Please note that Bonuses are cumulative and are then applied to the target value before the final numbers are calculated. Of note is that the prestige population bonus is a multiplicative bonus, not an additive bonus, though the prestige attack bonus is additive. Also, there is a 50% cumulative platinum production bonus maximum.</p>
                        <p>You should also be aware that you will be assailed with game information in the following pages, and it takes some time to fully understand the game and every little detail. Use these scribes as a reference you can go back to again and again to remind yourself of some of these details.</p>
                    </div>
                    <div id="status">
                        <h4 class="text-center">Status</h4>
                        <p>The Status screen offers you a quick view of some general information regarding your dominion, resources currently available for spending and trained military. It is also home to the 'Recent News' section which shows notifications for events in your dominion which can be configured in your user settings. There is also a notification "bell" at the top of every screen that has new notifications. The type of events that trigger notifications (from units being trained to having been the victim of a fireball frenzy) can be selected in your user settings (on the "notifications" tab).</p>
                    </div>
                    <div id="advisors">
                        <h4 class="text-center">Advisors</h4>
                        <p>There are several advisors available to your dominion that inform you of your progress in various fields:</p>
                        <ul>
                            <li>Op Center   - The overview of your dominion as you would see it in the Op Center including buttons to easily copy your military into the game's calculators and export your data in the JSON format.</li>
                            <li>Production 	- Provides information on hourly resource generation, resource spending over the course of the round as well as a population and job breakdown.</li>
                            <li>Military 	- Lists military bonuses and statistics with additional overviews regarding units in training and returning from battle.</li>
                            <li>Magic		- Tells you the spells currently affecting your dominion, their remaining duration and the caster (if known in case of offensive spells).</li>
                            <li>Rankings	- Compare how you are doing in all of the game's different rankings.</li>
                            <li>Statistics	- Reports on your spies' and wizards' prowess throughout the round.</li>
                        </ul>
                    </div>
                    <div id="daily-bonus">
                        <h4 class="text-center">Daily Bonus</h4>
                        <p>This is where you can take your free bonuses of 20 barren acres of your home land type and 4x the number of peasants in platinum once each day. Taking your daily platinum bonus also awards 750 research points. Note that the daily bonus resets at 1800 or midnight UTC (alternates each round), and the hours remaining to take the daily bonus is always displayed on the side bar. You can also find links to the game's Discord server and ways to support the game here.</p>
                    </div>
                    <div id="explore">
                        <h4 class="text-center">Explore Land</h4>
                        <p>Exploration allows you to peacefully grow your dominion without having to rely on invading other dominions for land gain. Exploration can be performed at a rising platinum and draftee cost based on your current total land size. You can freely decide which land types to explore for and it takes 12 hours for the exploration to complete. Exploration also lowers your dominions morale, which has a slight effect on your military power.</p>
                    </div>
                    <div id="construct">
                        <h4 class="text-center">Construct</h4>
                        <p>Land gained through invasion, exploration or taking your daily bonus arrives barren and as a specific land type and can be built on. It takes 12 hours to construct a building. Depending on the land type there are several options of different buildings to choose from. Learn more about what each building does by hovering over the building name or from the table down below. Constructing buildings on your land entails a cost in platinum and lumber which increases as you acquire more buildings.Construction cost can be reduced by factories (building), certain techs and some races have racial perks to reduce the cost of construction. Furthermore you can build conquered acres (from a >= 75% hit) at a discounted rate. Also, if you have lost built acres in an invasion you gain reduced cost buildings, but note that those bonuses can not both be used on the same buildings.</p>
                    </div>
                    <div id="rezone">
                        <h4 class="text-center">Re-Zone</h4>
                        <p>Rezoning is the process of converting barren land of one type into barren land of another type. Each building can only be built on one particular type of land, and some units have their military power based on how much of a certain land type your dominion is composed of. Rezoning land processes instantly. Rezoning costs can be reduced by factories (building), certain techs and certain races have racial perks and/or spells that reduce the cost of rezoning.</p>
                    </div>
                    <div id="improvements">
                        <h4 class="text-center">Improvements</h4>
                        <p>Improvements offer a way to permanently increase certain parts of your dominion by investing resources into your castle:
                        <ul>
                            <li>Science	    - Increases platinum production up to a maximum of +20% production.</li>
                            <li>Keep		- Increases population maximum up to +30% maximum population.</li>
                            <li>Towers		- Increases wizard power, mana production and reduces spell damage taken up to a maximum of 60% each.</li>
                            <li>Forges 	    - Increases offensive military power by up to +30%.</li>
                            <li>Walls		- Increases defensive military power by up to +30%.</li>
                            <li>Harbor		- Increases food production from all sources and boat production and protection from docks by up to +60%.</li>
                        </ul>
                        <p>These bonuses can be increased by the Masonry building (no limit). Masonries provide a bonus on what has already been invested, while some races and wonders have bonuses that are applied when you are investing. Improvements never go away (unless you are the victim of Lightning Bolt spells), but as you grow larger the bonuses will diminish.</p>
                        <p>The Improvements page displays your modified castle improvements. Investing into your castle is processed and the bonuses are provided instantly. Only platinum, ore, lumber and gems may be invested into your castle at the following rates:<br/>Each platinum is worth 1 point, ore and lumber are each worth 2 points and gems are worth 12 points.</p>
                        <p>Some races and wonders have an investment bonus for these values. Masonries are the only way to increase the bonus after investments have already occurred.</p>
                    </div>
                    <div id="national-bank">
                        <h4 class="text-center">National Bank</h4>
                        <p>The National Bank allows you to exchange resources with the empire. Exchanging resources processes instantly.</p>
                        <p>
                            Platinum, lumber and ore trade 2 for 1.<br/>
                            Gems trade 1:2 platinum, lumber or ore.<br/>
                            Food sells for 4 platinum, lumber or ore, or 1 gem.
                        </p>
                        <p>The exchange rate can be improved by the banker's friend tech and the Great Market (wonder).</p>
                    </div>
                    <div id="technology">
                        <h4 class="text-center">Technology</h4>
                        <p>Technological advancements offer a way to add permanent upgrades to your dominion. Each tech costs 9500 research points (+100 after each unlock), which can be earned through building schools, invading other dominions and taking your daily platinum bonus. Most techs require you to have taken certain other techs before them, which is outlined in the tech tree below.</p>
                    </div>
                    <div id="military">
                        <h4 class="text-center">Military</h4>
                        <p>The Military screen is where you can train and release the 4 different types of military units as well as spies and wizards, each of which takes different resources (platinum, ore, lumber and/or mana) and 1 draftee to train. Archmages come at a raw cost of 1,000 platinum and 1 trained wizard. Draftees are drafted from your peasant population at a rate of 1% per hour as long as your target draft rate is set higher than your current military. Your military units are divided among the 4 different types of units:</p>
                        <ul>
                            <li>Offensive specialist	- Cheap units with no defensive value</li>
                            <li>Defensive specialist	- Cheap units with no offensive value</li>
                            <li>First & second elite	- Higher cost units which usually provide higher efficiency at a higher cost than specialist units (with some exceptions).</li>
                        </ul>
                        <p>The Military screen also includes tables on units in training and units/resources/land incoming from recent invasions.</p>
                    </div>
                    <div id="government">
                        <h4 class="text-center">Government</h4>
                        <p>This is where you can cast your vote for the Monarch of your realm. The Monarch has a few special powers: they can set the Realm Name and Message of the Day (the message that appears on the Status screen); they can declare War on other realms using this tab, and they can delete posts in the in-game Council. To cast your vote, select the Dominion you want to vote for from the dropdown menu and click on 'Vote'.</p>
                        <p>You can also share your advisors with others in your realm here. You do so by checking the box in the 'Advisors' column. If you do that, those who you have shared the information can also see everything you can see in your 'Advisors' tab.</p>
                        <p>This is also where you can apply for the Emperor's Royal Guard. Members of the Royal Guard must pay 2% of their platinum production per hour, in exchange for some protection. As a member, you cannot take any action against Dominions that are less than 60 % of your land size or greater than 166 % of your land size, nor can Dominions outside that range take any action against you. You can also not attack any wonders, either with your Military or with the Cyclone spell. Once you are in the Royal Guard, you can apply for the Elite Guard, which has even stricter range limitations: you will only be able to take actions against Dominions within 75 % to 133 % of your land size. The Elite Guard imposes an additional 25 % cost increase on exploration. Both guards take 24 hours to join, and once you have joined, you cannot leave for 48 hours. During the application process, doing any hostile actions against a Dominion that is not inside the range of the guard you are joining will reset your application, as will attacking a Wonder. It is prohibited to join the Guard until the start of Day 3.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('page-styles')
    <style type="text/css">
        #scribes h4 { margin-top: 15px; }
        #scribes p { text-indent: 20px; }
    </style>
@endpush
