<template>
    <v-app light>
        <v-container>
            <v-layout row>
            <v-flex md3>
            <form @submit="subirformulario" name="form_file" id="form_file" >
            <v-subheader>Guardar Arquetipo en PC</v-subheader>
            <p>
                <label for="xmlfile">
                <v-file-input :placeholder="Archivo_valor" name="xmlfile" id="xmlfile"></v-file-input>
                </label>
            </p>
            <v-btn type="submit" id='upload' color="success" dark large form="form_file" >Guardar</v-btn>
            <!-- @click="snackbar = true" -->
            <v-snackbar v-model="snackbar" :multi-line="multiLine"> 
                {{ text }} 
                <v-btn color="red" text @click="snackbar = false">Close</v-btn>
            </v-snackbar>
            </form>
            <v-subheader>Guardar en MongoDB </v-subheader>
            <!--Formulario subir archivo y guardarlo en mongo DB-->
            <form @submit="guardarDB" name="mongo_save" id="mongo_save">
            <p>
                <label for="xmlsave">
                    <v-file-input :placeholder="Archivo_valor" name="xmlsave" id="xmlsave"></v-file-input>
                </label>
            </p>
                <v-btn type="submit" id='guardar' color="success" dark large form="mongo_save">Guardar</v-btn>
            <v-snackbar v-model="snackbar" :multi-line="multiLine"> 
                {{ text }} 
                <v-btn color="red" text @click="snackbar = false">Close</v-btn>
            </v-snackbar>
            </form>
            <!-- -->
            <!--FORMULARIO PARA SUBIR UN ARQUETIPO DESDE EL PC Y PROCESARLO  -->
            <!-- -->
            <form @submit="procesarArquetipo" name="form_file_load" id="form_file_load" >
            <v-subheader>Cargar Arquetipo desde PC</v-subheader>
            <p>
                <label for="xmlfile_load">
                <v-file-input :placeholder="Archivo_valor" name="xmlfile_load" id="xmlfile_load"></v-file-input>
                </label>
            </p>
            <v-btn type="submit" id='procesa' color="success" dark large form="form_file_load">Procesar</v-btn>
            <v-btn color="success" dark large v-on:click="exportar">Exportar Data</v-btn>
            <!-- @click="snackbar = true" -->
            <v-snackbar v-model="snackbar" :multi-line="multiLine"> 
                {{ text }} 
                <v-btn color="red" text @click="snackbar = false">Close</v-btn>
            </v-snackbar>
            </form>
            </v-flex>
            <v-flex md1>
            </v-flex>
            <v-flex md8>
                <div id="jsmind_container">
                </div>
            </v-flex>

        </v-layout>
        </v-container>
    </v-app>
</template>
<script>

