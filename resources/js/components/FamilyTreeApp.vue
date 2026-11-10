<script setup>
import { dia, shapes } from '@joint/core';
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';

const members = ref([]);
const saving = ref(false);
const deleting = ref(false);
const error = ref('');
const selectedMemberId = ref(null);
const imagePreview = ref('');
const jointTreeRef = ref(null);
const chartZoom = ref(1);
let familyGraph = null;
let familyPaper = null;
let graphDimensions = { width: 0, height: 0 };
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
const isAdmin = document.querySelector('meta[name="is-admin"]')?.content === 'true';
const isFamilyAdmin = document.querySelector('meta[name="is-family-admin"]')?.content === 'true';
const isPinViewer = document.querySelector('meta[name="is-pin-viewer"]')?.content === 'true';
const form = reactive({
    first_name: '', last_name: '', birth_date: '', gender: '', life_status: 'alive', profile_image: null, parent_id: '', secondary_parent_id: '', spouse_id: '', address: '',
    contact_number: '', email: '', google_maps_url: '', facebook_url: '', instagram_url: '', tiktok_url: '', x_url: '',
});

const fullName = (member) => [member.first_name, member.last_name].filter(Boolean).join(' ');
const isEditing = computed(() => selectedMemberId.value !== null);
const availableSpouses = computed(() => members.value.filter(member => member.id !== selectedMemberId.value && (!member.spouse_id || member.id === Number(form.spouse_id)) && (!isFamilyAdmin || member.can_edit || member.id === Number(form.spouse_id))));
const availableSecondaryParents = computed(() => members.value.filter(member => member.id !== Number(form.parent_id)));

function setChartZoom(nextZoom) {
    chartZoom.value = Math.max(0.6, Math.min(1.5, Number(nextZoom.toFixed(2))));
    if (!familyPaper || !graphDimensions.width) return;
    familyPaper.scale(chartZoom.value, chartZoom.value);
    familyPaper.setDimensions(graphDimensions.width * chartZoom.value, graphDimensions.height * chartZoom.value);
}

function resetForm() {
    selectedMemberId.value = null;
    error.value = '';
    Object.assign(form, {
        first_name: '', last_name: '', birth_date: '', gender: '', life_status: 'alive', profile_image: null, parent_id: '', secondary_parent_id: '', spouse_id: '', address: '',
        contact_number: '', email: '', google_maps_url: '', facebook_url: '', instagram_url: '', tiktok_url: '', x_url: '',
    });
}

function editMember(member) {
    if (!member.can_edit) return;
    selectedMemberId.value = member.id;
    error.value = '';
    Object.assign(form, {
        first_name: member.first_name || '', last_name: member.last_name || '', birth_date: member.birth_date || '', profile_image: null,
        gender: member.gender || '', life_status: member.life_status || 'alive', parent_id: member.parent_id || '', secondary_parent_id: member.secondary_parent_id || '', spouse_id: member.spouse_id || '',
        address: member.address || '', contact_number: member.contact_number || '', email: member.email || '', google_maps_url: member.google_maps_url || '', facebook_url: member.facebook_url || '',
        instagram_url: member.instagram_url || '', tiktok_url: member.tiktok_url || '', x_url: member.x_url || '',
    });
    imagePreview.value = member.profile_image_path ? `/storage/${member.profile_image_path}` : '';
}

function selectImage(event) {
    const [file] = event.target.files;
    form.profile_image = file || null;
    imagePreview.value = file ? URL.createObjectURL(file) : imagePreview.value;
}

async function loadMembers() {
    const response = await fetch('/members');
    members.value = await response.json();
}

