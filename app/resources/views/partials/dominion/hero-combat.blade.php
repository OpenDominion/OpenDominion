@php
    $recoverCharges = \OpenDominion\HeroCombat\Content\Abilities\Recover::MAX_CHARGES;
    $exposedPercent = (int) round(\OpenDominion\HeroCombat\Content\Effects\Exposed::DEFENSE_PERCENT * 100);
    $guardBonus = (int) round(\OpenDominion\HeroCombat\Content\Effects\Stances\Guarding::DEFENSE_BONUS * 100);
@endphp
<div class="row g-4">
    <div class="col-lg-4">
        <h5 class="h6 fw-bold">Each turn</h5>
        <p class="mb-1">Turns play out in the same order as the battle log:</p>
        <ol class="mb-2 ps-3">
            <li><b>Actions</b>: every hero chooses an action in secret, and they're revealed once everyone has locked in.</li>
            <li><b>Status</b>: Defend, Guard, Counter, Rally, Exposed<!--, Fortify and Caltrops--> take effect first, so they apply to every attack that turn.</li>
            <li><b>Damage</b>: attacks and counters deal their damage.</li>
            <li><b>Healing</b>: Recover and Heal restore health.</li>
            <li><b>Defeats</b>: anyone at 0 health is defeated. Healing comes first, so it can save an ally from that turn's damage.</li>
        </ol>
        <p class="mb-0">The battle ends when everyone on one side is defeated. If both sides are defeated on the same turn, it's a draw.</p>
    </div>
    <div class="col-lg-4">
        <h5 class="h6 fw-bold">Damage</h5>
        <ul class="mb-0 ps-3">
            <li>Attack damage is your attack value minus the target's defense value. For example, 40 attack against 20 defense deals 20 damage.</li>
            <li>Evasion is the target's percent chance to halve the damage of an attack.</li>
            <li>Counter damage is your attack value plus your counter value, minus the attacker's defense value. Counters can't be evaded.</li>
        </ul>
    </div>
    <div class="col-lg-4">
        <h5 class="h6 fw-bold">Limits</h5>
        <ul class="mb-0 ps-3">
            <li>Attack, Defend, and Guard can be used every turn.</li>
            <li>Counter, Focus, and Recover cannot be used two turns in a row. Rally shares Focus's cooldown, and Heal shares Recover's.</li>
            <li>Each hero can use Recover or Heal {{ $recoverCharges }} times per battle.</li>
            <li>Exposed means {{ $exposedPercent }}% less defense that turn. Defending two turns in a row leaves you Exposed, and so does using Recover or Heal.</li>
        </ul>
    </div>
</div>

<h5 class="h6 fw-bold mt-4">Actions</h5>
<div class="table-responsive">
    <table class="table table-sm mb-0">
        <thead>
            <tr>
                <th>Action</th>
                <th>On yourself</th>
                <th>On an ally</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="fw-bold">Attack</td>
                <td>Deals damage to an enemy.</td>
                <td class="text-secondary">—</td>
            </tr>
            <tr>
                <td class="fw-bold">Defend</td>
                <td>Doubles your defense value this turn.</td>
                <td><b>Guard</b>: +{{ $guardBonus }}% defense value, and attacks aimed at your ally hit you instead.</td>
            </tr>
            <tr>
                <td class="fw-bold">Focus</td>
                <td>Your next attack adds your focus value to its damage.</td>
                <td><b>Rally</b>: your ally's attacks this turn add your focus value.</td>
            </tr>
            <tr>
                <td class="fw-bold">Counter</td>
                <td>If you're attacked this turn, you hit back, even while an ally guards you.</td>
                <td class="text-secondary">—</td>
            </tr>
            <tr>
                <td class="fw-bold">Recover</td>
                <td>Heals you by your recover value, but leaves you Exposed this turn.</td>
                <td><b>Heal</b>: heals your ally by your recover value. You are still Exposed.</td>
            </tr>
        </tbody>
    </table>
</div>
<p class="form-text mb-0">Ally actions are only available in battles where you have a teammate.</p>

<h5 class="h6 fw-bold mt-4">Your hero</h5>
<ul class="mb-0 ps-3">
    <li>Heroes have 80 health plus 2 per level, before gear, and bring their combat loadout into every battle.</li>
    <li>When you run out of match time, your hero acts automatically using the selected strategy.</li>
</ul>
