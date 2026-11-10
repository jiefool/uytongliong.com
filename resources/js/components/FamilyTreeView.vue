<script setup>
import { computed, nextTick, onMounted, ref } from 'vue';

const props = defineProps({ pinViewer: { type: Boolean, default: false } });
const members = ref([]);
const selected = ref(null);
const search = ref('');
const loading = ref(true);
const error = ref('');
const zoom = ref(1);
const treeScroller = ref(null);
const fullName = (member) => [member.first_name, member.last_name].filter(Boolean).join(' ');
const peopleById = computed(() => new Map(members.value.map(member => [member.id, member])));
const person = (id) => peopleById.value.get(Number(id));
const personName = (id) => person(id) ? fullName(person(id)) : null;
const birthDate = (date) => date ? new Intl.DateTimeFormat('en-US', { year: 'numeric', month: 'long', day: 'numeric', timeZone: 'UTC' }).format(new Date(date)) : null;
const year = (date) => date ? String(date).slice(0, 4) : null;
const matches = computed(() => search.value.trim()
    ? members.value.filter(member => fullName(member).toLocaleLowerCase().includes(search.value.trim().toLocaleLowerCase())).slice(0, 8)
    : []);

const layout = computed(() => {
    const representative = new Map();
    const couples = new Map();
    for (const member of members.value) {
        const spouse = person(member.spouse_id);
        const paired = spouse?.spouse_id === member.id;
        const key = paired ? Math.min(member.id, spouse.id) : member.id;
        representative.set(member.id, key);
        if (!couples.has(key)) couples.set(key, []);
        couples.get(key).push(member);
    }

    const children = new Map([...couples.keys()].map(key => [key, []]));
    const roots = [];
    for (const [key, group] of couples) {
        const parent = group.flatMap(member => [member.parent_id, member.secondary_parent_id])
            .map(id => person(id))
            .find(candidate => candidate && representative.get(candidate.id) !== key);
        const parentKey = parent ? representative.get(parent.id) : null;
        if (children.has(parentKey)) children.get(parentKey).push(key);
        else roots.push(key);
    }

    const byAge = (a, b) => {
        const first = couples.get(a)[0];
        const second = couples.get(b)[0];
        return (first.birth_date || '9999').localeCompare(second.birth_date || '9999')
            || fullName(first).localeCompare(fullName(second));
    };
    const placed = new Set();
    function measure(key, trail = new Set()) {
        if (placed.has(key) || trail.has(key)) return null;
        placed.add(key);
        const nextTrail = new Set(trail).add(key);
        const descendants = children.get(key).sort(byAge).map(child => measure(child, nextTrail)).filter(Boolean);
        const childrenWidth = descendants.reduce((width, child) => width + child.width, 0) + Math.max(0, descendants.length - 1) * 24;
        return { key, people: couples.get(key).sort((a, b) => a.id - b.id), children: descendants, width: Math.max(couples.get(key).length > 1 ? 350 : 190, childrenWidth) };
    }
    const trees = roots.sort(byAge).map(key => measure(key)).filter(Boolean);
    // Keep family members visible if older records contain a relationship cycle.
    for (const key of couples.keys()) if (!placed.has(key)) trees.push(measure(key));
    const depthOf = (tree) => tree.children.length ? 1 + Math.max(...tree.children.map(depthOf)) : 0;
    const maxDepth = Math.max(0, ...trees.map(depthOf));
    const totalWidth = trees.reduce((width, tree) => width + tree.width, 0) + Math.max(0, trees.length - 1) * 60;
    const width = Math.max(1200, totalWidth + 120);
    const height = Math.max(1100, 400 + maxDepth * 230);
    const nodes = [];
    const limbs = [];
    const baseLimbs = [];
    const leaves = [];
    const leafColors = ['#708d49', '#8ba95f', '#a0b96c', '#627f43', '#b2c77b'];
    const addLeaf = (key, x, y, rotation, index) => {
        if (x < 10 || x > width - 10 || y < 10 || y > height - 10) return;
        leaves.push({ key, x, y, rotation, color: leafColors[index % leafColors.length], rx: 8 + (index % 3) * 2, ry: 3.5 + (index % 2) });
    };
    const cubic = (start, controlA, controlB, end, t) => ((1 - t) ** 3 * start) + (3 * (1 - t) ** 2 * t * controlA) + (3 * (1 - t) * t ** 2 * controlB) + (t ** 3 * end);

    function place(tree, left, depth, parent = null) {
        const node = { key: tree.key, people: tree.people, x: left + tree.width / 2, y: 150 + (maxDepth - depth) * 230, depth, isLeaf: !tree.children.length };
        nodes.push(node);
        const leafCount = node.isLeaf ? 44 : depth === 0 ? 14 : 28;
        for (let index = 0; index < leafCount; index++) {
            const angle = index * 2.39996 + node.key * 0.37;
            const radius = 92 + (index % 8) * 14;
            addLeaf(`node-${node.key}-${index}`, node.x + Math.cos(angle) * radius, node.y + 60 + Math.sin(angle) * (62 + (index % 6) * 14), (angle * 180 / Math.PI + 42) % 180, index);
        }
        if (parent) {
            limbs.push({ key: `${parent.key}-${node.key}`, width: Math.max(7, 18 - depth * 3), path: `M ${parent.x} ${parent.y + 28} C ${parent.x} ${parent.y - 75}, ${node.x} ${node.y + 205}, ${node.x} ${node.y + 132}` });
            for (let index = 0; index < 14; index++) {
                const t = 0.16 + index * 0.05;
                const side = (index % 2 ? 1 : -1) * (16 + (index % 3) * 9);
                const x = cubic(parent.x, parent.x, node.x, node.x, t) + side;
                const y = cubic(parent.y + 28, parent.y - 75, node.y + 205, node.y + 132, t) + ((index % 3) - 1) * 16;
                addLeaf(`limb-${parent.key}-${node.key}-${index}`, x, y, (index * 37 + node.key * 9) % 180, index + node.key);
            }
        } else {
            baseLimbs.push({ key: node.key, path: `M ${width / 2} ${height - 8} C ${width / 2} ${height - 105}, ${node.x} ${node.y + 195}, ${node.x} ${node.y + 132}` });
        }
        const childrenWidth = tree.children.reduce((sum, child) => sum + child.width, 0) + Math.max(0, tree.children.length - 1) * 24;
        let cursor = left + (tree.width - childrenWidth) / 2;
        for (const child of tree.children) {
            place(child, cursor, depth + 1, node);
            cursor += child.width + 24;
        }
    }
    let cursor = (width - totalWidth) / 2;
    for (const tree of trees) {
        place(tree, cursor, 0);
        cursor += tree.width + 60;
    }
    return { width, height, nodes, limbs, baseLimbs, leaves };
});

