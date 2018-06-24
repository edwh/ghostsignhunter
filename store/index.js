import Vuex from 'vuex'

const createStore = () => {
    return new Vuex.Store({
        state: {
            signs: []
        },
        mutations: {
            setSigns (state, signs) {
                state.signs = signs;
            }
        }
    })
}

export default createStore