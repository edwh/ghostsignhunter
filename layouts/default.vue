<template>
    <div>
        <b-navbar toggleable="md" type="dark" variant="info">

            <b-navbar-toggle target="nav_collapse"></b-navbar-toggle>

            <b-navbar-brand href="#">
                <b-img rounded alt="Logo" title="Logo" width=30 height=30 src="~/assets/icon.png" />
            </b-navbar-brand>

            <b-collapse is-nav id="nav_collapse">

                <b-navbar-nav>
                    <b-nav-item href="/">Map</b-nav-item>
                    <b-nav-item v-if="$store.state.loggedIn" href="/add">Add</b-nav-item>
                    <b-nav-item v-else @click="login">Add</b-nav-item>
                </b-navbar-nav>

                <b-navbar-nav class="ml-auto">
                    <b-nav-item href="/privacy">Privacy</b-nav-item>
                    <b-nav-item href="/terms">Terms</b-nav-item>
                    <b-nav-item v-if="$store.state.loggedIn">
                        <b-button size="sm" @click="logout">Logout</b-button>
                    </b-nav-item>
                    <b-nav-item v-else>
                        <b-button size="sm" variant="warning" @click="login">Login</b-button>
                    </b-nav-item>
                </b-navbar-nav>

            </b-collapse>
        </b-navbar>
        <b-modal okOnly ok-title="Close" v-model="loginShow" ref="loginModal" title="Log in">
            <b-row>
                <b-col class="text-center">
                    <p>We only support logging in with Facebook at the moment.</p>
                    <b-img class="clickme" v-if="isFBReady" @click="fblogin" src="~/assets/signin/facebook.png" alt="Log in with Facebook" title="Log in with Facebook" />
                </b-col>
            </b-row>
        </b-modal>

        <nuxt/>
    </div>
</template>

<script>
    import axios from 'axios';
    import Vue from 'vue';

    export default {
        data () {
            return {
                isFBReady: false,
                loginShow: false
            }
        },
        mounted: function () {
            this.isFBReady = Vue.FB != undefined
            window.addEventListener('fb-sdk-ready', this.onFBReady)

            console.log("Logged in?", this.$store.state.loggedIn);
            if (this.$store.state.loggedIn) {
                // We think we're logged in.  Bounce it off the server to check.
                this.loginToServer();
            }
        },
        beforeDestroy: function () {
            window.removeEventListener('fb-sdk-ready', this.onFBReady)
        },
        methods: {
            onFBReady: function () {
                console.log("Facebook is ready");
                this.isFBReady = true
            },

            login: function() {
                console.log("Login");
                this.loginShow = true;
            },

            loginToServer: function() {
                let self = this;

                // Get login details.
                let fb = this.$store.state.facebook;
                console.log("Got fb", fb);

                if (fb) {
                    let data = new FormData();
                    data.append('facebooktoken', fb.token);

                    axios.post(API + 'user', data)
                        .then(function (response) {
                            console.log("login response", response);
                            let ret = response.data

                            if (ret.ret !== 0) {
                                console.error("Not logged in on server", ret);
                                self.$store.commit('clearFacebook');
                            }
                        })
                        .catch(function (error) {
                            console.log("login error", error);
                        });
                } else {
                    self.$store.commit('clearFacebook');
                }
            },

            fblogin: function() {
                var self = this;
                console.log("Facebook sign in", self);

                Vue.FB.login(function (response) {
                    console.log("Facebook login returned", response);
                    if (response.status == 'connected') {
                        let data = {
                            token: response.authResponse.accessToken,
                            facebookid: response.authResponse.userID
                        }

                        console.log("Set facebook", self, data);
                        self.$store.commit('setFacebook', data);
                        self.loginShow = false;
                        self.loginToServer();
                    }
                }, {
                    scope: 'email'
                });
            },

            logout: function() {
                var self = this;
                self.$store.commit('clearFacebook');
            }
        }
    }
</script>