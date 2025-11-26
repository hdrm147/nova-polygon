<!-- LocationPeek.vue Component -->
<script setup>
import {ref, onUnmounted} from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

const props = defineProps({
    polygon: {
        type: Object,
        required: true,
    }
})

const mapContainer = ref(null)
const map = ref(null)
const loading = ref(true)

const initMap = () => {
    if (!mapContainer.value || !props.polygon) return;

    // Initialize map with a default view
    map.value = L.map(mapContainer.value).setView([33.271522, 44.325124], 10);

    // Add OpenStreetMap tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map.value);

    // Add the polygon to the map
    const layer = L.geoJSON(props.polygon, {
        style: {
            color: 'rgba(var(--colors-primary-500))',
            weight: 3,
            opacity: 0.65,
            fillOpacity: 0.3
        }
    }).addTo(map.value);

    // Fit bounds to the polygon
    try {
        if (layer.getBounds && layer.getBounds().isValid()) {
            map.value.fitBounds(layer.getBounds(), {
                padding: [20, 20]
            });
        }
    } catch (e) {
        console.error('Error fitting bounds:', e);
    }

    loading.value = false;

    // Force a map resize after loading to ensure proper rendering
    setTimeout(() => {
        if (map.value) {
            map.value.invalidateSize();
        }
    }, 400);
}

const init = () => {
    loading.value = true;

    if (!map.value) {
        setTimeout(() => {
            initMap();
        }, 350);
    } else {
        map.value.invalidateSize();
    }
};

// Clean up map instance on component unmount
onUnmounted(() => {
    if (map.value) {
        map.value.remove();
        map.value = null;
    }
});
</script>

<template>
    <Tooltip
        :triggers="['hover']"
        :popperTriggers="['hover']"
        placement="top-start"
        theme="plain"
        @tooltip-show="init"
        @tooltip-hide="map = null"
        :auto-hide="true"
    >
        <template #default>
            <slot/>
        </template>

        <template #content>
            <div class="bg-white dark:bg-gray-900 text-gray-500 dark:text-gray-400">
                <div class="min-w-[24rem] max-w-2xl">
                    <div class="relative divide-y divide-gray-100 dark:divide-gray-800 rounded-lg py-1">
                        <div class="absolute w-full flex justify-center items-center h-full z-10">
                            <Loader v-if="loading" class="text-primary-500" width="30"></Loader>
                        </div>
                        <div ref="mapContainer" style="height: 350px; width: 100%; background-color: transparent"></div>
                    </div>
                    <p v-if="!props.polygon" class="p-3 text-center dark:text-gray-400">
                        {{ __("There's nothing configured to show here.") }}
                    </p>
                </div>
            </div>
        </template>
    </Tooltip>
</template>

<style scoped>
:deep(.leaflet-container) {
    z-index: 1;
    border-radius: 0.5rem;
}
</style>