function chooseMember(member) {
    selected.value = member;
    search.value = '';
    requestAnimationFrame(() => {
        const target = document.querySelector(`[data-tree-member-id="${member.id}"]`);
        const scroller = treeScroller.value;
        if (!target || !scroller) return;
        const card = target.getBoundingClientRect();
        const view = scroller.getBoundingClientRect();
        scroller.scrollBy({ left: card.left - view.left - (scroller.clientWidth - card.width) / 2, top: card.top - view.top - (scroller.clientHeight - card.height) / 2, behavior: 'smooth' });
    });
}

async function setZoom(value) {
    const nextZoom = Math.min(2, Math.max(0.5, value));
    if (nextZoom === zoom.value) return;

    const scroller = treeScroller.value;
    const horizontalInset = (scale) => scroller ? Math.max(0, (scroller.clientWidth - layout.value.width * scale) / 2) : 0;
    const centerX = scroller ? (scroller.scrollLeft + scroller.clientWidth / 2 - horizontalInset(zoom.value)) / zoom.value : 0;
    const centerY = scroller ? (scroller.scrollTop + scroller.clientHeight / 2) / zoom.value : 0;
    zoom.value = nextZoom;
    await nextTick();

    if (scroller) {
        scroller.scrollLeft = centerX * nextZoom + horizontalInset(nextZoom) - scroller.clientWidth / 2;
        scroller.scrollTop = centerY * nextZoom - scroller.clientHeight / 2;
    }
}