const MemberCard = dia.Element.define('clan.MemberCard', {
    size: { width: 220, height: 76 },
    attrs: {
        body: { width: 220, height: 76, rx: 7, ry: 7, fill: '#fffdf6', stroke: '#e0c98f', strokeWidth: 1.5 },
        accent: { width: 220, height: 4, rx: 7, ry: 7, fill: '#ad8a3b' },
        avatar: { cx: 34, cy: 38, r: 19, fill: '#f8dfd5' },
        profileImage: { x: 15, y: 19, width: 38, height: 38, preserveAspectRatio: 'xMidYMid slice', opacity: 0, clipPath: 'circle(50%)' },
        initial: { x: 34, y: 44, textAnchor: 'middle', fill: '#a31f26', fontSize: 16, fontWeight: 700, fontFamily: 'Fraunces, serif' },
        name: { x: 61, y: 32, fill: '#3e1717', fontSize: 14, fontWeight: 700, fontFamily: 'Fraunces, serif' },
        details: { x: 61, y: 53, fill: '#8f8a82', fontSize: 9, fontFamily: 'DM Mono, monospace' },
    },
}, {
    markup: [
        { tagName: 'rect', selector: 'body' }, { tagName: 'rect', selector: 'accent' },
        { tagName: 'circle', selector: 'avatar' }, { tagName: 'image', selector: 'profileImage' }, { tagName: 'text', selector: 'initial' },
        { tagName: 'text', selector: 'name' }, { tagName: 'text', selector: 'details' },
    ],
});

const CARD_WIDTH = 220;
const CARD_HEIGHT = 76;
const ROW_GAP = 68;
const GROUP_GAP = 32;

function makeLink(source, target, vertices = [], options = {}) {
    const link = new shapes.standard.Link({ source, target, vertices });
    link.attr({ line: { stroke: options.stroke || '#c6a863', strokeWidth: options.strokeWidth || 1.5, sourceMarker: { type: 'none' }, targetMarker: { type: 'none' } } });
    if (options.heart) link.labels([{ position: 0.5, attrs: { text: { text: '♥', fill: '#a31f26', fontSize: 17, fontFamily: 'serif' } } }]);
    return link;
}

