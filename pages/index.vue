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
        <b-modal v-model="modalShow" ref="mapModal" title="Ghost Sign Details">
            <b-row>
                <b-col v-if="modalItem">
                    <em>Found by {{ modalItem.user.displayname }}</em>
                </b-col>
                <b-col cols="1" v-if="modalItem">
                    <span class="float-right text-muted">
                        #{{ modalItem.id }}
                    </span>
                </b-col>
            </b-row>
            <p class="my-4"></p>
            <b-row>
                <b-col cols="12">
                    <p>It's more fun if you try to find the sign yourself first, but you can click to unlock the photo.</p>
                    <b-button variant="success" v-if="!showPhoto && modalItem" @click="setShowPhoto(true)">Unlock photo</b-button>
                    <b-button variant="success" class="mb-3" v-if="showPhoto && modalItem" @click="setShowPhoto(false)">Hide photo</b-button>
                </b-col>
            </b-row>
            <b-row>
                <b-col cols="12">
                    <b-img fluid v-if="showPhoto && modalItem" rounded alt="Ghost sign image" title="Ghost sign image" :src="modalItem.path" />
                </b-col>
            </b-row>
        </b-modal>
    </div>
</template>

<script>
    import axios from 'axios';

    export default {
        data () {
            return {
                modalShow: false,
                modalItem: null,
                showPhoto: false
            }
        },
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

                axios.get(API + 'sign', {
                    params: data
                }).then(function(response) {
                    var ret = response.data;

                    if (ret.ret === 0) {
                        _.each(ret.signs, (sign) => {
                            let found = null;
                            _.each(ret.users, (user) => {
                                if (user.id == sign.userid) {
                                    found = user;
                                }
                            });

                            sign.user = found;
                        })

                        self.$store.commit('setSigns', ret.signs);
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
                this.modalShow = !this.modalShow;
                this.modalItem = item;
                console.log("show modal", item);
            },

            setShowPhoto: function(val) {
                console.log("Set show", val);
                this.showPhoto = val;
            }
        },
    }
</script>