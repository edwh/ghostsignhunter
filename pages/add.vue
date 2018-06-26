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
                <p class="mt-3">This photo was taken at {{ Math.round(lat * 1000 ) / 1000 }}, {{ Math.round(lng * 1000) / 1000 }}</p>
                <p>You can drag the marker to alter the position.</p>
                <b-img width="200" height="200" :src="getStaticMap()" />
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
                imagedata: null
            }
        },
        methods: {
            getURL: function() {
                return API + 'image'
            },

            getStaticMap: function() {
                let lat = this.lat;
                let lng = this.lng;
                let url = 'https://maps.googleapis.com/maps/api/staticmap?center=' + lat + "," + lng + "&markers=color:blue%7C" + lat + "," + lng + "&size=200x200&zoom=16&key=" + GOOGLE_MAPKEY;
                return(url)
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

                    if (exifObj.hasOwnProperty('GPS')) {
                        let gps = exifObj.GPS;
                        lat = ConvertDMSToDD(gps[2][0][0], gps[2][1][0], gps[2][2][0], gps[1]);
                        lng = ConvertDMSToDD(gps[4][0][0], gps[4][1][0], gps[4][2][0], gps[3]);
                    }

                    self.lat = lat;
                    self.lng = lng;
                    self.latlng = true;
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