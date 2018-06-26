<template>
    <b-container fluid>
        <b-row>
            <b-col cols="12" class="text-center">
                <p class="mt-3" v-if="!imagedata">
                    Upload your ghosts here!  We'll work out where they were taken if we can, and then you can
                    post them to the site.
                </p>
                <vue-core-image-upload
                        text="Choose photo"
                        class="mt-3 btn btn-success"
                        @imagechanged="imagechanged"
                        inputOfFile="photo"
                        :isXhr="false"
                        :url="getURL()" >
                </vue-core-image-upload>
            </b-col>
        </b-row>
        <b-row class="mt-3" v-if="imagedata && !latlng">
            <b-col cols-sm="2">
            </b-col>
            <b-col cols-sm="8">
                <b-alert variant="warning" show>Sorry, we only handle photos which have location information inside them at the moment.</b-alert>
            </b-col>
            <b-col cols-sm="2">
            </b-col>
        </b-row>
        <b-row>
            <b-col cols-sm="6" class="text-center" v-if="imagedata">
                <b-img thumbnail style="max-width: 400px" class="mt-3" v-if="imagedata" :src="imagedata" />
            </b-col>
            <b-col cols-sm="6" class="text-center" v-if="latlng">
                <p class="mt-3">This photo was taken at {{ Math.round(lat * 100000 ) / 100000 }}, {{ Math.round(lng * 100000) / 100000 }}</p>
                <p v-if="address"><em><b>{{ address }}</b></em></p>
                <GmapMap rounded
                         :center="latlng"
                         :zoom="16"
                         ref="map"
                         :options="mapOptions"
                         style="width: 200px; height: 200px; float: none; margin: 0 auto;">
                    <gmap-marker
                            :position="latlng"
                            :draggable="true"
                            :icon="getIcon()",
                            :animation="2",
                            @dragend="drag"
                    />
                </GmapMap>
                <p>Drag the marker to alter the position</p>
            </b-col>
        </b-row>
        <b-row class="mt-5">
            <b-col cols="0" sm="3"></b-col>
            <b-col cols="12" sm="6">
                <b-form @submit="submit" v-if="imagedata && latlng">
                    <b-form-input v-model="title" type="text" placeholder="Make up a title for this ghost" />
                    <b-form-textarea ref="notes" v-model="notes" placeholder="Any notes you want to add about the ghost or how to find it" :rows="3" :max-rows="6" />
                    <b-button :disabled="posting" class="mt-3" type="submit" variant="success">Post your ghost!</b-button>
                </b-form>
            </b-col>
        </b-row>
        <b-row class="mt-5" v-if="percentCompleted > 0 && percentCompleted < 100">
            <b-col cols="0" sm="3"></b-col>
            <b-col cols="12" sm="6">
                <b-progress :value="percentCompleted" :max="100" show-progress animated></b-progress>
            </b-col>
        </b-row>
        <b-row v-if="postSucceeded" class="mt-3">
            <b-col cols-sm="2">
            </b-col>
            <b-col cols-sm="8">
                <b-alert variant="success" show>Thanks!  This is now on the map.</b-alert>
            </b-col>
            <b-col cols-sm="2">
            </b-col>
        </b-row>
        <b-row v-if="postFailed" class="mt-3">
            <b-col cols-sm="2">
            </b-col>
            <b-col cols-sm="8">
                <b-alert variant="danger" show>Sorry, something went wrong.  Please try later.</b-alert>
            </b-col>
            <b-col cols-sm="2">
            </b-col>
        </b-row>
    </b-container>
</template>