function renderFamilyGraph() {
    const host = jointTreeRef.value;
    if (!host || !members.value.length) return;
    const byId = new Map(members.value.map(member => [member.id, member]));
    const generationMemo = new Map();
    const generationOf = (member, trail = new Set()) => {
        if (generationMemo.has(member.id)) return generationMemo.get(member.id);
        if (trail.has(member.id)) return 0;
        const nextTrail = new Set(trail).add(member.id);
        const parents = [member.parent_id, member.secondary_parent_id].map(id => byId.get(Number(id))).filter(Boolean);
        const generation = parents.length ? Math.max(...parents.map(parent => generationOf(parent, nextTrail))) + 1 : 0;
        generationMemo.set(member.id, generation);
        return generation;
    };
    const levels = new Map();
    members.value.forEach(member => {
        const level = generationOf(member);
        if (!levels.has(level)) levels.set(level, []);
        levels.get(level).push(member);
    });

    // A spouse belongs on the same visual generation row even if one person's
    // parent information is incomplete. Propagate that row level through each
    // couple before groups and card positions are created.
    const rowLevel = new Map(members.value.map(member => [member.id, generationOf(member)]));
    for (let pass = 0; pass < members.value.length; pass += 1) {
        let changed = false;
        members.value.forEach(member => {
            const spouse = byId.get(Number(member.spouse_id));
            if (spouse?.spouse_id === member.id) {
                const sharedLevel = Math.max(rowLevel.get(member.id), rowLevel.get(spouse.id));
                if (rowLevel.get(member.id) !== sharedLevel || rowLevel.get(spouse.id) !== sharedLevel) {
                    rowLevel.set(member.id, sharedLevel);
                    rowLevel.set(spouse.id, sharedLevel);
                    changed = true;
                }
            }
            const parents = [member.parent_id, member.secondary_parent_id].map(id => rowLevel.get(Number(id))).filter(level => level !== undefined);
            if (parents.length) {
                const childLevel = Math.max(rowLevel.get(member.id), Math.max(...parents) + 1);
                if (childLevel !== rowLevel.get(member.id)) {
                    rowLevel.set(member.id, childLevel);
                    changed = true;
                }
            }
        });
        if (!changed) break;
    }
    levels.clear();
    members.value.forEach(member => {
        const level = rowLevel.get(member.id);
        if (!levels.has(level)) levels.set(level, []);
        levels.get(level).push(member);
    });

    const groupedLevels = [...levels.keys()].sort((a, b) => a - b).map(level => {
        const grouped = [];
        const included = new Set();
        const parentKey = (member) => {
            const parents = [member.parent_id, member.secondary_parent_id].filter(Boolean).map(Number).sort((a, b) => a - b);
            return parents.length ? parents.join('-') : `root-${member.id}`;
        };
        levels.get(level).sort((a, b) => parentKey(a).localeCompare(parentKey(b), undefined, { numeric: true }) || fullName(a).localeCompare(fullName(b))).forEach(member => {
            if (included.has(member.id)) return;
            const spouse = byId.get(Number(member.spouse_id));
            const isPair = spouse && spouse.spouse_id === member.id && rowLevel.get(spouse.id) === level;
            const people = isPair ? [member, spouse].sort((a, b) => a.id - b.id) : [member];
            people.forEach(person => included.add(person.id));
            grouped.push({ people, width: people.length === 2 ? (CARD_WIDTH * 2) + 44 : CARD_WIDTH });
        });
        return { level, groups: grouped };
    });
    const graphWidth = Math.max(950, ...groupedLevels.map(({ groups }) => groups.reduce((total, group) => total + group.width, 0) + (Math.max(0, groups.length - 1) * GROUP_GAP) + 96));
    const graphHeight = Math.max(420, (groupedLevels.length * (CARD_HEIGHT + ROW_GAP)) + 55);

    const childrenByParent = new Map();
    members.value.forEach(member => [member.parent_id, member.secondary_parent_id].filter(Boolean).forEach(parentId => {
        if (!childrenByParent.has(Number(parentId))) childrenByParent.set(Number(parentId), []);
        childrenByParent.get(Number(parentId)).push(member.id);
    }));
    const descendantCount = (memberId, seen = new Set()) => {
        if (seen.has(memberId)) return 0;
        const children = childrenByParent.get(memberId) || [];
        return children.length + children.reduce((total, childId) => total + descendantCount(childId, new Set(seen).add(memberId)), 0);
    };
    const rootLevel = groupedLevels.find(({ level }) => level === 0);
    const rootStarts = new Map();
    if (rootLevel) {
        const rootGroups = rootLevel.groups.map(group => ({ group, key: `root-${group.people[0].id}`, descendants: Math.max(...group.people.map(person => descendantCount(person.id)))}))
            .sort((a, b) => b.descendants - a.descendants);
        const primaryRoot = rootGroups.shift();
        if (primaryRoot) {
            const center = graphWidth / 2;
            rootStarts.set(primaryRoot.key, center - (primaryRoot.group.width / 2));
            let left = center - (primaryRoot.group.width / 2) - GROUP_GAP;
            let right = center + (primaryRoot.group.width / 2) + GROUP_GAP;
            rootGroups.forEach((root, index) => {
                if (index % 2 === 0) {
                    left -= root.group.width;
                    rootStarts.set(root.key, Math.max(48, left));
                    left -= GROUP_GAP;
                } else {
                    rootStarts.set(root.key, right);
                    right += root.group.width + GROUP_GAP;
                }
            });
        }
    }

    graphDimensions = { width: graphWidth, height: graphHeight };
    if (!familyGraph) familyGraph = new dia.Graph();
    if (!familyPaper) {
        familyPaper = new dia.Paper({ el: host, model: familyGraph, width: graphWidth * chartZoom.value, height: graphHeight * chartZoom.value, gridSize: 1, async: false, interactive: { elementMove: false, addLinkFromMagnet: false } });
        familyPaper.on('element:pointerclick', (view) => {
            const member = members.value.find(person => person.id === view.model.get('memberId'));
            if (member?.can_edit) editMember(member);
        });
    }
    familyPaper.scale(chartZoom.value, chartZoom.value);
    familyPaper.setDimensions(graphWidth * chartZoom.value, graphHeight * chartZoom.value);
    familyGraph.clear();

    const cards = new Map();
    const pairAnchors = new Map();
    const cells = [];
    groupedLevels.forEach(({ level, groups }) => {
        const y = 28 + (level * (CARD_HEIGHT + ROW_GAP));
        const families = new Map();
        groups.forEach(group => {
            const parentIds = group.people
                .flatMap(member => [member.parent_id, member.secondary_parent_id])
                .map(Number)
                .filter(id => cards.has(id))
                .sort((a, b) => a - b);
            const uniqueParents = [...new Set(parentIds)];
            const familyKey = uniqueParents.length ? uniqueParents.join('-') : `root-${group.people[0].id}`;
            if (!families.has(familyKey)) families.set(familyKey, { parentIds: uniqueParents, groups: [] });
            families.get(familyKey).groups.push(group);
        });

        const familyLayouts = [...families.entries()].map(([familyKey, family]) => {
            const familyWidth = family.groups.reduce((total, group) => total + group.width, 0) + (Math.max(0, family.groups.length - 1) * GROUP_GAP);
            const parentCenters = family.parentIds.map(id => cards.get(id).x + (CARD_WIDTH / 2));
            const preferredStart = parentCenters.length
                ? (parentCenters.reduce((total, center) => total + center, 0) / parentCenters.length) - (familyWidth / 2)
                : (rootStarts.get(`root-${family.groups[0].people[0].id}`) ?? 48);
            return { ...family, familyKey, familyWidth, parentCenters, preferredStart };
        }).sort((a, b) => a.preferredStart - b.preferredStart);

        let cursor = 48;
        familyLayouts.forEach(({ parentIds, groups: familyGroups, familyWidth, parentCenters, preferredStart }) => {
            // Pack clusters in the order of their parent centers. This preserves
            // the parent-child hierarchy while ensuring cards never overlap.
            const clampedStart = Math.max(48, Math.min(graphWidth - familyWidth - 48, preferredStart));
            let familyCursor = Math.max(cursor, clampedStart);
            familyGroups.forEach(group => {
                group.people.forEach((member, index) => {
                    const x = familyCursor + (index * (CARD_WIDTH + 44));
                    const deceased = member.life_status === 'deceased';
                    const profileImage = member.profile_image_path ? `/storage/${member.profile_image_path}` : '';
                    const card = new MemberCard({ position: { x, y }, memberId: member.id });
                    card.attr({
                        accent: { fill: member.gender === 'male' ? '#8d151b' : member.gender === 'female' ? '#b45a4d' : '#ad8a3b' },
                        avatar: { fill: member.gender === 'male' ? '#f8dfd5' : '#f5e7be' },
                        profileImage: { xlinkHref: profileImage, opacity: profileImage ? 1 : 0 },
                        initial: { text: (member.first_name || '?').charAt(0).toUpperCase(), opacity: profileImage ? 0 : 1 },
                        name: { text: fullName(member) || 'Family member' },
                        details: { text: `${member.birth_date ? new Date(member.birth_date).getFullYear() : 'Family member'} · ${deceased ? 'Deceased' : 'Alive'}${isFamilyAdmin && !member.can_edit ? ' · View only' : ''}`, fill: deceased ? '#9a6a63' : '#438653' },
                    });
                    cards.set(member.id, { card, x, y });
                    cells.push(card);
                });
                if (group.people.length === 2) {
                    const [left, right] = group.people.map(person => cards.get(person.id));
                    pairAnchors.set([group.people[0].id, group.people[1].id].sort((a, b) => a - b).join('-'), { x: familyCursor + CARD_WIDTH + 22, y: y + (CARD_HEIGHT / 2) });
                    cells.push(makeLink({ id: left.card.id, anchor: { name: 'right' } }, { id: right.card.id, anchor: { name: 'left' } }, [], { heart: true, strokeWidth: 1.25 }));
                }
                familyCursor += group.width + GROUP_GAP;
            });
            cursor = familyCursor;
        });
    });
    const relationshipLanes = new Map();
    members.value.forEach(child => {
        const childCard = cards.get(child.id);
        const parentIds = [child.parent_id, child.secondary_parent_id].map(Number).filter(id => cards.has(id));
        if (!childCard || !parentIds.length) return;
        const pairKey = parentIds.length === 2 ? [...parentIds].sort((a, b) => a - b).join('-') : '';
        const pairAnchor = pairAnchors.get(pairKey);
        const source = pairAnchor || { x: cards.get(parentIds[0]).x + (CARD_WIDTH / 2), y: cards.get(parentIds[0]).y + CARD_HEIGHT };
        const target = { x: childCard.x + (CARD_WIDTH / 2), y: childCard.y };
        // Each parent/couple gets a separate lane above a child generation.
        // Siblings share their parent's lane, but cousin branches cannot merge
        // into a misleading horizontal connection.
        const relationshipKey = pairAnchor ? `couple-${pairKey}` : `parent-${parentIds[0]}`;
        const laneRowKey = `${target.y}`;
        if (!relationshipLanes.has(laneRowKey)) relationshipLanes.set(laneRowKey, new Map());
        const lanesInRow = relationshipLanes.get(laneRowKey);
        if (!lanesInRow.has(relationshipKey)) lanesInRow.set(relationshipKey, lanesInRow.size);
        const barY = target.y - 24 - (lanesInRow.get(relationshipKey) * 10);
        cells.push(makeLink(source, target, [{ x: source.x, y: barY }, { x: target.x, y: barY }]));
    });
    familyGraph.addCells(cells);
}

