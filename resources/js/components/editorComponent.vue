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
                <v-btn color="green" text @click="snackbar = false">Close</v-btn>
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
                <v-btn color="green" text @click="snackbar = false">Close</v-btn>
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
                <v-btn color="green" text @click="snackbar = false">Close</v-btn>
            </v-snackbar>
            </form>
            </v-flex>
            <v-flex md1>
            </v-flex>
            <v-flex md8>
                <div id="main"></div>
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
/*      mounted(){
          var mind = {"meta":{ "name":"archetype", "author":"importe_editor", "version":"1.0" },"format":"node_tree","data":{"id":"root","topic":"Anatomical location","children":[{"id":"1000","topic":"items","direction":"left","children":[{"id":"1001","topic":"Body site name"},{"id":"1051","topic":"Specific site"},{"id":"1101","topic":"Laterality"},{"id":"1151","topic":"Occurrence"},{"id":"1201","topic":"Aspect"},{"id":"1251","topic":"Anatomical Line"},{"id":"1301","topic":"Description"},{"id":"1351","topic":"Alternative structure"},{"id":"1401","topic":"Multimedia representation"}]},{"id":"200","topic":"description","direction":"right","children":[{"id":"201","topic":"Concept description","children":[{"id":"202","topic":"A physical site on or within the human body."}]},{"id":"251","topic":"purpose","children":[{"id":"252","topic":"To identify and record structured details about one or more physical sites on, or within, the human body using macroscopic anatomical terms."}]},{"id":"301","topic":"use","children":[{"id":"302","topic":"Use to record structured and consistent details about one or more identified physical sites on, or within, the human body. \r\n\r\nThis archetype is specifically designed to be used within the context of any appropriate ENTRY or CLUSTER archetypes which supply the context of the anatomical location. \r\n\r\nAs a fundamental part of clinical practice, clinicians can describe anatomical locations in a myriad of complex and variable ways. In practice, some archetypes carry a single data element for carrying a simple description of body site - for example, OBSERVATION.blood_pressure and CLUSTER.symptom when describing ear pain. In this situation, where the value set is predictable and simple to define, this single data element is a very accurate and pragmatic way to record the site in the body and to query at a later date. However in the situation where the anatomical location is not well defined or needs to be determined at run-time, it may be more flexible to use this structured archetype. For example, in the situation where any symptom can be recorded without any predefined scope of the type of symptom, then allowing the use of this archetype to specifically define an anatomical location in the body may be useful. In this case the CLUSTER.symptom archetype also carries a SLOT for 'Detailed anatomical location' which can include this archetype to support maximal flexibility in recording anatomical location data.\r\n\r\nThis archetype supports recording complex structured anatomical sites. For example, the apex beat of the heart is typically found at the fifth left intercostal space in the mid-clavicular line, tenderness at McBurney's point on the abdominal wall or a laceration on the palmar aspect of the proximal right thumb.\r\n\r\nA combination of the data elements in this archetype can be used to individually record each component of a postcoordinated terminology expression that represents the anatomical site.\r\n\r\nThe 'Alternative structure' SLOT allows inclusion of additional archetypes that provide an alternative structure for describing the same body site, such as CLUSTER.anatomical_location_relative or CLUSTER.anatomical_location_clock, should this be required. In the situation where this archetype can only be used to name a large and\/or non-specific body part, the additional use of the CLUSTER.anatomical_location_relative archetype will support recording of a more precise location - for example, 2 cm anterior to the cubital fossa of the left forearm or 4 cm below R costal margin on the chest wall in the mid-clavicular line.\r\n\r\nIf this archetype is used within other archetypes where the specified subject of care is not the individual for whom the record is being created, for example a fetus in-utero, then the anatomical location will be identifying a body site on or within the fetus."}]},{"id":"351","topic":"keywords","children":[{"id":"352","topic":"location,site,anatomical,anatomic region,topographic anatomy,macroscopic,anatomic,anatomy"}]},{"id":"401","topic":"references","children":[{"id":"402","topic":"Anatomy Mapper website [Internet]. Matt Molenda, [cited 2015 Apr 27]. Available from: http:\/\/www.anatomymapper.com\/."}]}]}]}};
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
                console.log(padre_jsmind);                
                var mind = JSON.parse(padre_jsmind);
                var id_container = currentObj.crearDiv();
                var options = {
                    container:id_container,
                    editable:true,
                    theme:'primary'
                }

                var jm = jsMind.show(options,mind);
                jm.collapse_all();
                currentObj.data = jm

            })
            .catch(function (error) {
                let mensaje_error = error.response.data.msg;
                currentObj.snackbar = true;
                currentObj.text = mensaje_error;

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
        crearDiv(self){
            let currentObj = this;
            var c = document.getElementById("main").childNodes.length; 
            var div = document.createElement("div");
            div.style.width = "800px";
            div.style.height = "500px";
            div.style.border = "solid 1px #ccc";
            div.style.background = "#f4f4f4";
            var id = c+1;
            div.id = "jsmind_container"+id;            
            var boton = currentObj.crearBtn();
            document.getElementById("main").appendChild(div);
            document.getElementById("jsmind_container"+id).appendChild(boton);

            return div.id;
        },
        crearBtn(stringHTML){
            var b = document.createElement("button");
            b.style.color = "white";
            b.style.backgroundColor = "green";
            b.innerHTML = "guardar";
            return b;
        },
    }
}
</script>
<style lang="scss">
    @import '../../../node_modules/vuetify/src/styles/main';
</style>
