<script>
export default { name: 'FamilyBranch' };
</script>

<script setup>
import { computed, onMounted, onUpdated, ref } from 'vue';

const props = defineProps({
    member: { type: Object, required: true },
    members: { type: Array, required: true },
});
const emit = defineEmits(['select']);
const formatDate = (date) => date ? new Intl.DateTimeFormat('en', { year: 'numeric' }).format(new Date(date)) : 'Family member';
const spouse = computed(() => {
    const partner = props.member.spouse_id ? props.members.find(person => person.id === props.member.spouse_id) : null;
    return partner?.spouse_id === props.member.id ? partner : null;
});
const imageUrl = (person) => person.profile_image_path ? `/storage/${person.profile_image_path}` : null;
const lifeStatus = (person) => person.life_status === 'deceased' ? 'Deceased' : 'Alive';
const branchRef = ref(null);
const coupleRowRef = ref(null);
const childrenRef = ref(null);

function alignConnectors() {
    requestAnimationFrame(() => {
        if (!branchRef.value || !coupleRowRef.value) return;

        const branchBox = branchRef.value.getBoundingClientRect();
        const coupleBox = coupleRowRef.value.getBoundingClientRect();
        const coupleCenter = coupleBox.left + (coupleBox.width / 2);
        branchRef.value.style.setProperty('--own-center', `${coupleCenter - branchBox.left}px`);

        if (!childrenRef.value) return;
        const childrenBox = childrenRef.value.getBoundingClientRect();
        childrenRef.value.style.setProperty('--parent-center', `${coupleCenter - childrenBox.left}px`);

        const childBranches = [...childrenRef.value.children].filter(element => element.classList.contains('branch'));
        if (childBranches.length < 2) return;
        const firstCard = childBranches[0].firstElementChild;
        const lastCard = childBranches.at(-1).firstElementChild;
        const firstBox = firstCard.getBoundingClientRect();
        const lastBox = lastCard.getBoundingClientRect();
        const start = firstBox.left + (firstBox.width / 2) - childrenBox.left;
        const end = lastBox.left + (lastBox.width / 2) - childrenBox.left;
        childrenRef.value.style.setProperty('--sibling-line-start', `${start}px`);
        childrenRef.value.style.setProperty('--sibling-line-width', `${Math.max(0, end - start)}px`);
    });
}

onMounted(alignConnectors);
onUpdated(alignConnectors);
</script>

<template>
    <div ref="branchRef" class="branch">
        <div ref="coupleRowRef" class="couple-row">
            <article class="person-card" :class="member.gender || 'other'" tabindex="0" role="button" @click="emit('select', member)" @keydown.enter="emit('select', member)">
                <div class="avatar"><img v-if="imageUrl(member)" :src="imageUrl(member)" :alt="`${member.first_name}'s profile`"><span v-else>{{ member.first_name.charAt(0) }}</span></div>
                <div><h3>{{ member.first_name }} {{ member.last_name }}</h3><p>{{ formatDate(member.birth_date) }} · <span class="life-status" :class="member.life_status">{{ lifeStatus(member) }}</span></p></div>
            </article>
            <template v-if="spouse && member.id < spouse.id">
                <span class="spouse-line" aria-label="Spouse connection">♥</span>
                <article class="person-card spouse-card" :class="spouse.gender || 'other'" tabindex="0" role="button" @click="emit('select', spouse)" @keydown.enter="emit('select', spouse)">
                    <div class="avatar"><img v-if="imageUrl(spouse)" :src="imageUrl(spouse)" :alt="`${spouse.first_name}'s profile`"><span v-else>{{ spouse.first_name.charAt(0) }}</span></div>
                    <div><h3>{{ spouse.first_name }} {{ spouse.last_name }}</h3><p>{{ formatDate(spouse.birth_date) }} · <span class="life-status" :class="spouse.life_status">{{ lifeStatus(spouse) }}</span></p></div>
                </article>
            </template>
        </div>
        <div v-if="member.children.length" ref="childrenRef" class="children"><FamilyBranch v-for="child in member.children" :key="child.id" :member="child" :members="members" @select="emit('select', $event)" /></div>
    </div>
</template>
