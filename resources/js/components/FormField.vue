<template>
    <DefaultField
        :field="field"
        :errors="errors"
        :show-help-text="showHelpText"
        :full-width-content="fullWidthContent"
    >
        <template #field>
            <div class="relative">
                <div ref="mapContainer" style="height: 400px; width: 100%; margin-bottom: 1rem;"></div>
                <div ref="polygonControls" class="polygon-controls" style="position: absolute; top: 10px; right: 10px; z-index: 1000; background: white; padding: 5px; border-radius: 4px; box-shadow: 0 1px 5px rgba(0,0,0,0.4);" v-if="drawingMode">
                    <button @click="savePolygon" class="btn btn-primary btn-sm mx-1" style="background-color: rgba(var(--colors-primary-500)); color: white; padding: 4px 8px; border-radius: 3px; margin-right: 5px;">Save</button>
                    <button @click="cancelDrawing" class="btn btn-default btn-sm mx-1" style="background-color: #f8f9fa; padding: 4px 8px; border-radius: 3px;">Cancel</button>
                </div>
            </div>
        </template>
    </DefaultField>
</template>

<script>
import {FormField, HandlesValidationErrors} from 'laravel-nova'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import 'leaflet-editable';

export default {
    mixins: [FormField, HandlesValidationErrors],

    props: ['resourceName', 'resourceId', 'field'],

    data() {
        return {
            map: null,
            polygonLayer: null,
            currentPolygon: null,
            editableLayer: null,
            drawingMode: false,
            defaultLat: 33.271522,
            defaultLng: 44.325124,
            defaultZoom: 15,
            customIcon: null
        }
    },

    mounted() {
        // Initialize map after component is mounted
        this.$nextTick(() => {
            this.initializeCustomIcon()
            this.initializeMap()
            this.setInitialValue()
        })
    },

    computed: {
        formattedValue() {
            if (!this.value) {
                return ''
            }

            try {
                const {coordinates} = this.value;
                return `Polygon with ${coordinates[0].length} points`;
            } catch (e) {
                console.error('Invalid coordinates format')
                return ''
            }
        }
    },

    methods: {
        setInitialValue() {
            this.value = this.field.value || null;
            if (this.value) {
                this.loadPolygon(this.value);
            }
        },

        loadPolygon(geoJSONData) {
            if (!this.map || !geoJSONData) return;

            // Clear existing polygon if any
            if (this.polygonLayer) {
                this.map.removeLayer(this.polygonLayer);
            }

            const geoJSONFeature = {
                "type": "Feature",
                "geometry": geoJSONData,
                "properties": {}
            };

            this.polygonLayer = L.geoJSON(geoJSONFeature, {
                style: {
                    color: 'rgba(var(--colors-primary-500))',
                    weight: 3,
                    opacity: 0.65,
                    fillOpacity: 0.3
                }
            });

            this.polygonLayer.addTo(this.map);

            // Make polygon editable
            this.polygonLayer.eachLayer((layer) => {
                if (layer instanceof L.Polygon) {
                    this.currentPolygon = layer;
                    layer.enableEdit();

                    // Fit map to polygon bounds
                    this.map.fitBounds(layer.getBounds());

                    // Add event listeners to track changes
                    layer.on('editable:editing', () => {
                        this.updatePolygonValue(layer);
                    });
                }
            });

            // Add delete control
            this.addDeleteControl();
        },

        addDeleteControl() {
            // Create custom control for delete
            const DeleteControl = L.Control.extend({
                options: {
                    position: 'topright'
                },
                onAdd: () => {
                    const container = L.DomUtil.create('div', 'leaflet-bar leaflet-control');
                    const button = L.DomUtil.create('a', '', container);
                    button.innerHTML = '🗑️';
                    button.title = 'Delete Polygon';
                    button.href = '#';
                    button.style.width = '30px';
                    button.style.height = '30px';
                    button.style.lineHeight = '30px';
                    button.style.textAlign = 'center';
                    button.style.fontSize = '18px';
                    button.style.backgroundColor = 'white';
                    button.style.color = '#333';

                    L.DomEvent.on(button, 'click', L.DomEvent.stop);
                    L.DomEvent.on(button, 'click', () => {
                        if (this.polygonLayer) {
                            this.map.removeLayer(this.polygonLayer);
                            this.polygonLayer = null;
                            this.currentPolygon = null;
                            this.value = null;

                            // Show the add polygon control again
                            this.addDrawControl();
                        }
                    });

                    return container;
                }
            });

            if (this.deleteControl) {
                this.map.removeControl(this.deleteControl);
            }

            this.deleteControl = new DeleteControl();
            this.map.addControl(this.deleteControl);
        },

        addDrawControl() {
            // Create custom control for adding a polygon
            const DrawControl = L.Control.extend({
                options: {
                    position: 'topright'
                },
                onAdd: () => {
                    const container = L.DomUtil.create('div', 'leaflet-bar leaflet-control');
                    const button = L.DomUtil.create('a', '', container);
                    button.innerHTML = '➕';
                    button.title = 'Draw Polygon';
                    button.href = '#';
                    button.style.width = '30px';
                    button.style.height = '30px';
                    button.style.lineHeight = '30px';
                    button.style.textAlign = 'center';
                    button.style.fontSize = '18px';
                    button.style.backgroundColor = 'white';
                    button.style.color = '#333';

                    L.DomEvent.on(button, 'click', L.DomEvent.stop);
                    L.DomEvent.on(button, 'click', () => {
                        this.startDrawingPolygon();
                    });

                    return container;
                }
            });

            if (this.drawControl) {
                this.map.removeControl(this.drawControl);
            }

            this.drawControl = new DrawControl();
            this.map.addControl(this.drawControl);
        },

        startDrawingPolygon() {
            // If there's already a polygon, don't allow drawing another one
            if (this.polygonLayer) return;

            this.drawingMode = true;

            // Create a new polygon and enable editing
            this.currentPolygon = this.map.editTools.startPolygon();

            // Listen for changes
            this.currentPolygon.on('editable:drawing:end', () => {
                if (this.currentPolygon && this.currentPolygon.getLatLngs()[0].length < 3) {
                    // If polygon has less than 3 points, it's not valid
                    this.cancelDrawing();
                    return;
                }

                // Update value when drawing ends
                if (this.currentPolygon) {
                    this.updatePolygonValue(this.currentPolygon);
                }
            });
        },

        savePolygon() {
            if (!this.currentPolygon || this.currentPolygon.getLatLngs()[0].length < 3) {
                // Not a valid polygon
                this.cancelDrawing();
                return;
            }

            this.drawingMode = false;

            // Commit the polygon to the map
            this.currentPolygon.disableEdit();

            // Create a GeoJSON layer for consistency
            const geoJSON = this.currentPolygon.toGeoJSON();

            // Remove the temporary drawing polygon
            this.map.removeLayer(this.currentPolygon);

            // Create the permanent layer
            this.polygonLayer = L.geoJSON(geoJSON, {
                style: {
                    color: 'rgba(var(--colors-primary-500))',
                    weight: 3,
                    opacity: 0.65,
                    fillOpacity: 0.3
                }
            }).addTo(this.map);

            // Make it editable again
            this.polygonLayer.eachLayer((layer) => {
                if (layer instanceof L.Polygon) {
                    this.currentPolygon = layer;
                    layer.enableEdit();

                    layer.on('editable:editing', () => {
                        this.updatePolygonValue(layer);
                    });
                }
            });

            // Remove draw control and add delete control
            if (this.drawControl) {
                this.map.removeControl(this.drawControl);
                this.drawControl = null;
            }

            this.addDeleteControl();
        },

        cancelDrawing() {
            this.drawingMode = false;

            if (this.currentPolygon && !this.polygonLayer) {
                // Remove the temporary drawing polygon
                this.map.removeLayer(this.currentPolygon);
                this.currentPolygon = null;
            }
        },

        updatePolygonValue(layer) {
            // Convert the edited layer back to GeoJSON
            const geoJSON = layer.toGeoJSON().geometry;

            // Update the component's value
            this.value = geoJSON;
        },

        fill(formData) {
            formData.append(this.fieldAttribute, JSON.stringify(this.value) || '')
        },

        initializeCustomIcon() {
            this.customIcon = L.icon({
                iconUrl: '/vendor/leaflet/images/marker-icon-2x.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowUrl: '/vendor/leaflet/images/marker-shadow.png',
                shadowSize: [41, 41]
            })
        },

        initializeMap() {
            // Initialize the map
            this.map = L.map(this.$refs.mapContainer, {
                editable: true
            }).setView([this.defaultLat, this.defaultLng], this.defaultZoom)

            // Add tile layer
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(this.map)

            // Add the draw control if no polygon exists initially
            if (!this.field.value) {
                this.addDrawControl();
            }
        }
    }
}
</script>
