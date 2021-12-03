@if ($selectedDominion->round->discord_guild_id && $selectedDominion->realm->number != 0)
    <div class="box">
        <div class="box-header with-border">
            <h3 class="box-title">Team Play</h3>
        </div>
        <div class="box-body text-justify">
            <p>Open Dominion is intended to be played as a team game. For the best social team play experience, join us on Discord. There is a helpful community of players, particularly your realmies, who can teach and guide you.</p>
            <div class="text-center">
                <a href="{{ $discordHelper->getDiscordConnectUrl('join') }}" target="_blank" class="btn btn-primary">
                    <i class="ra ra-speech-bubbles"></i> Join Realm Discord
                </a>
            </div>
        </div>
    </div>
@endif