onMounted(async () => {
    try {
        const response = await fetch('/members', { headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error('The family tree could not be loaded.');
        members.value = await response.json();
    } catch (cause) {
        error.value = cause.message;
    } finally {
        loading.value = false;
        await nextTick();
        const scroller = treeScroller.value;
        if (scroller) {
            scroller.scrollLeft = Math.max(0, (layout.value.width - scroller.clientWidth) / 2);
            scroller.scrollTop = Math.max(0, layout.value.height - scroller.clientHeight);
        }
    }
});
</script>

<template>
    <main class="heritage-shell">
        <nav class="heritage-nav"><a :href="pinViewer ? '/view/family-tree' : '/family-tree'">← Back to family tree</a><span>家 UY TONG LIONG - LOO SIOK TIN</span></nav>
        <header class="heritage-heading"><p class="eyebrow">GROWING THROUGH GENERATIONS · 家</p><h1>Our Living <em>Family Tree</em></h1><p>Follow each branch from parents to children. Select a portrait to see that person's family ties.</p></header>
        <div class="heritage-toolbar"><div><strong>{{ members.length }}</strong> family members</div><label>Find a person<input v-model="search" type="search" placeholder="Search by name" autocomplete="off"></label></div>
        <div v-if="matches.length" class="heritage-search-results" aria-label="Matching family members"><button v-for="member in matches" :key="member.id" type="button" @click="chooseMember(member)">{{ fullName(member) }}</button></div>
        <p v-if="error" class="heritage-error" role="alert">{{ error }}</p>
        <p v-else-if="loading" class="heritage-empty">Loading the family tree…</p>
        <p v-else-if="!members.length" class="heritage-empty">No family members have been added yet.</p>
        <template v-else>
            <div class="living-tree-tools">
                <p class="heritage-hint">The oldest branches begin at the bottom. Scroll across the tree to follow families outward.</p>
                <div class="living-tree-zoom" role="group" aria-label="Family tree zoom controls">
                    <button type="button" aria-label="Zoom out" :disabled="zoom <= 0.5" @click="setZoom(zoom - 0.25)">−</button>
                    <output aria-live="polite">{{ Math.round(zoom * 100) }}%</output>
                    <button type="button" aria-label="Zoom in" :disabled="zoom >= 2" @click="setZoom(zoom + 0.25)">+</button>
                    <button type="button" class="living-tree-zoom-reset" :disabled="zoom === 1" @click="setZoom(1)">Reset</button>
                </div>
            </div>
            <div ref="treeScroller" class="living-tree-scroll" aria-label="Illustrated family tree">
                <div class="living-tree-zoom-stage" :style="{ width: `${layout.width * zoom}px`, height: `${layout.height * zoom}px` }">
                <div class="living-tree-board" :style="{ width: `${layout.width}px`, height: `${layout.height}px`, transform: `scale(${zoom})` }">
                    <svg class="living-tree-limbs" :viewBox="`0 0 ${layout.width} ${layout.height}`" aria-hidden="true">
                        <ellipse v-for="leaf in layout.leaves" :key="leaf.key" :cx="leaf.x" :cy="leaf.y" :rx="leaf.rx" :ry="leaf.ry" :transform="`rotate(${leaf.rotation} ${leaf.x} ${leaf.y})`" :fill="leaf.color" opacity=".86" />
                        <path v-for="limb in layout.baseLimbs" :key="`root-${limb.key}`" :d="limb.path" class="living-limb living-limb-base" />
                        <path v-for="limb in layout.limbs" :key="limb.key" :d="limb.path" class="living-limb" :stroke-width="limb.width" />
                    </svg>
                    <template v-for="node in layout.nodes" :key="node.key">
                        <button v-for="(member, index) in node.people" :key="member.id" type="button" class="family-orb" :class="{ 'family-orb-root': node.depth === 0, 'family-orb-leaf': node.isLeaf }" :style="{ left: `${node.x + (node.people.length === 2 ? (index === 0 ? -86 : 86) : 0)}px`, top: `${node.y}px` }" :data-tree-member-id="member.id" :aria-label="`${fullName(member)}; select for family details`" @click="chooseMember(member)">
                            <span class="family-orb-portrait"><img v-if="member.profile_image_path" :src="`/storage/${member.profile_image_path}`" :alt="`${fullName(member)} profile`"><span v-else>{{ member.first_name.charAt(0) }}</span></span>
                            <span class="family-orb-name">{{ fullName(member) }}<small v-if="year(member.birth_date)">{{ year(member.birth_date) }}</small></span>
                        </button>
                    </template>
                    <div class="living-tree-ribbon" aria-hidden="true">Our Family Tree</div>
                </div>
                </div>
            </div>
        </template>
        <section v-if="selected" class="heritage-detail" aria-labelledby="selected-member-name"><div v-if="selected.profile_image_path" class="heritage-detail-image"><img :src="`/storage/${selected.profile_image_path}`" :alt="`${fullName(selected)} profile`"></div><div v-else class="heritage-detail-initial" aria-hidden="true">{{ selected.first_name.charAt(0) }}</div><div><p class="eyebrow">FAMILY MEMBER</p><h2 id="selected-member-name">{{ fullName(selected) }}</h2><p v-if="selected.birth_date">Born {{ birthDate(selected.birth_date) }}</p><p>{{ selected.life_status === 'deceased' ? 'Deceased' : 'Living' }}</p><p v-if="personName(selected.parent_id)">Parent: {{ personName(selected.parent_id) }}</p><p v-if="personName(selected.secondary_parent_id)">Second parent: {{ personName(selected.secondary_parent_id) }}</p><p v-if="personName(selected.spouse_id)">Spouse: {{ personName(selected.spouse_id) }}</p></div><button type="button" aria-label="Close member details" @click="selected = null">×</button></section>
    </main>
</template>
