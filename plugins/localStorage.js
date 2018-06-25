import createPersistedState from 'vuex-persistedstate'

export default ({store}) => {
    createPersistedState({
        key: 'ghostsigns'
    })(store)
}