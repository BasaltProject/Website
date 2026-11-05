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

function sourceEntries(profile, facts = []) {
    const known = new Map(facts.map((f) => [f.id, f]));
    // Native threads are the same work seen as C++ functions, so counting them here would count it twice.
    const threads = profile.threads.filter((t) => !t.name.startsWith('Native '));
    const total = sum(threads.map((t) => profile.rootValue(t)));
    // A plugin seen by the sampler is read from there: it also covers its own threads, which the sections never
    // enter. The sections are kept only for plugins nothing was sampled for, such as the Lua ones.
    const seen = new Map();
    const collect = (node, sampled) => {
        if (isPlugin(node)) {
            const id = node.name.slice('plugin.'.length);
            if (!seen.has(id)) seen.set(id, { sampled: [], measured: [] });
            seen.get(id)[sampled ? 'sampled' : 'measured'].push(node);
            return;
        }
        for (const child of node.children || []) collect(child, sampled);
    };
    for (const thread of threads) collect(thread.root, /^(Java|Lua) /.test(thread.name));

    const dropped = new Set();
    const entries = [...seen.entries()].map(([id, nodes]) => {
        const sampled = nodes.sampled.length > 0;
        if (sampled) nodes.measured.forEach((n) => dropped.add(n));
        const merged = mergeNodes(profile, sampled ? nodes.sampled : nodes.measured, total);
        const fact = known.get(id);
        merged.name = fact ? fact.name : id;
        merged.label = fact?.type === 'bukkit' ? 'Bukkit plugin' : fact?.type === 'lua' ? 'Lua plugin' : 'plugin';
        return merged;
    });
    const counted = (node) => isPlugin(node) && !dropped.has(node);
    const pluginTotal = sum(entries.map((e) => e.value));
    const roots = threads.flatMap((t) => (t.root.children || []).filter((c) => !counted(c)));
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
                .map((list) => mergeNodes(profile, list, total, counted))
                .filter((m) => m.value > 0)
                .sort((a, b) => b.value - a.value));
        entries.push(core);
    }
    return entries.sort((a, b) => b.value - a.value);
}

// ---------------------------------------------------------------------------------------------------------------
// Charts

function lineChart(host, { title, unit, series, seconds, yMax, format, note, average }) {
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
        el.appendChild(svg('line', { x1: 0, x2: W, y1: gy, y2: gy, stroke: '#2c303a', 'vector-effect': 'non-scaling-stroke' }));
    }
    for (const s of live) {
        let path = '';
        s.values.forEach((v, i) => {
            if (v == null) return;
            path += `${path ? 'L' : 'M'}${x(i).toFixed(1)} ${y(v).toFixed(1)}`;
        });
        el.appendChild(svg('path', { d: path, fill: 'none', stroke: s.color, 'stroke-width': 1.6, 'vector-effect': 'non-scaling-stroke', 'stroke-linejoin': 'round' }));
    }
    const cursor = svg('line', { y1: 0, y2: H, stroke: '#949aa6', 'vector-effect': 'non-scaling-stroke', visibility: 'hidden' });
    el.appendChild(cursor);
    const plot = document.createElement('div');
    plot.className = 'tv-plot';
    plot.appendChild(el);
    plot.insertAdjacentHTML('beforeend', `<span class="tv-y tv-y-top">${esc(format(top))}</span><span class="tv-y tv-y-bottom">${esc(format(0))}</span>`);
    wrap.appendChild(plot);

    const axis = document.createElement('div');
    axis.className = 'tv-legend tv-mono';
    axis.style.justifyContent = 'space-between';
    axis.style.paddingLeft = '3.75rem';
    axis.innerHTML = `<span>${fmtClock(seconds[0])}</span><span>${fmtClock(seconds[points - 1])}</span>`;
    wrap.appendChild(axis);
    const legend = document.createElement('div');
    legend.className = 'tv-legend';
    legend.innerHTML = live.map((s) => `<span><i style="background:${s.color}"></i>${esc(s.name)}</span>`).join('');
    wrap.appendChild(legend);

    const lastOf = (s) => format(s.values[points - 1] ?? 0);
    let head;
    if (live.length === 1) {
        head = `last ${lastOf(live[0])}`;
        if (average) {
            const avg = mean(live[0].values.filter((v) => v != null));
            if (avg != null) head = `avg ${format(avg)} · ${head}`;
        }
    } else {
        head = `last: ${live.map((s) => `${s.name} ${lastOf(s)}`).join(' · ')}`;
    }
    wrap.querySelector('[data-now]').textContent = head;

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

