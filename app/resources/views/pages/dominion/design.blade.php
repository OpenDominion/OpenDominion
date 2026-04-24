@extends('layouts.master')

@section('page-header', 'Design System')

@section('content')

    {{-- ── Typography ──────────────────────────────────────────────────────── --}}
    <div class="row">
        <div class="col-sm-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <span class="card-title"><i class="fa fa-font"></i> Typography</span>
                </div>
                <div class="card-body">
                    <h1>Heading 1 &mdash; The Realm Awakens</h1>
                    <h2>Heading 2 &mdash; Military Advisors Report</h2>
                    <h3>Heading 3 &mdash; Land Distribution</h3>
                    <h4>Heading 4 &mdash; Resource Summary</h4>
                    <h5>Heading 5 &mdash; Unit Training Queue</h5>
                    <h6>Heading 6 &mdash; Footnotes</h6>
                    <hr>
                    <p>Body text: The dominion sprawls across <strong>4,200 acres</strong> of fertile land. Your <em>peasants number 12,847</em> and produce <a href="#">1,927 platinum</a> per hour. The realm stands united against <span class="text-danger">hostile forces</span> from the north.</p>
                    <p class="text-muted">Muted text: Last updated 3 hours ago. Next tick in 47 minutes.</p>
                    <p><small>Small text: Detailed calculations are available in the Advisors section.</small></p>
                    <blockquote>Blockquote: "Our spies report movement along the western border. The enemy musters a force of considerable strength."</blockquote>
                    <p>Monospace: <code>getOffensivePower($dominion) = 42,847</code></p>
                    <pre>Pre-formatted:
