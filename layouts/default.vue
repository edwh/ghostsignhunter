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
                    <b-nav-item href="/add">Add</b-nav-item>
                </b-navbar-nav>

                <b-navbar-nav class="ml-auto">
                    <b-nav-item href="/privacy">Privacy</b-nav-item>
                    <b-nav-item href="/terms">Terms</b-nav-item>
                </b-navbar-nav>

            </b-collapse>
        </b-navbar>
        <nuxt/>
    </div>

</template>