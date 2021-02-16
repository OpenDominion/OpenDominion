<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">
            <i class="ra ra-axe"></i>
            Military Power Mods
        </h3>
    </div>
    <div class="box-body">
        <table class="table table-condensed">
            <colgroup>
                <col width="40%">
                <col width="30%">
                <col width="30%">
            </colgroup>
            <thead>
                <tr>
                    <th>Modifier:</th>
                    <th>Offensive:</th>
                    <th>Defensive:</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Prestige:</td>
                    <td>{{ number_format($militaryCalculator->getOffensivePowerMultiplierFromPrestige($target) * 100, 2) }}%</td>
                    <td>--</td>
                </tr>
                <tr>
                    <td>Racial Bonus:</td>
                    <td>{{ number_format($target->race->getPerkMultiplier('offense') * 100, 2) }}%</td>
                    <td>{{ number_format($target->race->getPerkMultiplier('defense') * 100, 2) }}%</td>
                </tr>
                <tr>
                    <td>Buildings:</td>
                    <td>{{ number_format($militaryCalculator->getOffensivePowerMultiplierFromBuildings($target) * 100, 2) }}%</td>
                    <td>{{ number_format($militaryCalculator->getDefensivePowerMultiplierFromBuildings($target) * 100, 2) }}%</td>
                </tr>
                <tr>
                    <td>Improvements:</td>
                    <td>{{ number_format($militaryCalculator->getOffensivePowerMultiplierFromImprovements($target) * 100, 2) }}%</td>
                    <td>{{ number_format($militaryCalculator->getDefensivePowerMultiplierFromImprovements($target) * 100, 2) }}%</td>
                </tr>
                <tr>
                    <td>Techs:</td>
                    <td>{{ number_format($target->getTechPerkMultiplier('offense') * 100, 2) }}%</td>
                    <td>{{ number_format($target->getTechPerkMultiplier('defense') * 100, 2) }}%</td>
                </tr>
                <tr>
                    <td>Spells:</td>
                    <td>{{ number_format($militaryCalculator->getOffensivePowerMultiplierFromSpells($target) * 100, 2) }}%</td>
                    <td>{{ number_format($militaryCalculator->getDefensivePowerMultiplierFromSpells($target) * 100, 2) }}%</td>
                </tr>
                <!--<tr>
                    <td>Wonders:</td>
                    <td></td>
                    <td></td>
                </tr>-->
                <tr>
                    <th>Total:</th>
                    <th>{{ number_format(($militaryCalculator->getOffensivePowerMultiplier($target) - 1) * 100, 2) }}%</th>
                    <th>{{ number_format(($militaryCalculator->getDefensivePowerMultiplier($target) - 1) * 100, 2) }}%</th>
                </tr>
                @if ($target->morale < 100)
                    <tr>
                        <th class="text-danger">Morale:</th>
                        <th class="text-danger">{{ number_format($militaryCalculator->getMoraleMultiplier($target) * 100, 2) }}%</th>
                        <th class="text-danger">{{ number_format($militaryCalculator->getMoraleMultiplier($target) * 100, 2) }}%</th>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