Platinum:  127,482 (+1,927/hr)
Food:       84,291 (+3,104/hr)
Lumber:     41,028 (+892/hr)
Mana:       12,440 (+284/hr)</pre>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Text Colors ─────────────────────────────────────────────────────── --}}
    <div class="row">
        <div class="col-sm-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <span class="card-title"><i class="fa fa-palette"></i> Text Colors</span>
                </div>
                <div class="card-body">
                    <p class="text-success"><i class="fa fa-check"></i> text-success: Training complete. 500 Knights have joined your army.</p>
                    <p class="text-danger"><i class="fa fa-times"></i> text-danger: Invasion failed! You did not send enough troops.</p>
                    <p class="text-muted">text-muted: No active spells on this dominion.</p>
                    <p class="text-green"><i class="fa fa-arrow-up"></i> text-green: +2,400 acres gained</p>
                    <p class="text-orange"><i class="fa fa-exclamation-triangle"></i> text-orange: Starvation imminent</p>
                    <p class="text-red"><i class="fa fa-arrow-down"></i> text-red: -1,200 casualties</p>
                    <p class="text-aqua"><i class="ra ra-shield"></i> text-aqua: Under protection (48 hours remaining)</p>
                    <p class="text-purple"><i class="fa fa-hat-wizard"></i> text-purple: Arcane energy detected</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Alerts ──────────────────────────────────────────────────────────── --}}
    <div class="row">
        <div class="col-sm-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <span class="card-title"><i class="fa fa-bell"></i> Alerts</span>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i> <strong>Advisor:</strong> Until your draft rate is met, 1% of your peasants will join your military each hour.
                    </div>
                    <div class="alert alert-success">
                        <i class="fa fa-check"></i> <strong>Success:</strong> Training order queued. 4,200 Kraken will complete in 9 hours.
                    </div>
                    <div class="alert alert-warning">
                        <i class="fa fa-exclamation-triangle"></i> <strong>Warning:</strong> A swarm of insects is eating your crops, slowing food production by 5%.
                    </div>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        <i class="fa fa-skull-crossbones"></i> <strong>Danger:</strong> Our spies discovered 470 mana missing from our towers! Enemy wizards may be at work.
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Cards ───────────────────────────────────────────────────────────── --}}
    <div class="row">
        <div class="col-sm-12 col-md-4">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <span class="card-title"><i class="fa fa-bar-chart"></i> card-outline card-primary</span>
                </div>
                <div class="card-body">
                    <p>This is the most common card style used throughout the application for primary content panels.</p>
                </div>
                <div class="card-footer text-end">
                    <button class="btn btn-primary btn-sm">Action</button>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-md-4">
            <div class="card card-primary">
                <div class="card-header">
                    <span class="card-title"><i class="fa fa-shield"></i> card-primary (solid header)</span>
                </div>
                <div class="card-body">
                    <p>Solid primary header variant. Used for prominent sections that need strong visual hierarchy.</p>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-md-4">
            <div class="card">
                <div class="card-header">
                    <span class="card-title"><i class="fa fa-cube"></i> Plain card (no variant)</span>
                </div>
                <div class="card-body">
                    <p>A neutral card with no color variant. Used for secondary information panels.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-12 col-md-3">
            <div class="card border-info">
                <div class="card-header">
                    <span class="card-title">border-info</span>
                </div>
                <div class="card-body">
                    <p>Info border accent card.</p>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-md-3">
            <div class="card border-success">
                <div class="card-header">
                    <span class="card-title">border-success</span>
                </div>
                <div class="card-body">
                    <p>Success border accent card.</p>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-md-3">
            <div class="card border-warning">
                <div class="card-header">
                    <span class="card-title">border-warning</span>
                </div>
                <div class="card-body">
                    <p>Warning border accent card.</p>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-md-3">
            <div class="card border-danger">
                <div class="card-header">
                    <span class="card-title">border-danger</span>
                </div>
                <div class="card-body">
                    <p>Danger border accent card.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Buttons ─────────────────────────────────────────────────────────── --}}
    <div class="row">
        <div class="col-sm-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <span class="card-title"><i class="fa fa-mouse-pointer"></i> Buttons</span>
                </div>
                <div class="card-body">
                    <h5>Standard</h5>
                    <p>
                        <button class="btn btn-primary">Primary</button>
                        <button class="btn btn-secondary">Secondary</button>
                        <button class="btn btn-success">Success</button>
                        <button class="btn btn-info">Info</button>
                        <button class="btn btn-warning">Warning</button>
                        <button class="btn btn-danger">Danger</button>
                        <button class="btn btn-link">Link</button>
                    </p>
                    <h5>Sizes</h5>
                    <p>
                        <button class="btn btn-primary btn-lg">Large</button>
                        <button class="btn btn-primary">Default</button>
                        <button class="btn btn-primary btn-sm">Small</button>
                    </p>
                    <h5>States</h5>
                    <p>
                        <button class="btn btn-primary" disabled>Disabled</button>
                        <button class="btn btn-primary active">Active</button>
                    </p>
                    <h5>Block</h5>
                    <button class="btn btn-primary btn-block">Full Width Block Button</button>
                    <br><br>
                    <h5>App Buttons (icon tiles)</h5>
                    <p>
                        <a class="btn btn-app"><i class="fa fa-fort-awesome"></i> Construct</a>
                        <a class="btn btn-app"><i class="fa fa-flask"></i> Magic</a>
                        <a class="btn btn-app"><i class="fa fa-eye"></i> Espionage</a>
                        <a class="btn btn-app"><i class="ra ra-sword"></i> Invade</a>
                    </p>
                    <h5>Button Groups</h5>
                    <div class="btn-group w-100" role="group">
                        <button class="btn btn-primary active">Overview</button>
                        <button class="btn btn-primary">Military</button>
                        <button class="btn btn-primary">Land</button>
                        <button class="btn btn-primary">Buildings</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Badges ──────────────────────────────────────────────────────────── --}}
    <div class="row">
        <div class="col-sm-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <span class="card-title"><i class="fa fa-tag"></i> Badges</span>
                </div>
                <div class="card-body">
                    <p>
                        <span class="badge text-bg-primary">Primary</span>
                        <span class="badge text-bg-secondary">Secondary</span>
                        <span class="badge text-bg-success">Success</span>
                        <span class="badge text-bg-info">Info</span>
                        <span class="badge text-bg-warning">Warning</span>
                        <span class="badge text-bg-danger">Danger</span>
                    </p>
                    <p>
                        Used in context: Realm #4 <span class="badge text-bg-primary">War</span>
                        &middot; Spells Active <span class="badge text-bg-info">3</span>
                        &middot; Notifications <span class="badge text-bg-danger">7</span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Tables ──────────────────────────────────────────────────────────── --}}
    <div class="row">
        <div class="col-sm-12 col-md-6">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <span class="card-title"><i class="fa fa-table"></i> Status Table (striped)</span>
                </div>
                <div class="card-body table-responsive no-padding">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Resource</th>
                                <th class="text-end">Amount</th>
                                <th class="text-end">Per Hour</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Platinum</td>
                                <td class="text-end">127,482</td>
                                <td class="text-end text-success">+1,927</td>
                            </tr>
                            <tr>
                                <td>Food</td>
                                <td class="text-end">84,291</td>
                                <td class="text-end text-success">+3,104</td>
                            </tr>
                            <tr>
                                <td>Lumber</td>
                                <td class="text-end">41,028</td>
                                <td class="text-end text-success">+892</td>
                            </tr>
                            <tr>
                                <td>Mana</td>
                                <td class="text-end">12,440</td>
                                <td class="text-end text-danger">-128</td>
                            </tr>
                            <tr>
                                <td>Ore</td>
                                <td class="text-end">0</td>
                                <td class="text-end text-muted">0</td>
                            </tr>
                            <tr>
                                <td>Gems</td>
                                <td class="text-end">9,841</td>
                                <td class="text-end text-success">+47</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-6">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <span class="card-title"><i class="fa fa-table"></i> Military Table (row variants)</span>
                </div>
                <div class="card-body table-responsive no-padding">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Unit</th>
                                <th class="text-end">Home</th>
                                <th class="text-end">Training</th>
                                <th class="text-end">OP / DP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="table-active">
                                <td><strong>Draftees</strong></td>
                                <td class="text-end">4,281</td>
                                <td class="text-end text-muted">0</td>
                                <td class="text-end">0 / 1</td>
                            </tr>
                            <tr>
                                <td>Soldiers</td>
                                <td class="text-end">1,200</td>
                                <td class="text-end">0</td>
                                <td class="text-end">3 / 3</td>
                            </tr>
                            <tr class="table-success">
                                <td>Knights <span class="badge text-bg-success">Elite</span></td>
                                <td class="text-end">2,847</td>
                                <td class="text-end">500</td>
                                <td class="text-end">6 / 3</td>
                            </tr>
                            <tr class="table-info">
                                <td>Archmages <span class="badge text-bg-info">Wizard</span></td>
                                <td class="text-end">840</td>
                                <td class="text-end">200</td>
                                <td class="text-end">0 / 5</td>
                            </tr>
                            <tr class="table-warning">
                                <td>War Elephants <span class="badge text-bg-warning">Slow</span></td>
                                <td class="text-end">0</td>
                                <td class="text-end">100</td>
                                <td class="text-end">8 / 4</td>
                            </tr>
                            <tr class="table-danger">
                                <td>Spies <span class="badge text-bg-danger">Lost</span></td>
                                <td class="text-end">0</td>
                                <td class="text-end">0</td>
                                <td class="text-end">0 / 0</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Forms ───────────────────────────────────────────────────────────── --}}
    <div class="row">
        <div class="col-sm-12 col-md-6">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <span class="card-title"><i class="fa fa-edit"></i> Forms &mdash; Inputs</span>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="demo-text">Text Input</label>
                        <input type="text" class="form-control" id="demo-text" placeholder="Enter dominion name...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="demo-select">Select</label>
                        <select class="form-select" id="demo-select">
                            <option>Human</option>
                            <option>Elf</option>
                            <option>Dwarf</option>
                            <option>Orc</option>
                            <option>Undead</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="demo-number">Number Input</label>
                        <input type="number" class="form-control" id="demo-number" value="500" min="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="demo-textarea">Textarea</label>
                        <textarea class="form-control" id="demo-textarea" rows="3" placeholder="Write your journal entry..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="demo-disabled">Disabled Input</label>
                        <input type="text" class="form-control" id="demo-disabled" value="Cannot be changed" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="demo-readonly">Readonly Input</label>
                        <input type="text" class="form-control" id="demo-readonly" value="Read only value" readonly>
                    </div>
                    <span class="form-text">This is helper text below a form field.</span>
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-6">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <span class="card-title"><i class="fa fa-edit"></i> Forms &mdash; Input Groups &amp; Training</span>
                </div>
                <div class="card-body">
                    <h5>Input Groups</h5>
                    <div class="mb-3">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa fa-search"></i></span>
                            <input type="text" class="form-control" placeholder="Search dominions...">
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="input-group">
                            <input type="number" class="form-control text-center" value="1000" min="0">
                            <button class="btn btn-primary">Train</button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="input-group">
                            <span class="input-group-text">Platinum</span>
                            <input type="number" class="form-control text-center" value="50000">
                            <span class="input-group-text"><i class="fa fa-arrow-right"></i></span>
                            <input type="number" class="form-control text-center" value="25000">
                            <span class="input-group-text">Food</span>
                        </div>
                    </div>
                    <hr>
                    <h5>Training Form (typical layout)</h5>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Unit</th>
                                <th class="text-end">Available</th>
                                <th style="width: 120px;">Train</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Soldiers</td>
                                <td class="text-end">4,281</td>
                                <td><input type="number" class="form-control form-control-sm text-center" value="0" min="0"></td>
                            </tr>
                            <tr>
                                <td>Knights</td>
                                <td class="text-end">4,281</td>
                                <td><input type="number" class="form-control form-control-sm text-center" value="0" min="0"></td>
                            </tr>
                            <tr>
                                <td>Archmages</td>
                                <td class="text-end">4,281</td>
                                <td><input type="number" class="form-control form-control-sm text-center" value="0" min="0"></td>
                            </tr>
                            <tr>
                                <td>War Elephants</td>
                                <td class="text-end">4,281</td>
                                <td><input type="number" class="form-control form-control-sm text-center" value="0" min="0"></td>
                            </tr>
                        </tbody>
                    </table>
                    <button class="btn btn-primary btn-block">Train Units</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Navigation ──────────────────────────────────────────────────────── --}}
    <div class="row">
        <div class="col-sm-12 col-md-6">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <span class="card-title"><i class="fa fa-compass"></i> Nav Tabs</span>
                </div>
                <div class="card-body">
                    <div class="nav-tabs-custom">
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" href="#tab-overview" role="tab">Overview</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#tab-military" role="tab">Military</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#tab-land" role="tab">Land</a>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane active" id="tab-overview" role="tabpanel">
                                <p class="mt-3">Overview content: Your dominion is thriving with 4,200 acres of land and a networth of 128,472.</p>
                            </div>
                            <div class="tab-pane" id="tab-military" role="tabpanel">
                                <p class="mt-3">Military content: 8,847 troops stand ready to defend the realm.</p>
                            </div>
                            <div class="tab-pane" id="tab-land" role="tabpanel">
                                <p class="mt-3">Land content: 2,100 acres of plains, 840 acres of mountain, 620 acres of forest.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-6">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <span class="card-title"><i class="fa fa-list"></i> Nav Pills (vertical)</span>
                </div>
                <div class="card-body">
                    <ul class="nav nav-pills flex-column">
                        <li class="nav-item"><a class="nav-link active" href="#">Status</a></li>
                        <li class="nav-item"><a class="nav-link" href="#">Advisors</a></li>
                        <li class="nav-item"><a class="nav-link" href="#">Explore Land</a></li>
                        <li class="nav-item"><a class="nav-link" href="#">Construct Buildings</a></li>
                        <li class="nav-item"><a class="nav-link" href="#">Military</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Progress Bars ───────────────────────────────────────────────────── --}}
    <div class="row">
        <div class="col-sm-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <span class="card-title"><i class="fa fa-tasks"></i> Progress Bars</span>
                </div>
                <div class="card-body">
                    <h5>Technology Research</h5>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Siege Engineering</span>
                            <span>72%</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar progress-bar-primary" role="progressbar" style="width: 72%"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Arcane Knowledge</span>
                            <span>100%</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar progress-bar-success" role="progressbar" style="width: 100%">Complete</div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Raid Progress</span>
                            <span>45%</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar progress-bar-info" role="progressbar" style="width: 30%">Realm</div>
                            <div class="progress-bar progress-bar-primary" role="progressbar" style="width: 15%">You</div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="progress progress-sm">
                            <div class="progress-bar progress-bar-success" role="progressbar" style="width: 85%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── List Groups ─────────────────────────────────────────────────────── --}}
    <div class="row">
        <div class="col-sm-12 col-md-6">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <span class="card-title"><i class="fa fa-list-ul"></i> List Group</span>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Aetherius (#4)
                            <span class="badge text-bg-primary">12,847 NW</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Ironhold (#7)
                            <span class="badge text-bg-primary">11,203 NW</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Shadowmere (#2)
                            <span class="badge text-bg-primary">9,847 NW</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Dawnbreaker (#9)
                            <span class="badge text-bg-primary">8,421 NW</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-6">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <span class="card-title"><i class="fa fa-book"></i> Pagination</span>
                </div>
                <div class="card-body">
                    <nav>
                        <ul class="pagination">
                            <li class="page-item disabled"><a class="page-link" href="#">&laquo;</a></li>
                            <li class="page-item active"><a class="page-link" href="#">1</a></li>
                            <li class="page-item"><a class="page-link" href="#">2</a></li>
                            <li class="page-item"><a class="page-link" href="#">3</a></li>
                            <li class="page-item"><a class="page-link" href="#">4</a></li>
                            <li class="page-item"><a class="page-link" href="#">5</a></li>
                            <li class="page-item"><a class="page-link" href="#">&raquo;</a></li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Modals ──────────────────────────────────────────────────────────── --}}
    <div class="row">
        <div class="col-sm-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <span class="card-title"><i class="fa fa-window-maximize"></i> Modal</span>
                </div>
                <div class="card-body">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#demoModal">Open Demo Modal</button>

                    <div class="modal fade" id="demoModal" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Confirm Invasion</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p>You are about to invade <strong>Shadowmere (#2)</strong> with <strong>5,400 units</strong>.</p>
                                    <p class="text-danger">This action cannot be undone. Are you sure you want to proceed?</p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-danger">Confirm Invasion</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Tooltips ────────────────────────────────────────────────────────── --}}
    <div class="row">
        <div class="col-sm-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <span class="card-title"><i class="fa fa-comment"></i> Tooltips</span>
                </div>
                <div class="card-body">
                    <p>
                        Hover over these elements:
                        <a href="#" data-bs-toggle="tooltip" data-bs-placement="top" title="Offensive Power: 42,847">OP: 42,847</a> &middot;
                        <a href="#" data-bs-toggle="tooltip" data-bs-placement="top" title="Defensive Power: 38,291">DP: 38,291</a> &middot;
                        <span data-bs-toggle="tooltip" data-bs-placement="top" title="5% bonus from Temples" class="text-success">+5% <i class="fa fa-info-circle"></i></span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Icons ───────────────────────────────────────────────────────────── --}}
    <div class="row">
        <div class="col-sm-12 col-md-6">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <span class="card-title"><i class="fa fa-icons"></i> Font Awesome Icons</span>
                </div>
                <div class="card-body">
                    <p>
                        <i class="fa fa-bar-chart fa-fw"></i> bar-chart &middot;
                        <i class="fa fa-shield fa-fw"></i> shield &middot;
                        <i class="fa fa-flask fa-fw"></i> flask &middot;
                        <i class="fa fa-eye fa-fw"></i> eye &middot;
                        <i class="fa fa-search fa-fw"></i> search &middot;
                        <i class="fa fa-fort-awesome fa-fw"></i> fort-awesome &middot;
                        <i class="fa fa-university fa-fw"></i> university &middot;
                        <i class="fa fa-check fa-fw"></i> check &middot;
                        <i class="fa fa-times fa-fw"></i> times &middot;
                        <i class="fa fa-arrow-up fa-fw"></i> arrow-up &middot;
                        <i class="fa fa-arrow-down fa-fw"></i> arrow-down &middot;
                        <i class="fa fa-exclamation-triangle fa-fw"></i> exclamation-triangle &middot;
                        <i class="fa fa-info-circle fa-fw"></i> info-circle &middot;
                        <i class="fa fa-hat-wizard fa-fw"></i> hat-wizard &middot;
                        <i class="fa fa-skull-crossbones fa-fw"></i> skull-crossbones
                    </p>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-md-6">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <span class="card-title"><i class="ra ra-lg ra-sword"></i> RPG Awesome Icons</span>
                </div>
                <div class="card-body">
                    <p>
                        <i class="ra ra-sword ra-fw"></i> sword &middot;
                        <i class="ra ra-shield ra-fw"></i> shield &middot;
                        <i class="ra ra-tower ra-fw"></i> tower &middot;
                        <i class="ra ra-axe ra-fw"></i> axe &middot;
                        <i class="ra ra-crown ra-fw"></i> crown &middot;
                        <i class="ra ra-crossed-swords ra-fw"></i> crossed-swords &middot;
                        <i class="ra ra-gem-pendant ra-fw"></i> gem-pendant &middot;
                        <i class="ra ra-book ra-fw"></i> book &middot;
                        <i class="ra ra-helmet ra-fw"></i> helmet &middot;
                        <i class="ra ra-flag ra-fw"></i> flag &middot;
                        <i class="ra ra-scroll-unfurled ra-fw"></i> scroll-unfurled &middot;
                        <i class="ra ra-fire ra-fw"></i> fire &middot;
                        <i class="ra ra-potion ra-fw"></i> potion
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Combined Example: Status Panel ──────────────────────────────────── --}}
    <div class="row">
        <div class="col-sm-12 col-md-9">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <span class="card-title"><i class="fa fa-bar-chart"></i> The Dominion of Aetherius (#4)</span>
                    <span class="float-end">
                        <span class="badge text-bg-success">Growing</span>
                        <span class="badge text-bg-info">Protected</span>
                    </span>
                </div>
                <div class="card-body table-responsive no-padding">
                    <table class="table table-hover">
                        <colgroup>
                            <col width="200">
                            <col>
                            <col width="200">
                            <col>
                        </colgroup>
                        <tbody>
                            <tr>
                                <td class="text-muted">Ruler</td>
                                <td><strong>Lord Vexarius</strong></td>
                                <td class="text-muted">Race</td>
                                <td>Firewalker</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Land</td>
                                <td><strong>4,200</strong> acres</td>
                                <td class="text-muted">Networth</td>
                                <td><strong>128,472</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Prestige</td>
                                <td><strong>142</strong> <span class="text-success">(+2)</span></td>
                                <td class="text-muted">Day / Hour</td>
                                <td>Day 24 / Hour 7</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">
                    <a href="#" class="btn btn-primary btn-sm"><i class="fa fa-eye"></i> View Advisors</a>
                    <a href="#" class="btn btn-secondary btn-sm float-end"><i class="fa fa-search"></i> Search Realm</a>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-md-3">
            <div class="card border-warning">
                <div class="card-header">
                    <span class="card-title"><i class="ra ra-shield text-aqua"></i> Under Protection</span>
                </div>
                <div class="card-body">
                    <p>You are under a magical state of protection. During this time you cannot be attacked or attack other dominions.</p>
                    <p class="text-muted"><strong>48 hours</strong> remaining</p>
                    <a href="#" class="btn btn-success btn-block">Restart or Rename</a>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <span class="card-title"><i class="fa fa-clock"></i> Next Tick</span>
                </div>
                <div class="card-body text-center">
                    <h3>47:23</h3>
                    <p class="text-muted">minutes remaining</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Links ───────────────────────────────────────────────────────────── --}}
    <div class="row">
        <div class="col-sm-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <span class="card-title"><i class="fa fa-link"></i> Links</span>
                </div>
                <div class="card-body">
                    <p>
                        <a href="#">Standard link</a> &middot;
                        <a href="#" class="text-muted">Muted link</a> &middot;
                        <a href="#" class="text-success">Success link</a> &middot;
                        <a href="#" class="text-danger">Danger link</a> &middot;
                        <a href="#" class="text-aqua">Aqua link</a> &middot;
                        <a href="#" class="alert-link">Alert link</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

@endsection
