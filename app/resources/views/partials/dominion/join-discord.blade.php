@if ($selectedDominion->round->discord_guild_id && $selectedDominion->realm->number != 0 && $selectedDominion->realm->getSetting('usediscord') !== false)
    <div class="card">
        <div class="card-header">
            <span class="card-title">Team Play</span>
        </div>
        <div class="card-body text-justify">
            <p>Open Dominion is intended to be played as a team game. For the best social team play experience, join us on Discord. There is a helpful community of players, particularly your realmies, who can teach and guide you.</p>
            <div class="text-center">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#joinDiscordModal">
                    <i class="ra ra-speech-bubbles"></i> Join Realm Discord
                </button>
            </div>
        </div>
    </div>

    @once
        <div class="modal fade" id="joinDiscordModal" tabindex="-1" aria-labelledby="joinDiscordModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="joinDiscordModalLabel">Before you join</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>The round Discord server is shared between all realms. Your realm's channels are visible only to members of your own realm and they are named so that nobody can tell which realm they belong to. Currently, Discord leaks some information about private channels through the API and has the following limitations:</p>
                        <ul>
                            <li>A determined player using modified Discord software may be able to tell which accounts share a realm, though not <em>which</em> realm.</li>
                            <li>Everyone in your realm can see that you've joined the server and anything you post in your realm's channels, even after you leave the server.</li>
                        </ul>
                        <p>The first item above is temporary. Discord has <a href="https://docs.discord.com/developers/change-log#august-12-2026" target="_blank" rel="noopener">announced a change</a> which hides private channel information from anyone without access, closing the loophole. Bots are slated to lose access after November 16th 2026, but the timing is unclear for other Discord clients.</p>
                        <p class="mb-0">If your realm would rather not rely on any of this, create your own Discord server and share the invite with your realmies in the <a href="{{ route('dominion.council') }}">Council</a>.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                        <a href="{{ $discordHelper->getDiscordConnectUrl('join') }}" target="_blank" class="btn btn-primary">
                            <i class="ra ra-speech-bubbles"></i> Continue to Discord
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endonce
@endif
