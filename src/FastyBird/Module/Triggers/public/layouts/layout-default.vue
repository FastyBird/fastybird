<template>
	<RouterView @toggle-menu="emit('toggleMenu', $event)" />
</template>

<script setup lang="ts">
const emit = defineEmits<{
	(e: 'toggleMenu', event: Event): void;
}>();
</script>
