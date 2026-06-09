@if ($item['action'] == 'train')
    Train
    {{ $item['amount'] }}
    {{ $unitHelper->getUnitName($item['key'], $selectedDominion->race) }}
@elseif ($item['action'] == 'construct')
    Construct
    {{ $item['amount'] }}
    {{ $buildingHelper->getBuildingName($item['key']) }}
@elseif ($item['action'] == 'explore')
    Explore
    {{ $item['amount'] }}
    {{ ucwords($item['key']) }}
@elseif ($item['action'] == 'rezone')
    Rezone
    {{ $item['amount'] }}
    {{ ucwords($item['key']) }} to
    {{ ucwords($item['key2']) }}
@elseif ($item['action'] == 'spell')
    @php($spell = $spellHelper->getSpellByKey($item['key']))
    Cast
    {{ $spell ? $spell->name : 'Unknown Spell' }}
@elseif ($item['action'] == 'release')
    Release
    {{ $item['amount'] }}
    Draftees
@elseif ($item['action'] == 'draft_rate')
    Set Draft Rate
    {{ $item['amount'] }}%
@elseif ($item['action'] == 'daily_bonus')
    Daily Bonus
    {{ ucwords($item['key']) }}
@endif