async function saveMember() {
    saving.value = true;
    error.value = '';
    try {
        const payload = new FormData();
        Object.entries(form).forEach(([key, value]) => {
            if (value !== null && (value !== '' || (isEditing.value && ['parent_id', 'secondary_parent_id', 'spouse_id'].includes(key)))) payload.append(key, value);
        });
        if (isEditing.value) payload.append('_method', 'PUT');
        const response = await fetch(isEditing.value ? `/members/${selectedMemberId.value}` : '/members', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: payload,
        });
        const data = await response.json();
        if (!response.ok) throw new Error(Object.values(data.errors || {}).flat()[0] || 'Could not save this person.');
        if (isEditing.value) members.value = members.value.map(member => member.id === data.id ? data : member);
        else members.value.push(data);
        await loadMembers();
        resetForm();
    } catch (e) {
        error.value = e.message;
    } finally {
        saving.value = false;
    }
}

async function deleteMember() {
    if (!selectedMemberId.value || !window.confirm('Delete this family member? Their spouse and parent links will be removed, but other family members will remain.')) return;
    deleting.value = true;
    error.value = '';
    try {
        const response = await fetch(`/members/${selectedMemberId.value}`, {
            method: 'DELETE',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'Could not delete this family member.');
        await loadMembers();
        resetForm();
    } catch (e) {
        error.value = e.message;
    } finally {
        deleting.value = false;
    }
}

onMounted(() => {
    loadMembers();
    window.addEventListener('resize', renderFamilyGraph);
});

onBeforeUnmount(() => {
    window.removeEventListener('resize', renderFamilyGraph);
    familyPaper?.remove();
    familyPaper = null;
    familyGraph = null;
});
watch(members, () => nextTick(renderFamilyGraph), { deep: true });
</script>

<template>
    <main class="shell">
        <header class="hero">
            <div class="brand"><span class="brand-mark">家</span> Uy Tong Liong - Loo Siok Tin</div>
            <div class="hero-copy">
                <p class="eyebrow">UY TONG LIONG - LOO SIOK TIN · 家</p>
                <h1>Honoring our roots,<br><em>growing our legacy.</em></h1>
                <p>A living record of the Uy Tong Liong family, across every generation.</p>
            </div>
            <nav class="header-menu" aria-label="Clan navigation">
                <a v-if="isAdmin" href="/users"><span>管</span> Manage users</a>
                <template v-if="!isPinViewer"><a href="/events"><span>慶</span> Clan events</a><a href="/newsletters"><span>書</span> Newsletters</a><a v-if="!isFamilyAdmin" href="/access-pins"><span>鑰</span> View access PINs</a><a v-if="!isFamilyAdmin" class="header-message-link" href="/message-to-next-generations"><span>傳</span> Message to Next Generations</a><form method="POST" action="/logout"><input name="_token" type="hidden" :value="csrfToken"><button type="submit">Sign out</button></form></template>
                <template v-else><a class="header-message-link" href="/view/messages"><span>傳</span> Message to Next Generations</a><form method="POST" action="/view/leave"><input name="_token" type="hidden" :value="csrfToken"><button type="submit">Exit view</button></form></template>
            </nav>
        </header>

        <section class="workspace">
            <aside v-if="!isPinViewer" class="add-panel">
                <div class="panel-heading"><span class="step">01</span><div><p class="eyebrow">GROW THE TREE</p><h2>{{ isEditing ? 'Update family member' : 'Add a family member' }}</h2></div></div>
                <form @submit.prevent="saveMember">
                    <label>First name <input v-model.trim="form.first_name" required placeholder="e.g. Maria"></label>
                    <label>Last name <input v-model.trim="form.last_name" placeholder="e.g. Santos"></label>
                    <div class="field-row">
                        <label>Birth date <input v-model="form.birth_date" type="date"></label>
                        <label>Gender <select v-model="form.gender"><option value="">Select</option><option value="female">Female</option><option value="male">Male</option><option value="other">Other</option></select></label>
                    </div>
                    <label>Life status <select v-model="form.life_status"><option value="alive">Alive</option><option value="deceased">Deceased</option></select></label>
                    <label>Profile image <input class="image-input" type="file" accept="image/*" @change="selectImage"></label>
                    <div v-if="imagePreview" class="image-preview"><img :src="imagePreview" alt="Profile preview"> <span>Profile image preview</span></div>
                    <div class="parent-fields">
                        <p class="form-section">PARENTS</p>
                        <label>Parent 1 <select v-model="form.parent_id"><option value="">No parent selected</option><option v-for="member in members" :key="member.id" :value="member.id">{{ fullName(member) }}</option></select></label>
                        <label>Parent 2 <select v-model="form.secondary_parent_id"><option value="">No second parent selected</option><option v-for="member in availableSecondaryParents" :key="member.id" :value="member.id">{{ fullName(member) }}</option></select></label>
                    </div>
                    <label>Spouse <select v-model="form.spouse_id"><option value="">No spouse selected</option><option v-for="member in availableSpouses" :key="member.id" :value="member.id">{{ fullName(member) }}</option></select></label>
                    <div class="contact-fields">
                        <p class="form-section">LOCATION &amp; SOCIALS</p>
                        <label>Address <input v-model.trim="form.address" placeholder="City, Province"></label>
                        <div class="field-row">
                            <label>Contact number <input v-model.trim="form.contact_number" type="tel" placeholder="+63 9XX XXX XXXX"></label>
                            <label>Email address <input v-model.trim="form.email" type="email" placeholder="name@example.com"></label>
                        </div>
                        <label>Google Maps link <input v-model.trim="form.google_maps_url" type="url" placeholder="https://maps.google.com/..."></label>
                        <label>Facebook link <input v-model.trim="form.facebook_url" type="url" placeholder="https://facebook.com/..."></label>
                        <label>Instagram link <input v-model.trim="form.instagram_url" type="url" placeholder="https://instagram.com/..."></label>
                        <label>TikTok link <input v-model.trim="form.tiktok_url" type="url" placeholder="https://tiktok.com/@..."></label>
                        <label>X link <input v-model.trim="form.x_url" type="url" placeholder="https://x.com/..."></label>
                    </div>
                    <p v-if="error" class="form-error">{{ error }}</p>
                    <button :disabled="saving"><span>{{ isEditing ? '✓' : '＋' }}</span> {{ saving ? 'Saving…' : (isEditing ? 'Save changes' : 'Add to family tree') }}</button>
                    <button v-if="isEditing" class="secondary-button" type="button" @click="resetForm">Cancel editing</button>
                    <button v-if="isEditing && !isFamilyAdmin" class="delete-member-button" type="button" :disabled="deleting" @click="deleteMember">{{ deleting ? 'Deleting…' : 'Delete family member' }}</button>
                </form>
                <p class="hint">{{ isFamilyAdmin ? 'You can add family members and edit the members you added. Choose up to two parents to place a new member on the tree.' : 'Choose up to two parents to connect this person to the family branch.' }}</p>
            </aside>

            <section class="tree-panel" :class="{ 'viewer-tree-panel': isPinViewer }">
                <div class="tree-heading"><div><p class="eyebrow">THE FAMILY TREE</p><h2>Our roots &amp; branches</h2></div><div class="tree-meta"><a class="tree-view-link" :href="isPinViewer ? '/view/tree' : '/family-tree/tree'">View living tree →</a><div class="tree-zoom-controls" aria-label="Chart zoom controls"><button type="button" aria-label="Zoom out" :disabled="chartZoom <= 0.6" @click="setChartZoom(chartZoom - 0.1)">−</button><span>{{ Math.round(chartZoom * 100) }}%</span><button type="button" aria-label="Zoom in" :disabled="chartZoom >= 1.5" @click="setChartZoom(chartZoom + 0.1)">＋</button></div><div class="member-count"><strong>{{ members.length }}</strong><span>family members</span></div><span class="live-dot">Live</span></div></div>
                <div v-if="members.length" class="tree-scroll"><div ref="jointTreeRef" class="joint-tree-canvas" aria-label="Family tree diagram"></div></div>
                <div v-else class="empty-state"><div class="empty-icon">⌘</div><h3>Your family story starts here.</h3><p>Add your first family member to watch the tree take shape.</p></div>
            </section>
        </section>
    </main>
</template>
