<template>
    <div class="container-fluid d-flex h-100 flex-column">
        <b-row>
            <b-col cols="0" sm="2" class="d-none d-sm-block">
            </b-col>
            <b-col cols="12" sm="8" class="text-center p-0">
                <GmapMap rounded
                         :center="getStart()"
                         :zoom="getZoom()"
                         map-type-id="terrain"
                         ref="map"
                         @idle="onIdle"
                         style="width: 100%; height: calc(100vh - 56px)"
                >
                    <gmap-cluster :maxZoom="12">
                        <gmap-marker v-for="(item, key) in $store.state.signs" :key="key" :position="getPosition(item)" :icon="getIcon(item)" :clickable="true" @click="toggleModal(item, key)" />
                    </gmap-cluster>
                </GmapMap>
            </b-col>
            <b-col cols="0" sm="2" class="d-none d-sm-block">
            </b-col>
        </b-row>
        <b-modal okOnly ok-title="Close" v-model="modalShow" ref="mapModal" :title="modalTitle">
            <b-row>
                <b-col v-if="modalItem">
                    <b-img v-if="modalItem" rounded="circle" alt="Avatar picture" title="Avatar picture" :src="getUserProfile()" />
                    <em>&nbsp; Found by {{ modalItem.user.displayname }}</em>
                </b-col>
                <b-col cols="1" v-if="modalItem">
                    <span class="float-right text-muted small">
                        #{{ modalItem.id }}
                    </span>
                </b-col>
            </b-row>
            <hr  v-if="modalItem" />
            <p class="my-4" v-if="modalItem">
                {{ modalItem.notes }}
            </p>
            <hr  v-if="modalItem" />
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
    import Vue from 'vue';

    export default {
        data () {
            return {
                modalShow: false,
                modalItem: null,
                modalTitle: 'Ghost Sign Details',
                showPhoto: false,
            }
        },
        methods: {
            getStart: function() {
                let centre = this.$store.state.centre;
                return(centre ? centre : {
                    lat:53.9450,
                    lng:-2.5209
                });
            },

            getZoom: function() {
                let zoom = this.$store.state.zoom;
                return(zoom ? zoom : 7)
            },

            onIdle: function() {
                var self = this;

                // Get the bounds of the map.
                let map = this.$refs.map.$mapObject;
                let bounds = map.getBounds();
                let sw = bounds.getSouthWest();
                let ne = bounds.getNorthEast();

                // Save map position for next time we load the site.
                let centre = map.getCenter();
                let clat = centre.lat();
                let clng = centre.lng();
                this.$store.commit('setCentre', [ clat, clng ])
                this.$store.commit('setZoom', map.getZoom())

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

                        self.$store.commit('setSigns', self.arrange(ret.signs));
                    }
                })
            },

            arrange (data) {
                const RADIUS = 0.0001

                const getKey = (coords) => `${coords.lat}:${coords.lng}`
                const randomInRange = (min, max, seed) => Math.random(seed) * (max - min) + min

                let lookupMap = {}

                // Find out how many points are in the same location.
                data.forEach((item, index) => {
                    let key = getKey(item)
                    if (lookupMap.hasOwnProperty(key)) {
                        lookupMap[key]++
                    } else {
                        lookupMap[key] = 1
                    }
                })

                // Now put each point which is at the same location around a circle.
                data.forEach((item, index) => {
                    let key = getKey(item)
                    console.log("Consider", item, key);

                    if (lookupMap[key] > 1) {
                        let count = 0
                        data.forEach((item2, index2) => {
                            let key2 = getKey(item2)

                            if (key2 == key) {
                                let angle = count++ / (lookupMap[key] / 2) * Math.PI
                                let newlat = item2.lat + (RADIUS * Math.cos(angle))
                                let newlng = item2.lng + (RADIUS * Math.sin(angle))
                                data[index2].lat = newlat
                                data[index2].lng = newlng
                            }
                        })
                    }
                })

                return (data)
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
                this.modalTitle = this.modalItem && this.modalItem.title ? this.modalItem.title : "Ghost Sign Details";
                console.log("show modal", item);
            },

            setShowPhoto: function(val) {
                console.log("Set show", val);
                this.showPhoto = val;
            },

            getUserProfile: function() {
                return this.modalItem ? ('https://graph.facebook.com/' + this.modalItem.user.facebook.facebookid + '/picture') : null;
            }
        },
    }
</script>