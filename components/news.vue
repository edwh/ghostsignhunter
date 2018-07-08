<template>
    <div>
        <ul class="list-unstyled">
            <li v-for="(post, index) in news" :key="index">
                <div v-if="post.type == 'Added'">
                    <em class="small">{{ post.sign.added | moment }}</em><br />
                    {{ post.user.displayname }} <span class="text-muted">added</span>
                    <span v-if="post.sign.name">
                        {{ post.sign.name }}
                    </span>
                    <b-img fluid rounded alt="Ghost sign image" title="Ghost sign image" :src="post.sign.paththumb" />
                </div>
            </li>
        </ul>
    </div>
</template>

<script>

    import axios from 'axios'
    import moment from 'moment';

    export default {
        filters: {
            moment: function (date) {
                return moment(date).format('MMMM Do YYYY, h:mm:ss a');
            }
        },
        computed: {
            news() {
                console.log("Returning news", this.$store.state.news);
                return this.$store.state.news
            }
        },
        mounted: function() {
            var self = this;

            console.log("Mounted", self, this.$store);
            return axios.get(API + 'news').then((res) => {
                console.log("Fetched", res);
                this.$store.commit('setNews', res.data.news)
                console.log("Saved news", res.data.news);
            })
        },

        methods: {}
    }
</script>