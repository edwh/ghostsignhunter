<template>
    <div class="container-fluid d-flex h-100 flex-column">
        <b-row>
            <b-col cols="0" sm="2" class="d-none d-sm-block">
            </b-col>
            <b-col cols="12" sm="8" class="text-center p-0">
                <GmapMap rounded
                         :center="{lat:53.9450, lng:-2.5209}"
                         :zoom="7"
                         map-type-id="terrain"
                         ref="map"
                         @idle="onIdle"
                         style="width: 100%; height: calc(100vh - 56px)"
                >
                    <gmap-marker v-for="(item, key) in $store.state.signs" :key="key" :position="getPosition(item)" :clickable="true" @click="toggleInfo(item, key)" />
                </GmapMap>

            </b-col>
            <b-col cols="0" sm="2" class="d-none d-sm-block">
            </b-col>
        </b-row>
    </div>
</template>

<script>
    import axios from 'axios'

    export default {
        methods: {
            'onIdle': function() {
                var self = this;

                // Get the bounds of the map.
                let map = this.$refs.map.$mapObject;
                let bounds = map.getBounds();
                let sw = bounds.getSouthWest();
                let ne = bounds.getNorthEast();

                // Fetch the signs in this viewport.
                let data = {
                    swlat: sw.lat(),
                    swlng: sw.lng(),
                    nelat: ne.lat(),
                    nelng: ne.lng()
                };

                console.log("Data", data);
                axios.get(API + 'sign', {
                    params: data
                }).then(function(response) {
                    console.log("Got response", response);
                    var ret = response.data;

                    if (ret.ret === 0) {
                        self.$store.commit('setSigns', ret.signs);
                        console.log("Stored", ret.signs);
                    }
                })
            },

            getPosition: function(marker) {
                console.log("Get position", marker);
                return {
                    lat: parseFloat(marker.lat),
                    lng: parseFloat(marker.lng)
                }
            }
        },
    }
</script>