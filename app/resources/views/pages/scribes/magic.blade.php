@extends('layouts.topnav')

@section('content')
    @include('partials.scribes.nav')
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Magic</h3>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-md-12">
                    <p>Magic will let your wizards cast a variety of spells, giving temporary bonuses, information or damaging your enemies.</p>
                    <p>All spells cost mana, which is produced by towers. The cost of each spell is based on a multiplier of your land size.</p>
                    <p>Self spell bonuses of the same type do not stack. Only the highest bonus will be applied.</p>
                    <em>
                        <p>More information can be found on the <a href="https://wiki.opendominion.net/wiki/Magic">wiki</a>.</p>
                    </em>
                </div>
            </div>
        </div>
    </div>
    <div class="box">
        <div class="box-header with-border">
            <h3 class="box-title">Spells</h3>
        </div>
        <div class="box-body table-responsive">
            @foreach ($spellHelper->getSpells()->groupBy('category') as $category => $categorySpells)
                <div class="row">
                    <div class="col-md-12">
                        <h4 style="border-bottom: 1px solid #f4f4f4; margin-top: 0; padding: 10px 0">{{ $spellHelper->getCategoryString($category) }}</h4>
                        <table class="table table-striped" style="margin-bottom: 0">
                            <colgroup>
                                <col width="125px">
                                <col width="125px">
                                <col width="125px">
                                <col width="125px">
                                <col>
                            </colgroup>
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Race(s)</th>
                                    <th>Cost multiplier</th>
                                    <th>Duration (hours)</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($categorySpells->sortBy(['racial', 'name']) as $spell)
                                    <tr>
                                        <td>{{ $spell->name }}</td>
                                        <td>{{ $spellHelper->getSpellRaces($spell) ? $spellHelper->getSpellRaces($spell) : '--' }}</td>
                                        <td>{{ $spell->cost_mana }}x</td>
                                        <td>{{ $spell->duration ? $spell->duration : '--' }}</td>
                                        <td>{{ $spellHelper->getSpellDescription($spell) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <p>&nbsp;</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
