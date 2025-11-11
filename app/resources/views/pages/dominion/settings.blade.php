@extends('layouts.master')

@section('page-header', 'Settings')

@section('content')
    @php
        $resourceOptions = $miscHelper->getResourceOverviewOptions();
        $defaultConfig = $miscHelper->getResourceOverviewDefaultSettings();
        $config = $defaultConfig;
        if (isset($selectedDominion->settings['resources_overview'])) {
            $config = $selectedDominion->settings['resources_overview'];
        }
        // Fill in any missing rows with defaults
        for ($i = 0; $i < 4; $i++) {
            if (!isset($config[$i])) {
                $config[$i] = $defaultConfig[$i] ?? ['Defense', 'Jobs', 'Wiz Str', 'Morale'];
            }
        }
        $rowCount = isset($selectedDominion->settings['resources_overview']) ? count($selectedDominion->settings['resources_overview']) : 3;
    @endphp
    <div class="row">

        <div class="col-sm-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-cog"></i> Dominion Settings</h3>
                </div>
                <form class="form" action="{{ route('dominion.misc.settings') }}" method="post">
                    @csrf
                    <div class="box-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="clearfix">
                                    <label class="form-label pull-left" style="margin-top: 20px;">
                                        Resources Overview:
                                    </label>
                                    <div class="pull-right">
                                        <select class="form-control" id="rowCountSelect" style="width: auto; display: inline-block; margin-bottom: 5px; margin-right: 5px;">
                                            <option value="1" {{ $rowCount == 1 ? 'selected' : '' }}>1 Row</option>
                                            <option value="2" {{ $rowCount == 2 ? 'selected' : '' }}>2 Rows</option>
                                            <option value="3" {{ $rowCount == 3 ? 'selected' : '' }}>3 Rows</option>
                                            <option value="4" {{ $rowCount == 4 ? 'selected' : '' }}>4 Rows</option>
                                        </select>
                                        <button type="button" class="btn btn-default" id="resetButton">Reset to Default</button>
                                    </div>
                                </div>
                                @for ($row = 0; $row < 4; $row++)
                                    <div class="form-group row resource-row" data-row="{{ $row }}">
                                        @for ($col = 0; $col < 4; $col++)
                                            <div class="col-xs-3">
                                                <select class="form-control resource-select-{{ $row }}" name="resources_overview[{{ $row }}][{{ $col }}]" data-default="{{ $defaultConfig[$row][$col] ?? '' }}">
                                                    @foreach ($resourceOptions as $resourceOption)
                                                        <option value="{{ $resourceOption }}" {{ isset($config[$row][$col]) && $config[$row][$col] == $resourceOption ? 'selected' : '' }}>
                                                            {{ $resourceOption }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @endfor
                                    </div>
                                @endfor
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Preferred Title:</label>
                                    <select class="form-control" name="preferred_title">
                                        @foreach ($rankingsHelper->getRankings() as $ranking)
                                            <option value="{{ $ranking['key'] }}" {{ isset($selectedDominion->settings['preferred_title']) && $selectedDominion->settings['preferred_title'] == $ranking['key'] ? 'selected' : null }}>
                                                {{ $ranking['name'] }} - "{{ $ranking['title'] }}"
                                            </option>
                                        @endforeach
                                    </select>
                                    <span class="small">Used in round forum if you currently hold this title, otherwise uses the first in the list.</span>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Title Icon:</label>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="show_icon" {{ isset($selectedDominion->settings['show_icon']) && $selectedDominion->settings['show_icon'] == 'on' ? 'checked' : null }} />
                                            Display icon on the realm page
                                        </label>
                                    </div>
                                    <span class="small">Uses your preferred title, if possible.</span>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Chaos League:</label>
                                    <div class="radio">
                                        <label>
                                            <input type="radio" name="black_guard_icon" value="private" checked />
                                            Visible to members only
                                        </label>
                                    </div>
                                    <div class="radio">
                                        <label>
                                            <input type="radio" name="black_guard_icon" value="public" {{ isset($selectedDominion->settings['black_guard_icon']) && $selectedDominion->settings['black_guard_icon'] == 'public' ? 'checked' : null }} />
                                            Visible to everyone
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Hide Sidebar Links:</label>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="hidden_links[]" value="explore_land"
                                                {{ in_array('explore_land', $selectedDominion->settings['hidden_links'] ?? []) ? 'checked' : null }} />
                                            Explore Land
                                        </label>
                                    </div>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="hidden_links[]" value="hero_battles"
                                                {{ in_array('hero_battles', $selectedDominion->settings['hidden_links'] ?? []) ? 'checked' : null }} />
                                            Hero Battles
                                        </label>
                                    </div>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="hidden_links[]" value="hero_tournament"
                                                {{ in_array('hero_tournament', $selectedDominion->settings['hidden_links'] ?? []) ? 'checked' : null }} />
                                            Hero Tournament
                                        </label>
                                    </div>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="hidden_links[]" value="journal"
                                                {{ in_array('journal', $selectedDominion->settings['hidden_links'] ?? []) ? 'checked' : null }} />
                                            Journal
                                        </label>
                                    </div>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="hidden_links[]" value="invade"
                                                {{ in_array('invade', $selectedDominion->settings['hidden_links'] ?? []) ? 'checked' : null }} />
                                            Invade
                                        </label>
                                    </div>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="hidden_links[]" value="calculators"
                                                {{ in_array('calculators', $selectedDominion->settings['hidden_links'] ?? []) ? 'checked' : null }} />
                                            Calculators
                                        </label>
                                    </div>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="hidden_links[]" value="world"
                                                {{ in_array('world', $selectedDominion->settings['hidden_links'] ?? []) ? 'checked' : null }} />
                                            The World
                                        </label>
                                    </div>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="hidden_links[]" value="council"
                                                {{ in_array('council', $selectedDominion->settings['hidden_links'] ?? []) ? 'checked' : null }} />
                                            The Council
                                        </label>
                                    </div>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="hidden_links[]" value="rankings"
                                                {{ in_array('rankings', $selectedDominion->settings['hidden_links'] ?? []) ? 'checked' : null }} />
                                            Rankings
                                        </label>
                                    </div>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="hidden_links[]" value="forum"
                                                {{ in_array('forum', $selectedDominion->settings['hidden_links'] ?? []) ? 'checked' : null }} />
                                            Round Forum
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary">Update Settings</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
@endsection

@push('inline-scripts')
    <script type="text/javascript">
        document.getElementById('rowCountSelect').addEventListener('change', function() {
            var rowCount = parseInt(this.value);
            var rows = document.querySelectorAll('.resource-row');

            rows.forEach(function(row) {
                var rowIndex = parseInt(row.getAttribute('data-row'));
                var selects = row.querySelectorAll('select');

                if (rowIndex >= rowCount) {
                    selects.forEach(function(select) {
                        select.disabled = true;
                    });
                } else {
                    selects.forEach(function(select) {
                        select.disabled = false;
                    });
                }
            });
        });

        document.getElementById('resetButton').addEventListener('click', function() {
            var selects = document.querySelectorAll('[data-default]');
            selects.forEach(function(select) {
                var defaultValue = select.getAttribute('data-default');
                if (defaultValue) {
                    select.value = defaultValue;
                }
            });
            // Reset row count to 3
            document.getElementById('rowCountSelect').value = '3';
            document.getElementById('rowCountSelect').dispatchEvent(new Event('change'));
        });

        // Trigger on page load
        document.getElementById('rowCountSelect').dispatchEvent(new Event('change'));
    </script>
@endpush
