<template>
    <b-container fluid>
        <b-row>
            <b-col cols="12" class="text-center">
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
        <b-row>
            <b-col cols="12" cols-sm="8" class="text-center" v-if="latlng">
                <b-img thumbnail style="max-width: 400px" class="mt-3" v-if="imagedata" :src="imagedata" />
                <p class="mt-3">This photo was taken at {{ Math.round(lat * 100000 ) / 100000 }}, {{ Math.round(lng * 100000) / 100000 }}</p>
                <p v-if="address"><em><b>{{ address }}</b></em></p>
                <p>You can drag the marker to alter the position - perhaps you took the picture from a distance.</p>
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
            </b-col>
        </b-row>
    </b-container>
</template>

<script>
    import exif from 'exif-parser';
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
                imagedata: null,
                address: null,
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
                console.log("Drag", e);
                this.lat = Math.round(100000 * e.latLng.lat()) / 100000;
                this.lng = Math.round(100000 * e.latLng.lng()) / 100000;
            },

            imagechanged(res) {
                var self = this;

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
                        lat = ConvertDMSToDD(gps[2][0][0], gps[2][1][0], gps[2][2][0], gps[1]);
                        lng = ConvertDMSToDD(gps[4][0][0], gps[4][1][0], gps[4][2][0], gps[3]);

                        // Find the location.
                        let geocoder = new google.maps.Geocoder;
                        latlng = new google.maps.LatLng(lat, lng)
                        geocoder.geocode({'location': latlng}, function(results, status) {
                            if (status === 'OK' && results.length) {
                                console.log("Got results", results);
                                self.address = results[0].formatted_address;
                                console.log("Address", this.address);
                            }
                        });
                    }

                    self.lat = lat;
                    self.lng = lng;
                    self.latlng = latlng;
                }

                reader.readAsBinaryString(res);

                // Also read into a thumbnail.
                let reader2 = new window.FileReader();
                reader2.onloadend = function() {
                    self.imagedata = reader2.result;
                }

                reader2.readAsDataURL(res);
            }
        }
    }
</script>