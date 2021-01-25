@extends('layouts.topnav')

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        About the Project
                    </h3>
                </div>
                <div class="box-body">
                    <p>OpenDominion is a free and open source remake of Dominion from Kamikaze Games, which ran from 2000 to 2012 before stopping indefinitely.</p>

                    <h4>Vision Statement</h4>
                    <ul>
                        <li>Always free, always open source.</li>
                        <li>Remain true to the spirit of the original game while improving player experience.</li>
                        <li>Support and encourage a variety of playstyles.</li>
                        <li>Grow the playerbase.</li>
                    </ul>

                    <h4>Gameplay Committee</h4>
                    <p>The GPC is a group of players elected by their peers to make informed and thoughtful decisions about game balance, which can then be implemented at the administrators' discretion. Members are tasked with communicating to the playerbase what changes have been proposed, gathering feedback, and finalizing changes for each round based on relative consensus within the community.</p>
                    <p>New members are voted on by the community every few rounds as time and interest allows. The administrators may occasionally appoint new members, but appointed membership should never hold a majority. Members should treat the community and each other with respect, or face removal by the administrators.</p>

                    <h4>Game Balance</h4>
                    <p>The primary objective of the game is to gain land. This can be done by attacking, exploring, or a combination of the two (converting).</p>
                    <p>Balance changes should meet at least one of these criteria:</p>
                    <ul>
                        <li>Better balances the aforementioned three basic playstyles, so that each is viable to win a round (though individuals may disagree about the likelihood of each).</li>
                        <li>Adjusts the power level of individual races/mechanics/strategies to maximize strategic diversity.</li>
                        <li>Discourages abusive behavior that would violate the rules or harm player retention.</li>
                        <li>Reduces the learning curve for new players.</li>
                        <li>Keeps the game fresh for existing players by shaking things up on occasion.</li>
                    </ul>
                    <p>Proposals for a change can be presented by any player. A proposal should, at minimum, clearly explain the reasoning for the change. If possible, it should also show how it affects other aspects of the game and/or present charts or other visual reference so that the outcome can be more easily understood. Relevant game data from previous rounds can be requested from the admins. Proposals requiring non-trivial dev work should be presented well before the end of the current round or they may not be considered for the following round.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
