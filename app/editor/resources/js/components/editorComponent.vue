<template>
    <v-app light>
        <v-container>
            <v-layout row>
            <v-flex md3>
            <form action="/process" enctype="multipart/form-data" method="POST">
            <p>
                <label for="xmlfile">
                <v-file-input label="File input" name="xmlfile" id="xmlfile"></v-file-input>
                <!--<input type="file" name="xmlfile" id="xmlfile">-->
                </label>
            </p>
            <v-btn><button color="blue lighten-2" dark large>Cargar!</button></v-btn>
            <input type="hidden" name="_token" :value="csrf"><!--csfr token necesitado por laravel para ataques csrf-->
            </form>
            </v-flex>
            <v-flex md1>
            </v-flex>
            <v-flex md8>
                Lorem ipsum dolor, sit amet consectetur adipisicing elit. Ab quos debitis ex ad officia,
                 atque tenetur voluptatem maxime. Eveniet quisquam consequatur excepturi ipsa eaque, 
                 laborum omnis est optio libero eligendi!
            </v-flex>

        </v-layout>
        </v-container>
    </v-app>
</template>
<script>
export default {
    data(){
        return{
            csrf: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    }
}
</script>
<style lang="scss">
    @import '../../../node_modules/vuetify/src/styles/main';
</style>