const REJECTED = 'That file is not a Basalt timings report.';

// Reads a report from a file on this machine. Nothing is uploaded: it never leaves the browser.
function openFile(root, file) {
    return file
        .text()
        .then((text) => JSON.parse(text))
        .then((data) => {
            if (data?.format !== 'basalt-timings' || !['server', 'proxy'].includes(data.type ?? 'server')) throw new Error(REJECTED);
            new Viewer(root, data).render();
            window.scrollTo({ top: 0 });
        })
        .catch((error) => {
            const note = root.querySelector('[data-drop-error]');
            if (note) note.textContent = error.message === REJECTED ? REJECTED : 'That file could not be read as JSON.';
        });
}

function dropZone(root) {
    root.innerHTML = `<label class="glass tv-drop" data-drop>
        <svg viewBox="0 0 16 16" width="34" height="34" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 11V2M4.5 5.5 8 2l3.5 3.5M3 10v3.5h10V10"/></svg>
        <b>Drop a timings JSON here</b>
        <span>or click to choose one. The file is read in your browser and never uploaded.</span>
        <span class="tv-bad" data-drop-error></span>
        <input type="file" accept=".json,application/json" hidden>
    </label>`;
    root.querySelector('input').addEventListener('change', (event) => {
        const file = event.target.files[0];
        if (file) openFile(root, file);
    });
}

// A file dropped anywhere on a viewer page opens in it, so a report can be swapped for another without leaving.
let dropsBound = false;
function bindDrops() {
    if (dropsBound) return;
    dropsBound = true;
    const target = () => document.querySelector('[data-timings-viewer]');
    window.addEventListener('dragover', (event) => {
        if (!target() || !event.dataTransfer?.types?.includes('Files')) return;
        event.preventDefault();
        document.body.classList.add('tv-dragging');
    });
    window.addEventListener('dragleave', (event) => {
        if (event.relatedTarget === null) document.body.classList.remove('tv-dragging');
    });
    window.addEventListener('drop', (event) => {
        document.body.classList.remove('tv-dragging');
        const root = target();
        const file = event.dataTransfer?.files?.[0];
        if (!root || !file) return;
        event.preventDefault();
        openFile(root, file);
    });
}

