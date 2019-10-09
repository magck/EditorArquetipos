import Vue from 'vue';
import axios from 'axios';
import Vuetify from 'vuetify';
import jsmind from 'jsmind';
//import jsmind from '../../public/js/jsmind';
window.Vue = require('vue');
//import '../../node_modules/jsmind/style/jsmind.css';
//import '../../node_modules/jsmind/js/jsmind.draggable.js';
window.axios = axios;
window.jsMind = jsmind;
import "../../node_modules/vuetify/dist/vuetify.min.css"
Vue.use(Vuetify);
//Vue.config.productionTip = false
Vue.component('example-component', require('./components/barra_navComponent.vue').default);
Vue.component('homepage-component', require('./components/homepageComponent.vue').default);
Vue.component('editor-component', require('./components/editorComponent.vue').default);
Vue.component('crear-component',require('./components/crearComponent.vue').default);
const app = new Vue({
    el: '#app',
    vuetify: new Vuetify(),
})

