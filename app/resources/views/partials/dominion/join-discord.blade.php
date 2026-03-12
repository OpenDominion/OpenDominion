@if ($selectedDominion->round->discord_guild_id && $selectedDominion->realm->number != 0 && $selectedDominion->realm->getSetting('usediscord') !== false)
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Team Play</h3>
        </div>
        <div class="card-body text-justify">
            <p>Open Dominion is intended to be played as a team game. For the best social team play experience, join us on Discord. There is a helpful community of players, particularly your realmies, who can teach and guide you.</p>
            <div class="text-center">
                <a href="{{ $discordHelper->getDiscordConnectUrl('join') }}" target="_blank" class="btn btn-primary">
                    <i class="ra ra-speech-bubbles"></i> Join Realm Discord
                </a>
            </div>
        </div>
    </div>
@endif
