<style>
    .automation-studio {
        --automation-command: var(--od-primary, var(--bs-primary));
        --automation-command-deep: var(--od-primary-dark, var(--bs-primary));
        --automation-command-soft: var(--od-primary-bg-soft, color-mix(in srgb, var(--automation-command) 13%, transparent));
        --automation-brass: var(--od-primary, var(--bs-primary));
        --automation-paper: var(--od-surface, var(--bs-body-bg));
        --automation-paper-raised: var(--od-surface-elevated, var(--od-surface, var(--bs-body-bg)));
        --automation-paper-alt: var(--od-surface-alt, var(--bs-tertiary-bg));
        --automation-rule: var(--od-border-accent, var(--bs-border-color));
        --automation-rule-soft: var(--od-border, var(--bs-border-color));
        --automation-ink: var(--od-text-body, var(--bs-body-color));
        --automation-muted: var(--od-text-secondary, var(--bs-secondary-color));
        --automation-danger: var(--od-danger, var(--bs-danger));
        overflow: hidden;
        color: var(--automation-ink);
        background: var(--automation-paper);
        border-color: var(--automation-rule);
    }

    [data-color-scheme="parchment"] .automation-studio {
        --automation-command: #2d5944;
        --automation-command-deep: #1f3f31;
        --automation-command-soft: #dfe8d9;
        --automation-brass: #9a6c1d;
    }

    .automation-studio .btn-primary {
        color: #fff !important;
        background: var(--automation-command) !important;
        border-color: var(--automation-command-deep) !important;
    }

    .automation-studio .btn-primary:hover,
    .automation-studio .btn-primary:focus-visible {
        background: var(--automation-command-deep) !important;
    }

    .automation-studio-header {
        padding: 1.1rem 1.35rem;
        border-top: 3px solid var(--automation-brass);
        border-bottom: 1px solid var(--automation-rule);
        background: var(--automation-paper-alt);
    }

    .automation-studio-header .card-title {
        float: none;
        text-transform: uppercase;
        letter-spacing: .02em;
    }

    .automation-studio-header p {
        margin: .35rem 0 0;
        color: var(--automation-muted);
    }

    .automation-status-strip {
        padding: 1rem 1.35rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 2rem;
        border-bottom: 1px solid var(--automation-rule);
        background: var(--automation-paper-raised);
    }

    .automation-current-tick {
        display: flex;
        align-items: baseline;
        gap: .55rem;
    }

    .automation-current-tick strong,
    .automation-section-label,
    .automation-playbook-head h2,
    .automation-execution-rules summary {
        text-transform: uppercase;
        letter-spacing: .08em;
    }

    .automation-current-tick span { color: var(--automation-muted); }

    .automation-quota { width: min(320px, 42%); }
    .automation-quota-copy {
        margin-bottom: .45rem;
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        color: var(--automation-muted);
        font-size: .8rem;
    }
    .automation-quota-copy strong { color: var(--automation-ink); }
    .automation-quota-track { display: grid; grid-template-columns: repeat(3, 1fr); gap: 4px; }
    .automation-quota-track i {
        display: block;
        height: 7px;
        background: color-mix(in srgb, var(--automation-rule) 72%, transparent);
    }
    .automation-quota-track i.is-used { background: var(--automation-command); }

    .automation-workspace {
        padding: 1.35rem;
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(275px, 330px);
        align-items: start;
        gap: 1.35rem;
    }

    .automation-section-label {
        margin: 0 0 .8rem;
        color: var(--automation-brass);
        font-size: .82rem;
    }

    .automation-timeline { position: relative; }
    .automation-timeline::before {
        content: "";
        position: absolute;
        top: 18px;
        bottom: 18px;
        left: 87px;
        width: 1px;
        background: var(--automation-rule);
    }

    .automation-tick-row {
        position: relative;
        display: grid;
        grid-template-columns: 72px 30px minmax(0, 1fr);
        align-items: start;
        min-height: 70px;
    }

    .automation-tick-time {
        padding-top: .6rem;
        text-align: right;
        line-height: 1.2;
    }
    .automation-tick-time strong { display: block; font-size: .8rem; }
    .automation-tick-time span { color: var(--automation-muted); font-size: .72rem; }
    .automation-tick-mark {
        position: relative;
        z-index: 1;
        width: 16px;
        height: 16px;
        margin: .65rem auto 0;
        border: 2px solid var(--automation-rule);
        border-radius: 50%;
        background: var(--automation-paper);
    }
    .automation-tick-row.is-occupied .automation-tick-mark {
        border-color: var(--automation-command);
        background: var(--automation-command);
    }
    .automation-tick-content { min-width: 0; padding-bottom: 1rem; }

    .automation-open-tick {
        width: 100%;
        min-height: 42px;
        padding: .55rem .7rem;
        display: flex;
        align-items: center;
        gap: .55rem;
        color: var(--automation-muted);
        text-align: left;
        background: transparent;
        border: 1px solid transparent;
    }
    .automation-open-tick:hover,
    .automation-open-tick:focus-visible,
    .automation-open-tick[aria-expanded="true"] {
        color: var(--automation-command);
        background: var(--automation-command-soft);
        border-color: var(--automation-rule);
        outline: none;
    }
    .automation-open-tick:disabled { opacity: .55; }
    .automation-open-tick-form,
    .automation-inline-form {
        padding: 1rem;
        background: var(--automation-paper-raised);
        border: 1px solid var(--automation-rule);
        border-top: 0;
    }

    .automation-tick-card {
        overflow: hidden;
        background: var(--automation-paper-raised);
        border: 1px solid var(--automation-rule);
        border-left: 4px solid var(--automation-command);
        box-shadow: 0 3px 9px color-mix(in srgb, var(--automation-ink) 8%, transparent);
    }

    .automation-tick-head {
        min-height: 64px;
        padding: .75rem .85rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        background: var(--automation-paper-alt);
        border-bottom: 1px solid var(--automation-rule-soft);
    }
    .automation-tick-title strong { display: block; text-transform: uppercase; letter-spacing: .03em; }
    .automation-tick-title span { display: block; margin-top: .2rem; color: var(--automation-muted); font-size: .78rem; }
    .automation-tick-tools { display: flex; gap: .4rem; }
    .automation-copy-tick,
    .automation-clear-tick,
    .automation-template-replace,
    .automation-template-save {
        color: var(--automation-ink);
        background: var(--automation-paper-raised);
        border-color: var(--automation-rule);
    }
    .automation-copy-tick:hover,
    .automation-copy-tick:focus-visible,
    .automation-template-replace:hover,
    .automation-template-replace:focus-visible,
    .automation-template-save:hover,
    .automation-template-save:focus-visible {
        color: var(--automation-command);
        background: var(--automation-command-soft);
        border-color: var(--automation-command);
    }
    .automation-clear-tick { width: 40px; color: var(--automation-danger); }
    .automation-clear-tick:hover,
    .automation-clear-tick:focus-visible { color: #fff; background: var(--automation-danger); }

    .automation-action-list { display: block; }
    .action-display-row {
        min-height: 64px;
        padding: .65rem .75rem;
        display: grid;
        grid-template-columns: 28px 32px minmax(0, 1fr) auto;
        align-items: center;
        gap: .45rem;
        border-bottom: 1px solid var(--automation-rule-soft);
        background: var(--automation-paper-raised);
    }
    .action-display-row.is-dragging { opacity: .45; }
    .action-display-row.drop-before { box-shadow: inset 0 3px 0 var(--automation-command); }
    .action-display-row.drop-after { box-shadow: inset 0 -3px 0 var(--automation-command); }
    .action-order-number { color: var(--automation-muted); font-size: .72rem; font-variant-numeric: tabular-nums; }
    .action-drag-handle {
        width: 32px;
        height: 40px;
        padding: 0;
        color: var(--automation-muted);
        background: transparent;
        border: 0;
        cursor: grab;
        touch-action: none;
    }
    .action-drag-handle:active { cursor: grabbing; }
    .action-drag-handle:disabled { opacity: .28; cursor: default; }
    .automation-action-copy { min-width: 0; }
    .automation-action-copy strong { display: block; overflow-wrap: anywhere; font-size: 1rem; }
    .automation-action-copy > span {
        display: block;
        margin-top: .12rem;
        color: var(--automation-muted);
        font-size: .67rem;
        text-transform: uppercase;
        letter-spacing: .07em;
    }
    .automation-action-tools { display: flex; align-items: center; gap: .15rem; }
    .automation-action-tools form { margin: 0; }
    .automation-action-tool {
        width: 36px;
        height: 36px;
        padding: 0;
        color: var(--automation-muted);
        background: transparent;
        border: 1px solid transparent;
    }
    .automation-action-tool:hover,
    .automation-action-tool:focus-visible { color: var(--automation-command); border-color: var(--automation-rule); }
    .automation-action-tool.is-danger:hover,
    .automation-action-tool.is-danger:focus-visible { color: var(--automation-danger); }
    .automation-action-tool:disabled { opacity: .35; }

    .automation-tick-foot {
        min-height: 54px;
        padding: .55rem .75rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }
    .automation-tick-foot > span { color: var(--automation-muted); font-size: .72rem; }
    .automation-add-action { color: var(--automation-ink); background: transparent; border-color: transparent; }
    .automation-add-action:hover,
    .automation-add-action:focus-visible { color: var(--automation-command); border-color: var(--automation-rule); }

    .automation-playbooks { display: grid; gap: 1rem; }
    .automation-playbook-panel,
    .automation-execution-rules {
        border: 1px solid var(--automation-rule);
        background: var(--automation-paper-alt);
    }
    .automation-playbook-head { padding: 1rem; border-bottom: 1px solid var(--automation-rule); }
    .automation-playbook-head h2 { margin: 0; font-size: .96rem; }
    .automation-playbook-head p { margin: .45rem 0 0; color: var(--automation-muted); font-size: .78rem; line-height: 1.45; }
    .automation-playbook-list { padding: .65rem; display: grid; gap: .65rem; }
    .automation-template {
        position: relative;
        padding: .85rem;
        overflow: hidden;
        border: 1px solid var(--automation-rule-soft);
        background: var(--automation-paper-raised);
    }
    .automation-template::after {
        content: attr(data-slot);
        position: absolute;
        top: .15rem;
        right: .55rem;
        color: color-mix(in srgb, var(--automation-brass) 55%, transparent);
        font-size: 1.6rem;
        line-height: 1;
    }
    .automation-template.is-empty { border-style: dashed; text-align: center; }
    .automation-template-copy { padding-right: 2rem; }
    .automation-template-copy h3 {
        margin: 0;
        font-size: .88rem;
        text-transform: uppercase;
        letter-spacing: .055em;
    }
    .automation-template-copy p,
    .automation-template-empty-copy p { margin: .4rem 0 .65rem; color: var(--automation-muted); font-size: .78rem; }
    .automation-template-empty-copy strong { text-transform: uppercase; letter-spacing: .06em; font-size: .75rem; }
    .automation-template-delete { position: absolute; z-index: 1; top: .35rem; right: 2.35rem; }
    .template-offsets { margin-bottom: .65rem; display: flex; flex-wrap: wrap; gap: .3rem; }
    .template-offsets span {
        padding: .15rem .4rem;
        color: var(--automation-command);
        background: var(--automation-command-soft);
        border: 1px solid color-mix(in srgb, var(--automation-command) 30%, var(--automation-rule));
        font-size: .7rem;
        font-weight: 700;
    }
    .automation-template-actions { display: grid; grid-template-columns: minmax(0, 1fr) 40px; gap: .45rem; }
    .automation-template-replace { width: 40px; }
    .automation-template-save { width: 100%; }
    .template-load-preview { display: flex; flex-wrap: wrap; gap: .4rem; }
    .template-load-preview span { padding: .4rem .55rem; border: 1px solid var(--automation-rule); background: var(--automation-paper); }

    .automation-execution-rules summary { padding: .75rem .85rem; cursor: pointer; font-size: .76rem; }
    .automation-execution-rules > div { padding: 0 .85rem .8rem; }
    .automation-execution-rules ul { margin: 0; padding-left: 1.1rem; color: var(--automation-muted); font-size: .78rem; line-height: 1.55; }

    .automation-choice-list { display: grid; gap: .5rem; }
    .automation-choice {
        min-height: 68px;
        padding: .75rem .85rem;
        display: grid;
        grid-template-columns: 30px minmax(0, 1fr);
        align-items: center;
        gap: .85rem;
        border: 1px solid var(--od-border-accent, var(--bs-border-color));
        background: var(--od-surface, var(--bs-body-bg));
        cursor: pointer;
    }
    .automation-choice:has(input:checked) {
        border-color: var(--od-primary, var(--bs-primary));
        background: var(--od-primary-bg-soft, var(--bs-tertiary-bg));
    }
    .automation-choice.is-disabled { opacity: .55; cursor: not-allowed; }
    .automation-choice input { margin: 0; }
    .automation-choice strong,
    .automation-choice small { display: block; }
    .automation-choice strong { margin-bottom: .25rem; }
    .automation-choice small { color: var(--od-text-secondary, var(--bs-secondary-color)); line-height: 1.4; }

    .automation-quick-fill {
        --quick-fill-surface: var(--od-surface, var(--bs-secondary-bg));
        --quick-fill-text: var(--od-text-body, var(--bs-body-color));
        --quick-fill-muted: var(--od-text-secondary, var(--bs-secondary-color));
        --quick-fill-border: var(--od-border-accent, var(--bs-border-color));
        --quick-fill-accent: var(--od-primary, var(--bs-primary));
        --quick-fill-active: var(--od-primary-bg-soft, color-mix(in srgb, var(--quick-fill-accent) 14%, var(--quick-fill-surface)));
        padding: .75rem;
        border: 1px solid var(--quick-fill-border);
        background: var(--quick-fill-surface);
    }
    .quick-fill-manage { color: var(--quick-fill-muted); text-decoration: none; }
    .quick-fill-combobox { position: relative; }
    .quick-fill-combobox input[aria-expanded="true"] { border-color: var(--quick-fill-accent); box-shadow: inset 3px 0 0 var(--quick-fill-accent); }
    .quick-fill-popover {
        position: absolute;
        inset-inline: 0;
        top: 100%;
        z-index: 1055;
        margin-top: -1px;
        color: var(--quick-fill-text);
        background: var(--quick-fill-surface);
        border: 1px solid var(--quick-fill-border);
        box-shadow: 0 .75rem 1.75rem rgba(0, 0, 0, .18);
    }
    .quick-fill-popover[hidden] { display: none; }
    .quick-fill-listbox { max-height: min(280px, 42vh); margin: 0; padding: .25rem; overflow-y: auto; list-style: none; }
    .quick-fill-option {
        min-height: 46px;
        padding: .5rem .65rem;
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: center;
        gap: .75rem;
        border-left: 3px solid transparent;
        cursor: pointer;
    }
    .quick-fill-option + .quick-fill-option { border-top: 1px solid color-mix(in srgb, var(--quick-fill-border) 55%, transparent); }
    .quick-fill-option[aria-selected="true"], .quick-fill-option:hover { background: var(--quick-fill-active); border-left-color: var(--quick-fill-accent); }
    .quick-fill-option strong { min-width: 0; font-size: .95rem; line-height: 1.25; }
    .quick-fill-option span { color: var(--quick-fill-muted); font-size: .7rem; font-weight: 600; text-align: right; }
    .quick-fill-foot { padding: .4rem .65rem; color: var(--quick-fill-muted); border-top: 1px solid var(--quick-fill-border); font-size: .75rem; }
    .quick-fill-status { min-height: 1.25rem; margin-top: .25rem; color: var(--quick-fill-muted); font-size: .85rem; }
    .quick-fill-status.is-matched { color: var(--od-success, var(--bs-success)); }
    .quick-fill-status.is-matched::before { content: "✓ "; font-weight: 700; }
    .quick-fill-manager-summary { padding: .65rem .75rem; border-left: 3px solid var(--od-primary, var(--bs-primary)); background: var(--od-surface, var(--bs-tertiary-bg)); }
    .quick-fill-manager-list { display: grid; gap: .5rem; }
    .quick-fill-manager-row { padding: .65rem; display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: .4rem .6rem; border: 1px solid var(--od-border-accent, var(--bs-border-color)); }
    .quick-fill-manager-tools { display: flex; gap: .25rem; }
    .quick-fill-manager-state { grid-column: 1 / -1; min-height: 1.1rem; color: var(--od-text-secondary, var(--bs-secondary-color)); font-size: .8rem; }
    .quick-fill-manager-state.is-valid { color: var(--od-success, var(--bs-success)); }
    .quick-fill-manager-state.is-invalid { color: var(--od-danger, var(--bs-danger)); }

    @media (max-width: 991.98px) {
        .automation-workspace { grid-template-columns: 1fr; }
        .automation-playbooks { grid-template-columns: minmax(0, 1fr) minmax(250px, .55fr); }
    }

    @media (max-width: 575.98px) {
        .automation-studio-header,
        .automation-status-strip,
        .automation-workspace { padding-inline: .45rem; }
        .automation-studio-header { padding-block: .65rem; }
        .automation-studio-header .card-title { font-size: 1rem; }
        .automation-studio-header p { margin-top: .2rem; font-size: .76rem; }
        .automation-status-strip { padding-block: .55rem; align-items: stretch; flex-direction: column; gap: .45rem; }
        .automation-current-tick { gap: .4rem; font-size: .78rem; }
        .automation-current-tick span { white-space: nowrap; }
        .automation-quota { width: 100%; }
        .automation-quota-copy { margin-bottom: .25rem; font-size: .7rem; }
        .automation-quota-track i { height: 5px; }
        .automation-workspace { padding-block: .65rem; }
        .automation-section-label { margin-bottom: .45rem; font-size: .7rem; }
        .automation-timeline::before { left: 45px; }
        .automation-tick-row { grid-template-columns: 37px 18px minmax(0, 1fr); min-height: 54px; }
        .automation-tick-time { padding-top: .5rem; }
        .automation-tick-time strong { font-size: .68rem; }
        .automation-tick-time span { display: none; }
        .automation-tick-mark { width: 12px; height: 12px; margin-top: .5rem; }
        .automation-tick-content { padding-bottom: .55rem; }
        .automation-open-tick { min-height: 36px; padding: .35rem .25rem; gap: .35rem; }
        .automation-open-tick span { font-size: .72rem; }
        .automation-tick-head { min-height: 46px; padding: .4rem .45rem; }
        .automation-tick-title strong { font-size: .76rem; }
        .automation-tick-title span { display: none; }
        .automation-tick-tools { gap: .2rem; }
        .automation-copy-tick span { display: none; }
        .automation-copy-tick,
        .automation-clear-tick { width: 34px; min-height: 34px; padding: 0; }
        .action-display-row {
            min-height: 48px;
            padding: .28rem .3rem;
            grid-template-columns: 28px minmax(0, 1fr) auto;
            gap: .25rem;
        }
        .action-order-number { display: none; }
        .action-drag-handle { width: 28px; height: 38px; }
        .automation-action-copy { grid-column: 2; }
        .automation-action-copy strong {
            overflow: hidden;
            font-size: .82rem;
            line-height: 1.15;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .automation-action-copy > span { margin-top: .08rem; font-size: .55rem; }
        .automation-action-tools { grid-column: 3; justify-content: flex-end; gap: 0; }
        .automation-action-tool { width: 30px; height: 36px; }
        .automation-tick-foot { min-height: 42px; padding: .3rem .4rem; }
        .automation-add-action { padding: .3rem .4rem; font-size: .75rem; }
        .automation-tick-foot > span { font-size: .62rem; }
        .automation-playbooks { grid-template-columns: 1fr; }
        .automation-playbook-head { padding: .7rem; }
        .automation-playbook-head h2 { font-size: .82rem; }
        .automation-playbook-head p { margin-top: .25rem; font-size: .7rem; }
        .automation-playbook-list { padding: .4rem; gap: .4rem; }
        .automation-template { padding: .65rem; }
        .automation-template-copy h3 { font-size: .76rem; }
        .automation-template-copy p,
        .automation-template-empty-copy p { margin-block: .25rem .45rem; font-size: .7rem; }
        .quick-fill-option { min-height: 48px; grid-template-columns: 1fr; gap: .15rem; }
        .quick-fill-option span { text-align: left; }
        .quick-fill-manager-row { grid-template-columns: 1fr; }
        .quick-fill-manager-tools .btn { min-width: 44px; min-height: 44px; flex: 1; }
    }

    @media (prefers-reduced-motion: reduce) {
        .automation-template,
        .quick-fill-popover,
        .action-display-row,
        .automation-open-tick { transition: none !important; }
    }
</style>