export default {
    data(){
        return{
            Archivo_valor: 'Archivo...',
            salida: '',
            snackbar:false,
            multiLine: true,
            text: '',
            data: '',
            nombre_arch_expor: '',
        }
    },
/* mounted(){
var mind = {"meta":{ "name":"archetype", "author":"importe_editor", "version":"1.0" },"format":"node_tree","data":{"id":"root","topic":"Adverse reaction risk","children":[{"id":"301","topic":"data","direction":"right","children":[{"id":"\"100\"","topic":"Substance"},{"id":"\"101\"","topic":"Status"},{"id":"\"102\"","topic":"Criticality"},{"id":"\"103\"","topic":"Category"},{"id":"\"104\"","topic":"Onset of last reaction"},{"id":"\"105\"","topic":"Reaction mechanism"},{"id":"\"106\"","topic":"Comment"},{"id":"107","topic":"Reaction event","children":[{"id":"200","topic":"Reaction event"},{"id":"201","topic":"Specific substance"},{"id":"202","topic":"Certainty"},{"id":"203","topic":"Manifestation"},{"id":"204","topic":"Reaction description"},{"id":"205","topic":"Onset of reaction"},{"id":"206","topic":"Duration of reaction"},{"id":"207","topic":"Severity of reaction"},{"id":"208","topic":"Reaction details"},{"id":"209","topic":"Initial exposure"},{"id":"210","topic":"Duration of exposure"},{"id":"211","topic":"Route of exposure"},{"id":"212","topic":"Exposure description"},{"id":"213","topic":"Exposure details"},{"id":"214","topic":"Clinical management description"},{"id":"215","topic":"Clinical management details"},{"id":"216","topic":"Reporting details"},{"id":"217","topic":"Information source"},{"id":"218","topic":"Reaction comment"}]}]},{"id":"402","topic":"protocol","direction":"left","children":[{"id":"\"600\"","topic":"Last updated"},{"id":"\"601\"","topic":"Extension"},{"id":"\"602\"","topic":"Supporting clinical record information"},{"id":"\"603\"","topic":"Reaction reported?"},{"id":"604","topic":"Report summary","children":[{"id":"700","topic":"Report summary"},{"id":"701","topic":"Date of report"},{"id":"702","topic":"Report comment"},{"id":"703","topic":"Adverse reaction report"}]}]}]}};
    var options = {
        container:'jsmind_container',
        editable:true,
        theme:'primary'
    }
    var jm = jsMind.show(options,mind);
    },
*/
    methods:{
        subirformulario(file){
            file.preventDefault();
            let data = new FormData();
            let currentObj = this;
            //let file_new = document.getElementById('form_file');
            data.append('xmlfile',document.getElementById('xmlfile').files[0]);
            let settings = { headers: { 'content-type': 'multipart/form-data' } };

            axios.post('/process',data,settings)
            .then(function(response){
                let respuesta = response.data;
                console.log(respuesta);
                currentObj.snackbar = true;
                currentObj.text = respuesta.msg;
                //currentObj.Archivo_valor = "Archivo...";
                //currentObj.$emit('update:value',"null");
                //arreglar esto --------------------------
                //const input = currentObj.$refs.fileinput;
                //input.value = null;
                
            })
            .catch(function(err){
                //currentObj.salida = err;
                currentObj.snackbar = true;
                currentObj.text = err;
                console.log(err);
                
            })
            ;
        },
        guardarDB(formulario){
            formulario.preventDefault();
            let data = new FormData();
            let currentObj = this;
            let file_form = document.getElementById('xmlsave');
            let settings = { headers: { 'content-type': 'multipart/form-data' } };
            data.append('xmlsave',file_form.files[0]);
            data.append('nombre',file_form.files[0].name);
            data.append('extension','xml');
            axios.post('/insertar',data,settings)
            .then(function(response){
                let rspta = response.data;
                currentObj.snackbar = true;
                currentObj.text = rspta.msg;
            })
            .catch(function(error){
                console.log(error.message);
            })
        },
        procesarArquetipo(formulario){
            formulario.preventDefault();
            let data = new FormData();
            let currentObj = this;

            let archv_formulario = document.getElementById('xmlfile_load');
            let settings = { headers: { 'content-type': 'multipart/form-data' } };
            currentObj.nombre_arch_expor = archv_formulario.files[0].name
            data.append('archivo_xml',archv_formulario.files[0]);
            data.append('nombre_xml',archv_formulario.files[0].name);
            axios.post('/procesar_xml',data,settings)
            .then(function (response) {
                let rspta = response.data;
                currentObj.snackbar = true;
                currentObj.text = rspta.msg;
                let padre_jsmind = rspta.padre;
                let nombre_archetype = rspta.nombre_archetype;
                //console.log(padre_jsmind);                
                var mind = JSON.parse(padre_jsmind);
                
                var options = {
                    container:'jsmind_container',
                    editable:true,
                    theme:'primary'
                }

                var jm = jsMind.show(options,mind);
                jm.collapse_all();
                currentObj.data = jm

            })
            .catch(function (error) {
                console.log(error.message);
                console.log(error.response.data);
                console.log(error.response.status);
                console.log(error.response.headers);
            })            

        },
        exportar(formulario){
            formulario.preventDefault();
            let currentObj = this
            var mind_data = currentObj.data.get_data('node_array');
            var mind_name = mind_data.meta.name;
            var mind_str = jsMind.util.json.json2string(mind_data);
            jsMind.util.file.save(mind_str,'text/jsmind',mind_name+'.json');
            
            
           /* 
            var datos_exportar = currentObj.data.get_data() //get data obtiene el mind del modelo jsmind 
            var mind_string = jsMind.util.json.json2string(datos_exportar);
            var nombre_arquetipo = currentObj.nombre_arch_expor
            var exportObj = mind_string
            var exportName= nombre_arquetipo+"data"
            var dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(exportObj));
            var downloadAnchorNode = document.createElement('a');
            downloadAnchorNode.setAttribute("href",     dataStr);
            downloadAnchorNode.setAttribute("download", exportName + ".json");
            document.body.appendChild(downloadAnchorNode); // required for firefox
            downloadAnchorNode.click();
            downloadAnchorNode.remove();
            */
        },
    }
}
</script>
<style lang="scss">
    @import '../../../node_modules/vuetify/src/styles/main';
</style>
