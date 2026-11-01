// The timings viewer: renders a report uploaded by a running Basalt Server, entirely in the browser.

const NS = 'http://www.w3.org/2000/svg';
const OPEN_LIMIT = 4000;

const esc = (value) =>
    String(value).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

function fmtMs(value) {
    if (value == null || Number.isNaN(value)) return '-';
    if (value >= 60000) return `${(value / 60000).toFixed(1)} min`;
    if (value >= 1000) return `${(value / 1000).toFixed(2)} s`;
    if (value >= 100) return `${value.toFixed(0)} ms`;
    if (value >= 10) return `${value.toFixed(1)} ms`;
    return `${value.toFixed(2)} ms`;
}

function fmtBytes(value) {
    if (value == null) return '-';
    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
    let index = 0;
    let n = value;
    while (n >= 1024 && index < units.length - 1) {
        n /= 1024;
        index++;
    }
    return `${n.toFixed(index === 0 ? 0 : 1)} ${units[index]}`;
}

function fmtDuration(seconds) {
    seconds = Math.round(seconds || 0);
    if (seconds < 60) return `${seconds}s`;
    if (seconds < 3600) return `${Math.floor(seconds / 60)}m ${seconds % 60}s`;
    if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ${Math.floor((seconds % 3600) / 60)}m`;
    return `${Math.floor(seconds / 86400)}d ${Math.floor((seconds % 86400) / 3600)}h`;
}

const fmtClock = (seconds) => `${Math.floor(seconds / 60)}:${String(Math.floor(seconds % 60)).padStart(2, '0')}`;
const num = (value) => (value == null ? '-' : Number(value).toLocaleString('en-US'));
const fixed = (value, digits = 1) => (value == null ? '-' : Number(value).toFixed(digits));

const toneTps = (v) => (v >= 19 ? 'tv-good' : v >= 15 ? 'tv-warn' : 'tv-bad');
const toneMspt = (v) => (v <= 35 ? 'tv-good' : v <= 50 ? 'tv-warn' : 'tv-bad');
const tonePercent = (v) => (v < 60 ? 'tv-good' : v < 85 ? 'tv-warn' : 'tv-bad');
const heatOf = (share) => (share >= 0.5 ? 3 : share >= 0.2 ? 2 : share >= 0.08 ? 1 : 0);

const sum = (values) => values.reduce((a, b) => a + b, 0);
const mean = (values) => (values.length ? sum(values) / values.length : null);

function svg(tag, attrs = {}, html = '') {
    const el = document.createElementNS(NS, tag);
    for (const [k, v] of Object.entries(attrs)) el.setAttribute(k, v);
    if (html) el.innerHTML = html;
    return el;
}

// ---------------------------------------------------------------------------------------------------------------
// Profile model

class Profile {
    constructor(data) {
        this.data = data;
        this.threads = (data.threads || []).map((t) => ({ name: t.name, root: t.root }));
        this.windowSeconds = data.metadata?.window_seconds || 10;
        this.windowCount = Math.max(0, ...this.threads.map((t) => t.root.window_millis?.length || 0));
        this.selection = null;
        this.parents = new WeakMap();
        for (const thread of this.threads) this.index(thread.root, null);
    }

    index(node, parent) {
        this.parents.set(node, parent);
        for (const child of node.children || []) this.index(child, node);
    }

    setSelection(selection) {
        this.selection = selection;
    }

    value(node) {
        const s = this.selection;
        if (!s || !node.window_millis) return node.total_millis;
        let total = 0;
        for (let i = s.from; i <= s.to && i < node.window_millis.length; i++) total += node.window_millis[i];
        return total;
    }

    calls(node) {
        const s = this.selection;
        if (!s || !node.window_calls) return node.calls;
        let total = 0;
        for (let i = s.from; i <= s.to && i < node.window_calls.length; i++) total += node.window_calls[i];
        return total;
    }

    self(node) {
        return Math.max(this.value(node) - sum((node.children || []).map((c) => this.value(c))), 0);
    }

    rootValue(thread) {
        return sum((thread.root.children || []).map((c) => this.value(c)));
    }

    get total() {
        return sum(this.threads.map((t) => this.rootValue(t)));
    }
}

// A virtual node is what a view shows: a name, numbers, and a lazy list of children.
function vnodeOf(profile, node, denominator) {
    const v = {
        name: node.name,
        value: profile.value(node),
        self: profile.self(node),
        calls: profile.calls(node),
        denominator,
        raw: node,
    };
    v.children = () =>
        (v._kids ??= (node.children || [])
            .filter((c) => profile.value(c) > 0)
            .sort((a, b) => profile.value(b) - profile.value(a))
            .map((c) => vnodeOf(profile, c, denominator)));
    return v;
}

// Nodes with the same name become one, which is what the flat and plugin views need.
function mergeNodes(profile, nodes, denominator, exclude) {
    const first = nodes[0];
    const v = {
        name: first.name,
        value: sum(nodes.map((n) => profile.value(n))),
        calls: sum(nodes.map((n) => profile.calls(n))),
        denominator,
    };
    v.self = Math.max(v.value - sum(nodes.flatMap((n) => n.children || []).map((c) => profile.value(c))), 0);
    v.children = () => {
        if (v._kids) return v._kids;
        const groups = new Map();
        for (const node of nodes) {
            for (const child of node.children || []) {
                if (exclude?.(child) || profile.value(child) <= 0) continue;
                if (!groups.has(child.name)) groups.set(child.name, []);
                groups.get(child.name).push(child);
            }
        }
        return (v._kids = [...groups.values()]
            .map((list) => mergeNodes(profile, list, denominator, exclude))
            .sort((a, b) => b.value - a.value));
    };
    return v;
}

function flatEntries(profile) {
    const total = profile.total;
    const entries = new Map();
    const walk = (node, path) => {
        const seen = path.has(node.name);
        let entry = entries.get(node.name);
        if (!entry) entries.set(node.name, (entry = { name: node.name, total: 0, self: 0, calls: 0, nodes: [] }));
        entry.self += profile.self(node);
        entry.calls += profile.calls(node);
        if (!seen) entry.total += profile.value(node);
        entry.nodes.push(node);
        path.set(node.name, (path.get(node.name) || 0) + 1);
        for (const child of node.children || []) if (profile.value(child) > 0) walk(child, path);
        path.set(node.name, path.get(node.name) - 1);
        if (path.get(node.name) === 0) path.delete(node.name);
    };
    for (const thread of profile.threads) {
        for (const child of thread.root.children || []) if (profile.value(child) > 0) walk(child, new Map());
    }
    return [...entries.values()]
        .filter((e) => e.self > 0 || e.total > 0)
        .sort((a, b) => b.self - a.self)
        .map((e) => flatNode(profile, e.name, e.self, e.calls, e.nodes, total, e.total));
}

// The callers of some frames: bottom-up, so a hot frame can be followed back to what ran it.
function flatNode(profile, name, value, calls, nodes, denominator, total) {
    const v = { name, value, self: value, calls, denominator, total };
    v.children = () => {
        if (v._kids) return v._kids;
        const groups = new Map();
        for (const node of nodes) {
            const parent = profile.parents.get(node);
            if (!parent) continue;
            const label = profile.parents.get(parent) ? parent.name : null;
            if (label == null) {
                const thread = profile.threads.find((t) => t.root === parent);
                const key = `thread:${thread?.name ?? '?'}`;
                if (!groups.has(key)) groups.set(key, { name: thread?.name ?? 'thread', value: 0, calls: 0, nodes: [], thread: true });
                groups.get(key).value += profile.value(node);
                groups.get(key).calls += profile.calls(node);
                continue;
            }
            if (!groups.has(label)) groups.set(label, { name: label, value: 0, calls: 0, nodes: [] });
            const group = groups.get(label);
            group.value += profile.value(node);
            group.calls += profile.calls(node);
            group.nodes.push(parent);
        }
        return (v._kids = [...groups.values()]
            .sort((a, b) => b.value - a.value)
            .map((g) => {
                const kid = flatNode(profile, g.name, g.value, g.calls, g.nodes, denominator, g.value);
                kid.thread = g.thread;
                return kid;
            }));
    };
    return v;
}

const isPlugin = (node) => node.name.startsWith('plugin.');

function sourceEntries(profile) {
    const total = profile.total;
    const plugins = new Map();
    const collect = (node) => {
        if (isPlugin(node)) {
            const id = node.name.slice('plugin.'.length);
            if (!plugins.has(id)) plugins.set(id, []);
            plugins.get(id).push(node);
            return;
        }
        for (const child of node.children || []) collect(child);
    };
    for (const thread of profile.threads) collect(thread.root);

    const entries = [...plugins.entries()].map(([id, nodes]) => {
        const merged = mergeNodes(profile, nodes, total);
        merged.name = id;
        merged.label = 'plugin';
        return merged;
    });
    const pluginTotal = sum(entries.map((e) => e.value));
    const roots = profile.threads.flatMap((t) => (t.root.children || []).filter((c) => !isPlugin(c)));
    if (roots.length) {
        const core = {
            name: 'Basalt (core)',
            value: Math.max(total - pluginTotal, 0),
            self: 0,
            calls: 0,
            denominator: total,
            label: 'core',
        };
        const groups = new Map();
        for (const node of roots) {
            if (!groups.has(node.name)) groups.set(node.name, []);
            groups.get(node.name).push(node);
        }
        core.children = () =>
            (core._kids ??= [...groups.values()]
                .map((list) => mergeNodes(profile, list, total, isPlugin))
                .filter((m) => m.value > 0)
                .sort((a, b) => b.value - a.value));
        entries.push(core);
    }
    return entries.sort((a, b) => b.value - a.value);
}

// ---------------------------------------------------------------------------------------------------------------
// Charts

function lineChart(host, { title, unit, series, seconds, yMax, format, note }) {
    const wrap = document.createElement('div');
    wrap.className = 'glass tv-chart';
    const points = seconds.length;
    wrap.innerHTML = `<h3><span>${esc(title)}</span><span class="tv-dim tv-mono" data-now></span></h3>`;
    host.appendChild(wrap);

    const live = series.filter((s) => s.values.some((v) => v != null));
    if (points < 2 || !live.length) {
        wrap.insertAdjacentHTML('beforeend', `<p class="tv-note">${esc(note || 'Not enough data: profile for longer than a few seconds.')}</p>`);
        return;
    }

    const W = 600;
    const H = 150;
    const all = live.flatMap((s) => s.values.filter((v) => v != null));
    const top = yMax ?? (Math.max(...all) * 1.1 || 1);
    const x = (i) => (i / (points - 1)) * W;
    const y = (v) => H - (Math.min(v, top) / top) * H;

    const el = svg('svg', { viewBox: `0 0 ${W} ${H}`, preserveAspectRatio: 'none' });
    for (let g = 0; g <= 3; g++) {
        const gy = (g / 3) * H;
        el.appendChild(svg('line', { x1: 0, x2: W, y1: gy, y2: gy, stroke: 'rgba(255,255,255,.07)', 'vector-effect': 'non-scaling-stroke' }));
    }
    for (const s of live) {
        let path = '';
        s.values.forEach((v, i) => {
            if (v == null) return;
            path += `${path ? 'L' : 'M'}${x(i).toFixed(1)} ${y(v).toFixed(1)}`;
        });
        el.appendChild(svg('path', { d: path, fill: 'none', stroke: s.color, 'stroke-width': 1.6, 'vector-effect': 'non-scaling-stroke', 'stroke-linejoin': 'round' }));
    }
    const cursor = svg('line', { y1: 0, y2: H, stroke: 'rgba(255,255,255,.4)', 'vector-effect': 'non-scaling-stroke', visibility: 'hidden' });
    el.appendChild(cursor);
    wrap.appendChild(el);

    const axis = document.createElement('div');
    axis.className = 'tv-legend tv-mono';
    axis.style.justifyContent = 'space-between';
    axis.innerHTML = `<span>${fmtClock(seconds[0])}</span><span>0 - ${format(top)}</span><span>${fmtClock(seconds[points - 1])}</span>`;
    wrap.appendChild(axis);
    const legend = document.createElement('div');
    legend.className = 'tv-legend';
    legend.innerHTML = live.map((s) => `<span><i style="background:${s.color}"></i>${esc(s.name)}</span>`).join('');
    wrap.appendChild(legend);

    const last = live.map((s) => format(s.values[points - 1] ?? 0)).join(' / ');
    wrap.querySelector('[data-now]').textContent = last;

    const tip = document.createElement('div');
    tip.className = 'tv-tip';
    wrap.appendChild(tip);
    el.addEventListener('mousemove', (event) => {
        const box = el.getBoundingClientRect();
        const i = Math.max(0, Math.min(points - 1, Math.round(((event.clientX - box.left) / box.width) * (points - 1))));
        cursor.setAttribute('x1', x(i));
        cursor.setAttribute('x2', x(i));
        cursor.setAttribute('visibility', 'visible');
        tip.innerHTML =
            `<b>${fmtClock(seconds[i])}</b><br>` +
            live.map((s) => `<span style="color:${s.color}">${esc(s.name)}</span> ${s.values[i] == null ? '-' : format(s.values[i])}`).join('<br>');
        tip.style.display = 'block';
        const wrapBox = wrap.getBoundingClientRect();
        const left = event.clientX - wrapBox.left + 14;
        tip.style.left = `${Math.min(left, wrapBox.width - tip.offsetWidth - 8)}px`;
        tip.style.top = `${event.clientY - wrapBox.top - 10}px`;
    });
    el.addEventListener('mouseleave', () => {
        cursor.setAttribute('visibility', 'hidden');
        tip.style.display = 'none';
    });
}

// ---------------------------------------------------------------------------------------------------------------
// The viewer

export function mount(root) {
    if (root.dataset.ready) return;
    root.dataset.ready = '1';

    fetch(root.dataset.src, { headers: { Accept: 'application/json' } })
        .then((r) => {
            if (!r.ok) throw new Error(r.status);
            return r.json();
        })
        .then((data) => new Viewer(root, data).render())
        .catch(() => {
            root.innerHTML = '<p class="tv-note">This report could not be loaded.</p>';
        });
}

class Viewer {
    constructor(root, data) {
        this.root = root;
        this.data = data;
        this.profile = new Profile(data);
        this.state = { view: 'all', label: 'percent', selfMode: false, query: '', info: false, tab: 'platform', metrics: true, flame: null };
        this.meta = data.metadata || {};
        this.server = data.server || {};
        this.system = data.system || {};
        this.metrics = data.metrics || {};
        this.root.classList.add('tv');
        this.root._viewer = this;
    }

    render() {
        const user = this.meta.user ?? 'Console';
        const started = this.meta.start_epoch_millis ? new Date(this.meta.start_epoch_millis).toLocaleString() : (this.data.generated ?? '');
        const platform = this.meta.platform || {};
        this.root.innerHTML = `
            <div class="glass tv-bar" data-controls>
                <div class="tv-title"><b>Basalt ${esc(platform.version || this.data.basalt || '')}</b> profile
                    <small>${esc(user)} @ ${esc(started)}, profiled ${fmtDuration(this.meta.measured_seconds ?? this.data.measured_seconds)}</small></div>
                <button class="tv-btn is-solo" data-act="info" aria-pressed="false">Info</button>
                <button class="tv-btn is-solo" data-act="metrics" aria-pressed="true">Metrics</button>
                <div class="tv-group">
                    <button class="tv-btn" data-view="all" aria-pressed="true">All</button>
                    <button class="tv-btn" data-view="flat" aria-pressed="false">Flat</button>
                    <button class="tv-btn" data-view="plugins" aria-pressed="false">Plugins</button>
                    <button class="tv-btn" data-view="flame" aria-pressed="false">Flame</button>
                </div>
                <input class="tv-search" type="search" placeholder="Search frames" data-search>
            </div>
            <div class="tv-widgets" data-widgets style="margin-top:.75rem"></div>
            <div data-info hidden style="margin-top:.75rem"></div>
            <div data-metrics style="margin-top:.75rem"></div>
            <div data-windows style="margin-top:.75rem"></div>
            <div data-profile></div>`;
        this.widgets();
        this.info();
        this.charts();
        this.windows();
        this.profileView();
        this.bind();
    }

    bind() {
        const root = this.root;
        root.querySelector('[data-controls]').addEventListener('click', (event) => {
            const button = event.target.closest('button');
            if (!button) return;
            if (button.dataset.view) {
                this.state.view = button.dataset.view;
                this.state.flame = null;
                root.querySelectorAll('[data-view]').forEach((b) => b.setAttribute('aria-pressed', String(b === button)));
                this.profileView();
            } else if (button.dataset.act === 'info') {
                this.state.info = !this.state.info;
                button.setAttribute('aria-pressed', String(this.state.info));
                root.querySelector('[data-info]').hidden = !this.state.info;
            } else if (button.dataset.act === 'metrics') {
                this.state.metrics = !this.state.metrics;
                button.setAttribute('aria-pressed', String(this.state.metrics));
                root.querySelector('[data-metrics]').hidden = !this.state.metrics;
                root.querySelector('[data-windows]').hidden = !this.state.metrics;
            }
        });
        let timer;
        root.querySelector('[data-search]').addEventListener('input', (event) => {
            clearTimeout(timer);
            timer = setTimeout(() => {
                this.state.query = event.target.value.trim().toLowerCase();
                this.profileView();
            }, 180);
        });
    }

    // -- widgets ------------------------------------------------------------------------------------------------

    widgets() {
        const ticks = this.data.ticks || {};
        const perSecond = ticks.per_second || {};
        const durations = ticks.duration_millis || {};
        const spans = durations['5m']?.samples ? durations['5m'] : durations['1m'] || {};
        const m = this.metrics;
        const cpuNow = this.server.cpu_process_percent;
        const cpuSys = this.server.cpu_system_percent;
        const rss = this.server.process_resident_bytes;
        const memTotal = this.system.memory_total;
        const meanOf = (key) => mean((m[key] || []).filter((v) => v != null));
        const cell = (label, value, tone, text) =>
            `<div class="tv-value"><b class="${tone}">${text ?? value}</b><small>${label}</small></div>`;
        const widget = (title, sub, body, meter) =>
            `<div class="glass tv-widget"><h3>${title} <span>${sub}</span></h3>${body}${meter ?? ''}</div>`;

        const tps = ['1m', '5m', '15m']
            .filter((k) => perSecond[k] != null)
            .map((k) => cell(k, perSecond[k], toneTps(perSecond[k]), fixed(perSecond[k], 2)))
            .join('');
        const mspt = spans.samples
            ? [['min', spans.min], ['med', spans.median], ['95%ile', spans.p95], ['max', spans.max]]
                  .map(([l, v]) => cell(l, v, toneMspt(v), fixed(v, 1)))
                  .join('')
            : '';
        const cpuProc = [['now', cpuNow], ['avg', meanOf('cpu_process')]]
            .filter(([, v]) => v != null)
            .map(([l, v]) => cell(l, v, tonePercent(v), `${fixed(v, 1)}%`))
            .join('');
        const cpuSystem = [['now', cpuSys], ['avg', meanOf('cpu_system')]]
            .filter(([, v]) => v != null)
            .map(([l, v]) => cell(l, v, tonePercent(v), `${fixed(v, 1)}%`))
            .join('');
        const memPct = rss != null && memTotal ? (rss / memTotal) * 100 : null;
        const memory =
            rss != null
                ? `<div class="tv-values">${cell('process', rss, 'tv-mono', fmtBytes(rss))}${memTotal ? cell('physical', 0, 'tv-dim', fmtBytes(memTotal)) : ''}</div>`
                : '';
        const disk = this.system.disk_total
            ? `<div class="tv-values">${cell('used', 0, 'tv-mono', fmtBytes(this.system.disk_used))}${cell('total', 0, 'tv-dim', fmtBytes(this.system.disk_total))}</div>`
            : '';

        const html = [
            tps && widget('TPS', '', `<div class="tv-values">${tps}</div>`),
            mspt && widget('MSPT', 'ms', `<div class="tv-values">${mspt}</div>`),
            cpuProc && widget('CPU', '(process)', `<div class="tv-values">${cpuProc}</div>`),
            cpuSystem && widget('CPU', '(system)', `<div class="tv-values">${cpuSystem}</div>`),
            memory && widget('Memory', '(process)', memory, memPct != null ? `<div class="tv-meter"><i style="width:${Math.min(memPct, 100)}%"></i></div>` : ''),
            disk &&
                widget('Disk', '', disk, `<div class="tv-meter"><i style="width:${Math.min((this.system.disk_used / this.system.disk_total) * 100, 100)}%"></i></div>`),
        ]
            .filter(Boolean)
            .join('');
        this.root.querySelector('[data-widgets]').innerHTML = html;
    }

    // -- info panel ---------------------------------------------------------------------------------------------

    info() {
        const host = this.root.querySelector('[data-info]');
        const tabs = [
            ['platform', 'Platform'],
            ['system', 'System'],
            ['world', 'Worlds'],
            ['config', 'Configuration'],
            ['plugins', `Plugins (${(this.data.plugins || []).length})`],
        ];
        host.innerHTML = `<div class="glass tv-panel"><div class="tv-tabs">${tabs
            .map(([id, label]) => `<button class="tv-btn ${id === this.state.tab ? 'is-on' : ''}" data-tab="${id}">${label}</button>`)
            .join('')}</div><div data-tab-body></div></div>`;
        host.addEventListener('click', (event) => {
            const tab = event.target.closest('[data-tab]');
            if (!tab) return;
            this.state.tab = tab.dataset.tab;
            host.querySelectorAll('[data-tab]').forEach((b) => b.classList.toggle('is-on', b === tab));
            this.tabBody();
        });
        this.tabBody();
    }

    tabBody() {
        const body = this.root.querySelector('[data-tab-body]');
        const kv = (rows) =>
            `<dl class="tv-kv">${rows
                .filter(([, v]) => v != null && v !== '')
                .map(([k, v]) => `<dt>${esc(k)}</dt><dd>${esc(v)}</dd>`)
                .join('')}</dl>`;
        const meter = (label, used, total) =>
            total
                ? `<div style="margin-top:1rem"><div class="tv-hbar"><span class="name">${label}</span><span class="num">${fmtBytes(used)} / ${fmtBytes(total)}</span><div class="track"><i style="width:${Math.min((used / total) * 100, 100)}%"></i></div></div></div>`
                : '';
        const { platform = {}, user } = this.meta;
        const s = this.system;
        const sv = this.server;

        if (this.state.tab === 'platform') {
            body.innerHTML = kv([
                ['Server', `${platform.brand || 'Basalt'} ${platform.version || ''}`],
                ['Minecraft', platform.minecraft ? `${platform.minecraft} (protocol ${platform.protocol})` : null],
                ['Uptime', sv.uptime_seconds != null ? fmtDuration(sv.uptime_seconds) : null],
                ['Players', sv.players_online != null ? `${sv.players_online} / ${sv.players_max}` : null],
                ['Profiled by', user],
                ['Profiled for', fmtDuration(this.meta.measured_seconds)],
                ['Window size', `${this.meta.window_seconds || 10}s`],
                ['Chunks loaded', sv.chunks_loaded != null ? num(sv.chunks_loaded) : null],
                ['Chunk data in memory', sv.chunk_bytes != null ? fmtBytes(sv.chunk_bytes) : null],
                ['Region cache', sv.region_cache_bytes != null ? fmtBytes(sv.region_cache_bytes) : null],
                ['Block entities', sv.chunk_block_entities != null ? num(sv.chunk_block_entities) : null],
                ['Scheduled ticks', sv.chunk_scheduled_ticks != null ? num(sv.chunk_scheduled_ticks) : null],
                ['Worldgen templates', sv.worldgen_templates ? `${num(sv.worldgen_templates)} (${num(sv.worldgen_template_blocks)} blocks)` : null],
            ]);
        } else if (this.state.tab === 'system') {
            body.innerHTML =
                kv([
                    ['Operating system', [s.os, s.os_version].filter(Boolean).join(' ')],
                    ['Architecture', s.arch],
                    ['CPU', s.cpu_model],
                    ['Threads', s.cpu_threads],
                    ['Process CPU', sv.cpu_process_percent != null ? `${fixed(sv.cpu_process_percent)}% of the machine` : null],
                    ['Process memory', sv.process_resident_bytes != null ? fmtBytes(sv.process_resident_bytes) : null],
                ]) +
                meter('Physical memory', s.memory_used, s.memory_total) +
                meter('Swap', s.swap_used, s.swap_total) +
                meter('Disk', s.disk_used, s.disk_total);
        } else if (this.state.tab === 'world') {
            const ws = this.data.world_statistics || {};
            const worlds = ws.worlds || [];
            const counts = Object.entries(ws.entity_counts || {}).sort((a, b) => b[1] - a[1]);
            const top = counts[0]?.[1] || 1;
            body.innerHTML = `<div class="tv-split"><div>
                <h4 class="tv-h">Worlds (${num(ws.total_entities)} entities)</h4>
                <table class="tv-table"><thead><tr><th>World</th><th>Chunks</th><th>Entities</th><th>Block entities</th><th>Players</th></tr></thead><tbody>${
                    worlds
                        .map((w) => `<tr><td>${esc(w.name)}</td><td>${num(w.chunks)}</td><td>${num(w.entities)}</td><td>${num(w.block_entities)}</td><td>${num(w.players)}</td></tr>`)
                        .join('') || '<tr><td colspan="5">No data</td></tr>'
                }</tbody></table></div>
                <div><h4 class="tv-h">Entity counts</h4><div class="tv-hbar">${
                    counts
                        .slice(0, 40)
                        .map(([name, n]) => `<span class="name">${esc(name)}</span><span class="num">${num(n)}</span><div class="track"><i style="width:${(n / top) * 100}%"></i></div>`)
                        .join('') || '<span class="name tv-dim">No entities</span>'
                }</div></div></div>`;
        } else if (this.state.tab === 'config') {
            const entries = Object.entries(this.data.configuration || {});
            body.innerHTML = entries.length ? kv(entries) : '<p class="tv-note">No configuration was included.</p>';
        } else {
            const plugins = this.data.plugins || [];
            body.innerHTML = plugins.length
                ? `<table class="tv-table"><thead><tr><th>Plugin</th><th>Id</th><th>Version</th><th>Errors</th></tr></thead><tbody>${plugins
                      .map((p) => `<tr><td>${esc(p.name)}${p.disabled ? ' <span class="tv-bad">(disabled)</span>' : ''}</td><td>${esc(p.id)}</td><td>${esc(p.version)}</td><td>${num(p.errors)}</td></tr>`)
                      .join('')}</tbody></table>`
                : '<p class="tv-note">No plugins loaded.</p>';
        }
    }

    // -- metric charts ------------------------------------------------------------------------------------------

    charts() {
        const host = this.root.querySelector('[data-metrics]');
        host.innerHTML = '<div class="tv-charts"></div>';
        const grid = host.firstChild;
        const m = this.metrics;
        const seconds = m.seconds || [];
        const col = (key) => m[key] || [];
        lineChart(grid, { title: 'TPS', seconds, yMax: 20, format: (v) => fixed(v, 1), series: [{ name: 'TPS', color: '#6ee7b7', values: col('tps') }] });
        lineChart(grid, {
            title: 'MSPT',
            seconds,
            format: (v) => `${fixed(v, 1)} ms`,
            yMax: Math.max(60, ...col('mspt_max')),
            series: [
                { name: 'mean', color: '#93c5fd', values: col('mspt_mean') },
                { name: 'max', color: '#fb7185', values: col('mspt_max') },
            ],
        });
        lineChart(grid, {
            title: 'CPU',
            seconds,
            yMax: 100,
            format: (v) => `${fixed(v, 0)}%`,
            series: [
                { name: 'process', color: '#fcd34d', values: col('cpu_process') },
                { name: 'system', color: '#c4b5fd', values: col('cpu_system') },
            ],
        });
        lineChart(grid, {
            title: 'Memory',
            seconds,
            format: fmtBytes,
            series: [{ name: 'resident', color: '#93c5fd', values: col('memory_resident') }],
        });
        lineChart(grid, { title: 'Players', seconds, format: (v) => fixed(v, 0), series: [{ name: 'players', color: '#6ee7b7', values: col('players') }] });
        lineChart(grid, { title: 'Entities', seconds, format: (v) => num(Math.round(v)), series: [{ name: 'entities', color: '#fcd34d', values: col('entities') }] });
        lineChart(grid, { title: 'Chunks', seconds, format: (v) => num(Math.round(v)), series: [{ name: 'loaded chunks', color: '#c4b5fd', values: col('chunks') }] });
    }

    // -- window selection ---------------------------------------------------------------------------------------

    windows() {
        const host = this.root.querySelector('[data-windows]');
        const p = this.profile;
        if (p.windowCount < 2) {
            host.innerHTML = '';
            return;
        }
        const busiest = [...p.threads].sort((a, b) => b.root.total_millis - a.root.total_millis)[0];
        const bars = Array.from({ length: p.windowCount }, (_, i) => (busiest.root.window_millis[i] || 0) / p.windowSeconds);
        const top = Math.max(...bars, 1);
        const W = 600;
        const H = 80;
        const step = W / bars.length;

        host.innerHTML = `<div class="glass tv-windows">
            <div style="display:flex;justify-content:space-between;gap:1rem;margin-bottom:.4rem">
                <b style="font-size:.78rem;letter-spacing:.06em">Refine profile</b>
                <span class="tv-dim" style="font-size:.74rem" data-range>Drag across the graph to look at part of the run. Time in ${esc(busiest.name)} per second.</span>
                <button class="tv-btn is-solo" data-reset hidden>Reset</button></div>
            <svg viewBox="0 0 ${W} ${H}" preserveAspectRatio="none"></svg></div>`;
        const el = host.querySelector('svg');
        const rects = bars.map((v, i) => {
            const h = (v / top) * (H - 4);
            const r = svg('rect', { x: i * step + 1, y: H - h, width: Math.max(step - 2, 1), height: h, fill: 'rgba(255,255,255,.4)' });
            el.appendChild(r);
            return r;
        });
        const veil = svg('rect', { y: 0, height: H, fill: 'rgba(147,197,253,.15)', stroke: 'rgba(147,197,253,.6)', visibility: 'hidden', 'vector-effect': 'non-scaling-stroke' });
        el.appendChild(veil);

        const paint = () => {
            const s = p.selection;
            rects.forEach((r, i) => r.setAttribute('fill', !s || (i >= s.from && i <= s.to) ? 'rgba(255,255,255,.5)' : 'rgba(255,255,255,.16)'));
            host.querySelector('[data-reset]').hidden = !s;
            if (s) {
                veil.setAttribute('x', s.from * step);
                veil.setAttribute('width', (s.to - s.from + 1) * step);
                veil.setAttribute('visibility', 'visible');
                host.querySelector('[data-range]').textContent = `Showing ${fmtClock(s.from * p.windowSeconds)} to ${fmtClock((s.to + 1) * p.windowSeconds)}`;
            } else {
                veil.setAttribute('visibility', 'hidden');
                host.querySelector('[data-range]').textContent = `Drag across the graph to look at part of the run. Time in ${busiest.name} per second.`;
            }
        };
        const indexAt = (event) => {
            const box = el.getBoundingClientRect();
            return Math.max(0, Math.min(bars.length - 1, Math.floor(((event.clientX - box.left) / box.width) * bars.length)));
        };
        let anchor = null;
        const apply = (a, b) => {
            p.setSelection({ from: Math.min(a, b), to: Math.max(a, b) });
            paint();
            this.profileView();
        };
        el.addEventListener('pointerdown', (event) => {
            anchor = indexAt(event);
            el.setPointerCapture(event.pointerId);
            apply(anchor, anchor);
        });
        el.addEventListener('pointermove', (event) => {
            if (anchor != null) apply(anchor, indexAt(event));
        });
        el.addEventListener('pointerup', () => (anchor = null));
        host.querySelector('[data-reset]').addEventListener('click', () => {
            p.setSelection(null);
            paint();
            this.profileView();
        });
    }

    // -- profiler views -----------------------------------------------------------------------------------------

    profileView() {
        const host = this.root.querySelector('[data-profile]');
        const p = this.profile;
        const view = this.state.view;
        const titles = {
            all: ['Profiler - All view', 'The whole profile as an expandable tree, one stack per thread. A frame is a section the server timed; its value is its share of its thread.'],
            flat: ['Profiler - Flat view', 'Frames ranked by the time spent in the frame itself. Expand one to see what called it, which is how a hot frame is followed back to its cause.'],
            plugins: ['Profiler - Plugins view', 'Time grouped by where it was spent: each Lua plugin, and Basalt itself.'],
            flame: ['Profiler - Flame graph', 'Width is time. Click a frame to zoom into it, and the top bar to go back out.'],
        }[view];

        if (!p.threads.length) {
            host.innerHTML = `<div class="tv-profile-head"><h2>${titles[0]}</h2></div><div class="glass tv-note">Nothing measurable ran. Start the profiler, let the server do the thing you are investigating, then stop it.</div>`;
            return;
        }

        const labelButton = `<button class="tv-btn is-solo" data-label>${this.state.label === 'percent' ? 'Label: percentage' : 'Label: time'}</button>`;
        const selfButton = view === 'flat' || view === 'flame' ? '' : `<button class="tv-btn is-solo" data-self aria-pressed="${this.state.selfMode}">Self time</button>`;
        const expand =
            view === 'flame' ? '' : '<button class="tv-btn is-solo" data-expand>Expand all</button><button class="tv-btn is-solo" data-collapse>Collapse all</button>';
        host.innerHTML = `<div class="tv-profile-head"><h2>${titles[0]}</h2><p>${titles[1]}</p>
            <div style="display:flex;flex-wrap:wrap;gap:.5rem;margin-top:.8rem">${view === 'flame' ? '' : labelButton}${selfButton}${expand}</div></div>
            <div class="glass ${view === 'flame' ? 'tv-flame' : 'tv-tree'}" data-tree></div>`;

        const tree = host.querySelector('[data-tree]');
        if (view === 'flame') this.flame(tree);
        else this.tree(tree, view);

        host.querySelector('[data-label]')?.addEventListener('click', () => {
            this.state.label = this.state.label === 'percent' ? 'time' : 'percent';
            this.profileView();
        });
        host.querySelector('[data-self]')?.addEventListener('click', () => {
            this.state.selfMode = !this.state.selfMode;
            this.profileView();
        });
        host.querySelector('[data-expand]')?.addEventListener('click', () => this.setAll(tree, true));
        host.querySelector('[data-collapse]')?.addEventListener('click', () => this.setAll(tree, false));
    }

    tree(tree, view) {
        const p = this.profile;
        const roots = [];
        if (view === 'all') {
            for (const thread of [...p.threads].sort((a, b) => p.rootValue(b) - p.rootValue(a))) {
                const denominator = p.rootValue(thread);
                if (denominator <= 0) continue;
                const v = {
                    name: thread.name,
                    value: denominator,
                    self: 0,
                    calls: 0,
                    denominator,
                    isThread: true,
                    children: () => (v._kids ??= vnodeOf(p, thread.root, denominator).children()),
                };
                roots.push(v);
            }
        } else if (view === 'flat') {
            roots.push(...flatEntries(p).slice(0, 400));
        } else {
            roots.push(...sourceEntries(p));
        }

        if (!roots.length) {
            tree.innerHTML = '<p class="tv-note">Nothing to show for this selection.</p>';
            return;
        }
        for (const v of roots) tree.appendChild(this.nodeEl(v, 0, view));
    }

    matches(v) {
        const q = this.state.query;
        return q && v.name.toLowerCase().includes(q);
    }

    // Whether anything under `v` matches the search, remembered because every row asks it.
    below(v) {
        if (v._below !== undefined && v._belowQuery === this.state.query) return v._below;
        v._belowQuery = this.state.query;
        v._below = v.children().some((c) => this.matches(c) || this.below(c));
        return v._below;
    }

    nodeEl(v, depth, view) {
        const el = document.createElement('div');
        const kids = v.children();
        el.className = `tv-node${kids.length ? ' has-children' : ''}`;
        const shown = this.state.selfMode && view !== 'flat' && !v.isThread && !v.label ? v.self : v.value;
        const share = v.denominator > 0 ? shown / v.denominator : 0;
        const heat = heatOf(share);
        const label = this.state.label === 'percent' ? `${(share * 100).toFixed(share >= 0.1 ? 1 : 2)}%` : fmtMs(shown);
        const dot = v.name.lastIndexOf('.');
        const named = v.isThread || v.label ? esc(v.name) : dot > 0 ? `<em>${esc(v.name.slice(0, dot + 1))}</em>${esc(v.name.slice(dot + 1))}` : esc(v.name);
        const marked = this.matches(v) ? `<mark>${named}</mark>` : named;
        const sub = v.label ? `<span class="tv-sub">${v.label}</span>` : v.calls ? `<span class="tv-sub">${num(v.calls)}x</span>` : '';
        const extra = view === 'flat' && v.total != null && !v.thread ? `<span class="tv-sub">total ${fmtMs(v.total)}</span>` : '';
        el.innerHTML = `<div class="tv-row"><span class="tv-caret">${kids.length ? '▶' : ''}</span><span class="tv-name" title="${esc(v.name)}">${marked}</span>
            <span class="tv-badge heat-${heat}">${label}</span>${sub}${extra}<span class="tv-fill"></span>
            <div class="tv-mini"><i class="heat-${heat}" style="width:${Math.max(Math.min(share * 100, 100), share > 0 ? 1.5 : 0)}%"></i></div></div><div class="tv-kids"></div>`;
        el._v = v;
        el._depth = depth;
        el._view = view;

        const shouldOpen = this.state.query ? this.below(v) : depth < 2 && share >= 0.05 && kids.length > 0;
        if (shouldOpen && kids.length) this.open(el, true);
        return el;
    }

    open(el, open) {
        if (open === el.classList.contains('is-open')) return;
        el.classList.toggle('is-open', open);
        const box = el.querySelector(':scope > .tv-kids');
        if (open && !box.childElementCount) {
            for (const kid of el._v.children()) box.appendChild(this.nodeEl(kid, el._depth + 1, el._view));
        }
    }

    setAll(tree, open) {
        let budget = OPEN_LIMIT;
        const walk = (el) => {
            if (!el._v || budget <= 0) return;
            if (el._v.children().length) {
                this.open(el, open);
                budget--;
            }
            for (const kid of el.querySelectorAll(':scope > .tv-kids > .tv-node')) walk(kid);
        };
        for (const el of tree.querySelectorAll(':scope > .tv-node')) walk(el);
    }

    flame(host) {
        const p = this.profile;
        host.innerHTML = '';
        const state = this.state;
        const threads = [...p.threads].filter((t) => p.rootValue(t) > 0).sort((a, b) => p.rootValue(b) - p.rootValue(a));
        const draw = () => {
            host.innerHTML = '';
            const root = state.flame;
            const entries = root
                ? [root]
                : threads.map((t) => {
                      const v = vnodeOf(p, t.root, p.rootValue(t));
                      v.name = t.name;
                      v.value = p.rootValue(t);
                      return v;
                  });
            const bar = document.createElement('div');
            bar.className = 'tv-dim';
            bar.style.cssText = 'font-size:.75rem;padding:.2rem .3rem .5rem;cursor:pointer';
            bar.textContent = root ? `Zoomed into ${root.name}. Click here to zoom out.` : 'All threads. Click a frame to zoom in.';
            bar.addEventListener('click', () => {
                state.flame = null;
                draw();
            });
            host.appendChild(bar);
            const total = sum(entries.map((e) => e.value)) || 1;
            const levels = [];
            const place = (v, left, width, depth) => {
                if (width < 0.15) return;
                (levels[depth] ??= []).push({ v, left, width });
                let cursor = left;
                for (const child of v.children()) {
                    const w = (child.value / v.value) * width;
                    place(child, cursor, w, depth + 1);
                    cursor += w;
                }
            };
            let cursor = 0;
            for (const e of entries) {
                const w = (e.value / total) * 100;
                place(e, cursor, w, 0);
                cursor += w;
            }
            for (const level of levels) {
                const row = document.createElement('div');
                row.className = 'tv-flame-row';
                for (const { v, left, width } of level) {
                    const f = document.createElement('div');
                    f.className = 'tv-frame';
                    const share = v.value / (state.flame ? state.flame.value : total);
                    f.style.cssText = `left:${left}%;width:calc(${width}% - 1px);background:hsl(${Math.max(8, 52 - Math.min(share, 1) * 50)} 78% ${62 - Math.min(share, 1) * 10}%)`;
                    f.textContent = v.name;
                    f.title = `${v.name}\n${fmtMs(v.value)} (${((v.value / total) * 100).toFixed(2)}% of the view)\n${num(v.calls)} calls`;
                    f.addEventListener('click', () => {
                        if (v.children().length) {
                            state.flame = v;
                            draw();
                        }
                    });
                    row.appendChild(f);
                }
                host.appendChild(row);
            }
        };
        draw();
    }
}

// Rows toggle on click anywhere in them, which a delegated handler gives every lazily built row for free.
document.addEventListener('click', (event) => {
    const row = event.target.closest?.('.tv .tv-row');
    if (!row) return;
    const node = row.parentElement;
    if (!node?._v || !node.classList.contains('has-children')) return;
    const viewer = node.closest('.tv')?._viewer;
    viewer?.open(node, !node.classList.contains('is-open'));
});

export { Profile, flatEntries, sourceEntries, vnodeOf, mergeNodes };
