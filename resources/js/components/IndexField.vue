<template>
    <div v-if="fieldValue" class="flex items-center gap-2">
        <polygon-peek :polygon="fieldValue">
            <a @click.stop class="no-underline font-bold dim text-primary-500">
                <span>{{ formattedValue }}</span>
            </a>
        </polygon-peek>
        <div @click.stop="copyToClipboard" class="text-primary-500 p-1 rounded bg-primary-100 cursor-pointer">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1rem">
                <path fill-rule="evenodd"
                      d="M13.887 3.182c.396.037.79.08 1.183.128C16.194 3.45 17 4.414 17 5.517V16.75A2.25 2.25 0 0 1 14.75 19h-9.5A2.25 2.25 0 0 1 3 16.75V5.517c0-1.103.806-2.068 1.93-2.207.393-.048.787-.09 1.183-.128A3.001 3.001 0 0 1 9 1h2c1.373 0 2.531.923 2.887 2.182ZM7.5 4A1.5 1.5 0 0 1 9 2.5h2A1.5 1.5 0 0 1 12.5 4v.5h-5V4Z"
                      clip-rule="evenodd"/>
            </svg>
        </div>
    </div>
    <div v-else>
        <span class="text-80">{{__("No Polygon")}}</span>
    </div>
</template>

<script>
import PolygonPeek from "./LocationPeek.vue";

export default {
    components: {PolygonPeek},
    props: ['resourceName', 'field'],

    computed: {
        fieldValue() {
            return this.field.displayedAs || this.field.value;
        },
        formattedValue() {
            try {
                if (this.fieldValue && this.fieldValue.type === 'Polygon') {
                    // Count the number of points in the polygon
                    const pointCount = this.fieldValue.coordinates[0]?.length || 0;
                    return `Polygon (${pointCount} points)`;
                }
                return 'Polygon';
            } catch (e) {
                console.error('Error formatting polygon value:', e);
                return 'Polygon';
            }
        }
    },
    methods: {
        copyToClipboard() {
            try {
                // Copy a simpler representation or the GeoJSON string
                if (this.fieldValue) {
                    const geoJSON = JSON.stringify(this.fieldValue);
                    navigator.clipboard.writeText(geoJSON);
                    Nova.$toasted.success(this.__('Copied'));
                }
            } catch (e) {
                console.error('Error copying to clipboard:', e);
                Nova.$toasted.error(this.__('Failed to copy'));
            }
        }
    }

}
</script>
