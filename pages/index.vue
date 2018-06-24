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
                        style="width: 100%; height: calc(100vh - 56px)"
                >
                </GmapMap>

            </b-col>
            <b-col cols="0" sm="2" class="d-none d-sm-block">
            </b-col>
        </b-row>
    </div>
</template>