export function mount(root) {
    if (root.dataset.ready) return;
    root.dataset.ready = '1';
    bindDrops();

    if (root.dataset.local !== undefined) {
        root.classList.add('tv');
        dropZone(root);
        return;
    }

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
        this.state = { view: 'all', query: '', section: 'summary', flame: null };
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
                <div class="tv-title"><b>Basalt ${this.data.type === 'proxy' ? 'Proxy' : 'Server'} ${esc(platform.version || this.data.basalt || '')}</b> profile
                    <small>${esc(user)} @ ${esc(started)}, profiled ${fmtDuration(this.meta.measured_seconds ?? this.data.measured_seconds)}</small></div>
                <div class="tv-group">
                    <button class="tv-btn" data-section="summary" aria-pressed="true">Summary</button>
                    <button class="tv-btn" data-section="profiler" aria-pressed="false">Profiler</button>
                    <button class="tv-btn" data-section="server" aria-pressed="false">${this.data.type === 'proxy' ? 'Proxy' : 'Server'}</button>
                </div>
            </div>
            <div data-section-body="summary">
                <div class="tv-widgets" data-widgets style="margin-top:.75rem"></div>
                <p class="tv-scale" data-scale hidden></p>
                <div data-metrics style="margin-top:.75rem"></div>
            </div>
            <div data-section-body="profiler" hidden>
                <div data-windows style="margin-top:.75rem"></div>
                <div class="glass tv-bar" data-profile-bar style="margin-top:.75rem">
                    <div class="tv-group">
                        <button class="tv-btn" data-view="all" aria-pressed="true">All</button>
                        <button class="tv-btn" data-view="flat" aria-pressed="false">Flat</button>
                        <button class="tv-btn" data-view="plugins" aria-pressed="false">Plugins</button>
                        <button class="tv-btn" data-view="flame" aria-pressed="false">Flame</button>
                    </div>
                    <input class="tv-search" type="search" placeholder="Search frames" data-search>
                </div>
                <div data-profile></div>
            </div>
            <div data-section-body="server" hidden>
                <div data-info style="margin-top:.75rem"></div>
            </div>`;
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
            const button = event.target.closest('[data-section]');
            if (!button) return;
            this.state.section = button.dataset.section;
            root.querySelectorAll('[data-section]').forEach((b) => b.setAttribute('aria-pressed', String(b === button)));
            root.querySelectorAll('[data-section-body]').forEach((b) => (b.hidden = b.dataset.sectionBody !== this.state.section));
        });
        root.querySelector('[data-profile-bar]').addEventListener('click', (event) => {
            const button = event.target.closest('[data-view]');
            if (!button) return;
            this.state.view = button.dataset.view;
            this.state.flame = null;
            root.querySelectorAll('[data-view]').forEach((b) => b.setAttribute('aria-pressed', String(b === button)));
            this.profileView();
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
        const scale = this.root.querySelector('[data-scale]');
        scale.hidden = !html;
        scale.innerHTML = html
            ? '<span class="tv-good">Good</span>: TPS ≥ 19, MSPT ≤ 35 ms, usage &lt; 60%. <span class="tv-warn">Warning</span>: TPS ≥ 15, MSPT ≤ 50 ms, usage &lt; 85%. <span class="tv-bad">Critical</span>: anything worse.'
            : '';
    }

    // -- info panel ---------------------------------------------------------------------------------------------

    info() {
        const host = this.root.querySelector('[data-info]');
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

        const platformHtml = kv([
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
        const systemHtml =
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

        const ws = this.data.world_statistics || {};
        const worlds = ws.worlds || [];
        const counts = Object.entries(ws.entity_counts || {}).sort((a, b) => b[1] - a[1]);
        const top = counts[0]?.[1] || 1;
        const worldsHtml = `<div class="tv-split"><div>
                <h4 class="tv-h">Worlds (${num(ws.total_entities)} entities)</h4>
                <table class="tv-table"><thead><tr><th>World</th><th>Chunks</th><th>Entities</th><th>Block entities</th><th>Orphans</th><th>Players</th></tr></thead><tbody>${
                    worlds
                        .map((w) => `<tr><td>${esc(w.name)}</td><td>${num(w.chunks)}</td><td>${num(w.entities)}</td><td>${num(w.block_entities)}</td><td>${w.orphan_entities > 0 ? `<span class="tv-bad">${num(w.orphan_entities)}</span>` : num(w.orphan_entities || 0)}</td><td>${num(w.players)}</td></tr>`)
                        .join('') || '<tr><td colspan="6">No data</td></tr>'
                }</tbody></table></div>
                <div><h4 class="tv-h">Entity counts</h4><div class="tv-hbar">${
                    counts
                        .slice(0, 40)
                        .map(([name, n]) => `<span class="name">${esc(name)}</span><span class="num">${num(n)}</span><div class="track"><i style="width:${(n / top) * 100}%"></i></div>`)
                        .join('') || '<span class="name tv-dim">No entities</span>'
                }</div></div></div>`;

        const entries = Object.entries(this.data.configuration || {});
        const configHtml = entries.length ? kv(entries) : '<p class="tv-note">No configuration was included.</p>';

        const plugins = this.data.plugins || [];
        const pluginsHtml = plugins.length
            ? `<table class="tv-table"><thead><tr><th>Plugin</th><th>Type</th><th>Id</th><th>Version</th><th>Errors</th></tr></thead><tbody>${plugins
                  .map((p) => `<tr><td>${esc(p.name)}${p.disabled ? ' <span class="tv-bad">(disabled)</span>' : ''}</td><td>${p.type === 'bukkit' ? 'Bukkit' : 'Lua'}</td><td>${esc(p.id)}</td><td>${esc(p.version)}</td><td>${num(p.errors)}</td></tr>`)
                  .join('')}</tbody></table>`
            : '<p class="tv-note">No plugins loaded.</p>';

        host.innerHTML = `<div class="tv-server-grid">
                <div class="glass tv-panel"><h4 class="tv-h">Platform</h4>${platformHtml}</div>
                <div class="glass tv-panel"><h4 class="tv-h">System</h4>${systemHtml}</div>
                <div class="glass tv-panel"><h4 class="tv-h">Configuration</h4>${configHtml}</div>
            </div>
            <div class="glass tv-panel" style="margin-top:.75rem">${worldsHtml}</div>
            <div class="glass tv-panel" style="margin-top:.75rem"><h4 class="tv-h">Plugins (${plugins.length})</h4>${pluginsHtml}</div>`;
    }

    // -- metric charts ------------------------------------------------------------------------------------------

    charts() {
        const host = this.root.querySelector('[data-metrics]');
        host.innerHTML = '<div class="tv-charts is-large"></div><div class="tv-charts is-medium"></div><div class="tv-charts is-small"></div>';
        const [large, medium, small] = host.children;
        const m = this.metrics;
        const seconds = m.seconds || [];
        const col = (key) => m[key] || [];
        lineChart(large, { title: 'TPS', seconds, yMax: 20, average: true, format: (v) => fixed(v, 1), series: [{ name: 'TPS', color: '#6ee7b7', values: col('tps') }] });
        lineChart(large, {
            title: 'MSPT',
            seconds,
            format: (v) => `${fixed(v, 1)} ms`,
            yMax: Math.max(60, ...col('mspt_max')),
            series: [
                { name: 'mean', color: '#93c5fd', values: col('mspt_mean') },
                { name: 'max', color: '#fb7185', values: col('mspt_max') },
            ],
        });
        lineChart(medium, {
            title: 'CPU',
            seconds,
            yMax: 100,
            format: (v) => `${fixed(v, 0)}%`,
            series: [
                { name: 'process', color: '#fcd34d', values: col('cpu_process') },
                { name: 'system', color: '#c4b5fd', values: col('cpu_system') },
            ],
        });
        lineChart(medium, {
            title: 'Memory',
            seconds,
            format: fmtBytes,
            series: [{ name: 'resident', color: '#93c5fd', values: col('memory_resident') }],
        });
        lineChart(small, { title: 'Players', seconds, format: (v) => fixed(v, 0), series: [{ name: 'players', color: '#6ee7b7', values: col('players') }] });
        lineChart(small, { title: 'Entities', seconds, format: (v) => num(Math.round(v)), series: [{ name: 'entities', color: '#fcd34d', values: col('entities') }] });
        lineChart(small, { title: 'Chunks', seconds, format: (v) => num(Math.round(v)), series: [{ name: 'loaded chunks', color: '#c4b5fd', values: col('chunks') }] });
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
                <span class="tv-dim" style="font-size:.75rem" data-range>Drag across the graph to look at part of the run. Time in ${esc(busiest.name)} per second.</span>
                <button class="tv-btn is-solo" data-reset hidden>Reset</button></div>
            <svg viewBox="0 0 ${W} ${H}" preserveAspectRatio="none"></svg></div>`;
        const el = host.querySelector('svg');
        const rects = bars.map((v, i) => {
            const h = (v / top) * (H - 4);
            const r = svg('rect', { x: i * step + 1, y: H - h, width: Math.max(step - 2, 1), height: h, fill: '#7e848e' });
            el.appendChild(r);
            return r;
        });
        const veil = svg('rect', { y: 0, height: H, fill: 'rgba(45,212,191,.15)', stroke: '#2dd4bf', visibility: 'hidden', 'vector-effect': 'non-scaling-stroke' });
        el.appendChild(veil);

        const paint = () => {
            const s = p.selection;
            rects.forEach((r, i) => r.setAttribute('fill', !s || (i >= s.from && i <= s.to) ? '#7e848e' : '#3d4350'));
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
            plugins: ['Profiler - Plugins view', 'Time grouped by where it was spent: each plugin, Lua or Bukkit, and Basalt itself.'],
            flame: ['Profiler - Flame graph', 'Width is time. Click a frame to zoom into it, and the top bar to go back out.'],
        }[view];

        if (!p.threads.length) {
            host.innerHTML = `<div class="tv-profile-head"><h2>${titles[0]}</h2></div><div class="glass tv-note">Nothing measurable ran. Start the profiler, let the server do the thing you are investigating, then stop it.</div>`;
            return;
        }

        const expand =
            view === 'flame' ? '' : '<button class="tv-btn is-solo" data-expand>Expand all</button><button class="tv-btn is-solo" data-collapse>Collapse all</button>';
        host.innerHTML = `<div class="tv-profile-head"><h2>${titles[0]}</h2><p>${titles[1]}</p>
            ${expand ? `<div style="display:flex;flex-wrap:wrap;gap:.5rem;margin-top:.8rem">${expand}</div>` : ''}</div>
            <div class="glass ${view === 'flame' ? 'tv-flame' : 'tv-tree'}" data-tree></div>`;

        const tree = host.querySelector('[data-tree]');
        if (view === 'flame') this.flame(tree);
        else this.tree(tree, view);

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
            roots.push(...sourceEntries(p, this.data.plugins || []));
        }

        if (!roots.length) {
            tree.innerHTML = '<p class="tv-note">Nothing to show for this selection.</p>';
            return;
        }
        const columns =
            view === 'flat'
                ? [
                      ['Self %', 'Share of the total profile time spent in the frame itself, excluding what it calls.'],
                      ['Self time', 'Time spent in the frame itself. For a caller row, the time it contributed to the frame above.'],
                      ['Total time', 'Time spent in the frame including everything it calls.'],
                      ['Calls', 'How many times the frame ran.'],
                  ]
                : [
                      ['Total %', 'Share of the thread or view spent in the frame, including everything it calls.'],
                      ['Self %', 'Share spent in the frame itself, excluding what it calls.'],
                      ['Time', 'Time spent in the frame, including everything it calls.'],
                      ['Calls', 'How many times the frame ran.'],
                  ];
        const inner = document.createElement('div');
        inner.className = 'tv-inner';
        inner.innerHTML = `<div class="tv-head"><span class="tv-caret"></span><span class="tv-main" title="Frame name">Name</span>${columns
            .map(([name, hint]) => `<span class="tv-cell" title="${esc(hint)}">${name}</span>`)
            .join('')}<span class="tv-mini-head" title="Bar width is the share of the thread or view; colour is the self time.">Bar</span></div>`;
        tree.appendChild(inner);
        this.appendNodes(inner, roots, 0, view);
    }

    // Appends rows, folding the negligible ones into one button unless a search is looking for them.
    appendNodes(box, nodes, depth, view) {
        const tiny = (v) => (v.denominator > 0 ? (view === 'flat' ? v.self : v.value) / v.denominator : 0) < 0.001;
        const small = this.state.query ? [] : nodes.filter(tiny);
        const keep = small.length > 1 ? nodes.filter((v) => !small.includes(v)) : nodes;
        for (const v of keep) box.appendChild(this.nodeEl(v, depth, view));
        if (small.length < 2) return;
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'tv-more';
        button.textContent = `${small.length} more frames under 0.1% (${fmtMs(sum(small.map((v) => v.value)))} total)`;
        button._reveal = () => {
            const rows = small.map((v) => this.nodeEl(v, depth, view));
            button.replaceWith(...rows);
            return rows.length;
        };
        button.addEventListener('click', () => button._reveal());
        box.appendChild(button);
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
        const share = v.denominator > 0 ? v.value / v.denominator : 0;
        // Colour by the node's own time where it has one; threads and plugin groups keep the shown value.
        const ownShare = v.isThread ? 0 : v.label || v.self == null ? share : v.denominator > 0 ? v.self / v.denominator : 0;
        const heat = heatOf(ownShare);
        const pct = (x) => `${(x * 100).toFixed(x >= 0.1 ? 1 : 2)}%`;
        const dot = v.name.lastIndexOf('.');
        const named = v.isThread || v.label ? esc(v.name) : dot > 0 ? `<em>${esc(v.name.slice(0, dot + 1))}</em>${esc(v.name.slice(dot + 1))}` : esc(v.name);
        const marked = this.matches(v) ? `<mark>${named}</mark>` : named;
        const sub = v.label ? `<span class="tv-sub">${v.label}</span>` : '';
        const hasSelf = !v.isThread && v.self != null && !(v.label && !v.self);
        const selfText = hasSelf ? pct(v.denominator > 0 ? v.self / v.denominator : 0) : '-';
        const selfTitle = hasSelf ? ` title="Self time: ${fmtMs(v.self)}"` : '';
        const selfCell = `<span class="tv-cell"><span class="tv-badge heat-${heat}"${selfTitle}>${selfText}</span></span>`;
        const cell = (text) => `<span class="tv-cell">${text}</span>`;
        const calls = v.isThread || (v.label && !v.calls) ? '-' : num(v.calls);
        const cells =
            view === 'flat'
                ? selfCell + cell(hasSelf ? fmtMs(v.self) : '-') + cell(depth === 0 && v.total != null ? fmtMs(v.total) : '-') + cell(calls)
                : cell(pct(share)) + selfCell + cell(fmtMs(v.value)) + cell(calls);
        el.innerHTML = `<div class="tv-row"><span class="tv-caret">${kids.length ? '▶' : ''}</span><span class="tv-main"><span class="tv-name" title="${esc(v.name)}">${marked}</span>${sub}</span>${cells}
            <div class="tv-mini"><i class="heat-${heat}" style="width:${Math.max(Math.min(share * 100, 100), share > 0 ? 1.5 : 0)}%"></i></div></div><div class="tv-kids"></div>`;
        el._v = v;
        el._depth = depth;
        el._view = view;

        const shouldOpen = this.state.query ? this.below(v)
            : view === 'all'
              ? depth < 4 && share >= 0.05 && kids.length > 0
              : view === 'plugins' && depth < 2 && share >= 0.05 && kids.length > 0;
        if (shouldOpen && kids.length) this.open(el, true);
        return el;
    }

    open(el, open) {
        if (open === el.classList.contains('is-open')) return;
        el.classList.toggle('is-open', open);
        const box = el.querySelector(':scope > .tv-kids');
        if (open && !box.childElementCount) {
            this.appendNodes(box, el._v.children(), el._depth + 1, el._view);
        }
    }

    setAll(tree, open) {
        let budget = OPEN_LIMIT;
        const reveal = (box) => {
            if (!open) return;
            for (const button of box.querySelectorAll(':scope > .tv-more')) {
                if (budget <= 0) return;
                budget -= button._reveal();
            }
        };
        const walk = (el) => {
            if (!el._v || budget <= 0) return;
            if (el._v.children().length) {
                this.open(el, open);
                budget--;
            }
            const box = el.querySelector(':scope > .tv-kids');
            reveal(box);
            for (const kid of box.querySelectorAll(':scope > .tv-node')) walk(kid);
        };
        const inner = tree.querySelector(':scope > .tv-inner');
        if (!inner) return;
        reveal(inner);
        for (const el of inner.querySelectorAll(':scope > .tv-node')) walk(el);
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
                      v.self = 0;
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
                    const heatShare = Math.min((v.self ?? 0) / (state.flame ? state.flame.value : total) / 0.2, 1);
                    const dim = state.query && !this.matches(v) && !this.below(v);
                    f.style.cssText = `left:${left}%;width:calc(${width}% - 1px);background:${dim ? '#2c303a' : `hsl(${Math.max(8, 52 - heatShare * 50)} 78% ${62 - heatShare * 10}%)`}${dim ? ';color:#949aa6' : ''}`;
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