<script>
    import axios from 'axios';
    import piexif from 'piexifjs';

    function ConvertDMSToDD(degrees, minutes, seconds, direction) {
        var dd = degrees + minutes/60 + seconds/(60*60);

        if (direction == "S" || direction == "W") {
            dd = dd * -1;
        } // Don't do anything for N or E
        return dd;
    }

    export default {
        data() {
            return {
                latlng: false,
                lat: null,
                lng: null,
                fileinput: null,
                imagedata: null,
                address: null,
                title: null,
                notes: null,
                posting: false,
                postSucceeded: false,
                postFailed: false,
                percentComplete: 0,
                mapOptions: {
                    fullscreenControl: false,
                    mapTypeControl: false,
                    streetViewControl: false
                }
            }
        },
        methods: {
            getURL: function() {
                return API + 'image'
            },

            getIcon: function() {
                return({
                    url: require('~/assets/mapicon.png'),
                    size: {width: 46, height: 46, f: 'px', b: 'px'},
                    scaledSize: {width: 23, height: 23, f: 'px', b: 'px'},
                    anchor: new google.maps.Point(0, 32)
                });
            },

            getStaticMap: function() {
                let lat = this.lat;
                let lng = this.lng;
                let url = 'https://maps.googleapis.com/maps/api/staticmap?center=' + lat + "," + lng + "&markers=color:blue%7C" + lat + "," + lng + "&size=200x200&zoom=16&key=" + GOOGLE_MAPKEY;
                return(url)
            },

            drag: function(e) {
                this.lat = Math.round(100000 * e.latLng.lat()) / 100000;
                this.lng = Math.round(100000 * e.latLng.lng()) / 100000;
            },

            imagechanged(res) {
                var self = this;

                self.fileinput = res;
                self.postSucceeded = false;
                self.postFailed = false;

                // Load the file.
                let reader = new window.FileReader()
                reader.onloadend = function (event) {
                    // Get the exif data.
                    let data = event.target.result
                    let exifObj = piexif.load(data);

                    let lat = null;
                    let lng = null;
                    let latlng = null;

                    if (exifObj.hasOwnProperty('GPS')) {
                        let gps = exifObj.GPS;
                        console.log("GPS is", gps);

                        if (gps[2] && gps[4]) {
                            lat = ConvertDMSToDD(gps[2][0][0], gps[2][1][0], gps[2][2][0], gps[1]);
                            lng = ConvertDMSToDD(gps[4][0][0], gps[4][1][0], gps[4][2][0], gps[3]);

                            // Find the location.
                            let geocoder = new google.maps.Geocoder;
                            latlng = new google.maps.LatLng(lat, lng)
                            geocoder.geocode({'location': latlng}, function(results, status) {
                                if (status === 'OK' && results.length) {
                                    console.log("Got results", results);
                                    self.address = results[0].formatted_address;
                                    self.title = self.address;
                                    console.log("Address", self.address, self.title);
                                    self.$refs.notes.$el.focus()
                                }
                            });
                        }
                    }

                    self.lat = lat;
                    self.lng = lng;
                    self.latlng = latlng;
                    console.log("Set location", self.lat, self.lng, self.latlng);
                }

                reader.readAsBinaryString(res);

                // Also read into a thumbnail.
                let reader2 = new window.FileReader();
                reader2.onloadend = function() {
                    self.imagedata = reader2.result;
                    console.log("Set image data");
                }

                reader2.readAsDataURL(res);
            },

            submit: function(e) {
                var self = this;

                e.preventDefault();
                console.log("post")

                this.posting = true;

                var data = new FormData();
                data.append('lat', this.lat);
                data.append('lng', this.lng);
                data.append('title', this.title);
                data.append('notes', this.notes);
                data.append('photo', this.fileinput);

                let config = {
                    onUploadProgress(progressEvent) {
                        self.percentCompleted = Math.round((progressEvent.loaded * 100) /
                            progressEvent.total);
                        console.log("Percent complete", self.percentCompleted);
                        return self.percentCompleted;
                    },
                    withCredentials: true
                };

                axios.post(API + 'image', data, config)
                    .then(function (response) {
                        console.log("Post succeeded", response);
                        self.posting = false;
                        self.postSucceeded = true;
                    })
                    .catch(function (error) {
                        console.log("Post failed", error);
                        self.posting = false;
                        self.postFailed = true;
                    });
            }
        }
    }
</script>