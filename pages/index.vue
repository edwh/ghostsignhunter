<template>
    <div class="container-fluid d-flex h-100 flex-column">
        <b-row>
            <b-col cols="0" sm="2" class="d-none d-sm-block">
            </b-col>
            <b-col cols="12" sm="8" class="text-center p-0">
                <GmapMap rounded
                         :center="getStart()"
                         :zoom="7"
                         map-type-id="terrain"
                         ref="map"
                         @idle="onIdle"
                         style="width: 100%; height: calc(100vh - 56px)"
                >
                    <gmap-marker v-for="(item, key) in $store.state.signs" :key="key" :position="getPosition(item)" :icon="getIcon(item)" :clickable="true" @click="toggleModal(item, key)" />
                </GmapMap>
            </b-col>
            <b-col cols="0" sm="2" class="d-none d-sm-block">
            </b-col>
        </b-row>
        <b-modal ref="mapModal" title="Ghost Sign Details">
            <p class="my-4"></p>
            <b-row>
                <b-col cols="12">
                    <b-img fluid v-if="$store.state.mapModalOpen" rounded alt="Ghost sign image" title="Ghost sign image" :src="$store.state.mapModalItem.path" />
                </b-col>
            </b-row>
        </b-modal>
    </div>
</template>

<script>
    import axios from 'axios'

    export default {
        methods: {
            'getStart': function() {
                return({
                    lat:53.9450,
                    lng:-2.5209
                });
            },
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
                return {
                    lat: parseFloat(marker.lat),
                    lng: parseFloat(marker.lng)
                }
            },

            getIcon: function(marker) {
                return({
                    url: require('~/assets/mapicon.png'),
                    size: {width: 46, height: 46, f: 'px', b: 'px'},
                    scaledSize: {width: 23, height: 23, f: 'px', b: 'px'}
                });
            },

            toggleModal: function(item, key) {
                console.log("Toggle", item, key);
                this.$store.commit('setMapModalOpen', this.$store.state.mapModalOpen ? false : true);
                console.log("Open", this.$store.state.mapModalOpen);

                if (this.$store.state.mapModalOpen) {
                    this.$store.commit('setMapModalItem', item);
                    this.$refs.mapModal.show();
                } else {
                    this.$refs.mapModal.hide();
                }
            }
        },
    }
</script>