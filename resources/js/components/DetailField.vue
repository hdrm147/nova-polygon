<template>
    <PanelItem :field="field">
        <template #value>
            <div ref="mapContainer" style="height: 300px; width: 100%;"></div>
        </template>
    </PanelItem>
</template>

<script>
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

export default {
    props: ['resource', 'resourceName', 'resourceId', 'field'],

    data() {
        return {
            map: null,
            polygonLayer: null,
            defaultLat: 33.271522,
            defaultLng: 44.325124,
            defaultZoom: 15
        }
    },

    mounted() {
        // Initialize map after component is mounted
        this.$nextTick(() => {
            this.initializeMap()
            this.loadPolygonData()
        })
    },

    methods: {
        loadPolygonData() {
            const value = this.field.value

            if (value) {
                try {
                    // Handle the case where value might be a string
                    const geoJSONData = typeof value === 'string' ? JSON.parse(value) : value
                    this.displayPolygon(geoJSONData)
                } catch (e) {
                    console.error('Invalid polygon data:', e)
                }
            }
        },

        displayPolygon(geoJSONData) {
            if (!this.map || !geoJSONData) return

            // Create GeoJSON feature
            const geoJSONFeature = {
                "type": "Feature",
                "geometry": geoJSONData,
                "properties": {}
            }

            // Add the polygon to map
            this.polygonLayer = L.geoJSON(geoJSONFeature, {
                style: {
                    color: 'rgba(var(--colors-primary-500))',
                    weight: 3,
                    opacity: 0.65,
                    fillOpacity: 0.3
                }
            }).addTo(this.map)

            // Fit map to the polygon bounds
            if (this.polygonLayer.getBounds) {
                try {
                    const bounds = this.polygonLayer.getBounds()
                    if (bounds.isValid()) {
                        this.map.fitBounds(bounds, {
                            padding: [20, 20]
                        })
                    }
                } catch (e) {
                    console.error('Error fitting bounds:', e)
                }
            }
        },

        initializeMap() {
            // Initialize the map
            this.map = L.map(this.$refs.mapContainer).setView([this.defaultLat, this.defaultLng], this.defaultZoom)

            // Add tile layer
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(this.map)
        }
    },

    // Cleanup on component destroy
    beforeDestroy() {
        if (this.map) {
            this.map.remove()
        }
    }
}
</script>
