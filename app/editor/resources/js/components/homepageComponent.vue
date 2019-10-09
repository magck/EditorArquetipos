<template>
   <v-app light>
    <v-content>
      <section>
        <v-parallax  :src="require('../../image/hero.jpeg')" height="600">
        
          <v-layout
            column
            align-center
            justify-center
            class="white--text"
          >
            <img :src="require('../../image/vuetify.png')" alt="Vuetify.js" height="200">
            <h1 class="white--text mb-2 display-1 text-center">Arquetipos</h1>
            <div class="subheading mb-4 text-center">Powered by Vuetify+Laravel+Vue</div>
            <v-btn class="mt-12" color="blue lighten-2" dark large href="" > Comenzar  </v-btn>
          </v-layout>
        </v-parallax>
      </section>

      <section>
        <v-layout column wrap class="my-12" align-center>
          <v-flex xs12 sm4 class="my-4">
            <div class="text-center">
              <h2 class="headline">¿Que son los arquetipos?</h2>
            </div>
          </v-flex>
          <v-flex xs12>
            <v-container grid-list-xl>
              <v-layout row wrap align-center>
                <v-flex xs12 md4>
                  <v-card flat class="transparent">
                    <v-card-text class="text-center">
                      <v-icon x-large class="blue--text text--lighten-2">mdi-palette</v-icon>
                    </v-card-text>
                    <v-card-title primary-title class="layout justify-center">
                      <div class="headline text-center">Definicion</div>
                    </v-card-title>
                    <v-card-text>
                      Son un modelo original que sirve como pauta para imitarlo, reproducirlo o copiarlo, 
                      o prototipo ideal que sirve como ejemplo, en cuanto a este proyecto el trabajo sera realizado
                      con arquetipos medicos los cuales contienen informacion de pacientes y pautas a seguir.
                    </v-card-text>
                  </v-card>
                </v-flex>
                <v-flex xs12 md4>
                  <v-card flat class="transparent">
                    <v-card-text class="text-center">
                      <v-icon x-large class="blue--text text--lighten-2">mdi-flash</v-icon>
                    </v-card-text>
                    <v-card-title primary-title class="layout justify-center">
                      <div class="headline">Objetivos de este proyecto</div>
                    </v-card-title>
                    <v-card-text>
                      El de crear un sitio web completo el cual sirva para la creacion y edicion de arquetipos
                      asi tambien el lograr importar arquetipos en formato ADL y XML desde el repositorio, cuya finalidad
                      sera de proveer materia prima a los alumnos de segundo año para la creacion de fichas dinamicas.

                    </v-card-text>
                  </v-card>
                </v-flex>
                <v-flex xs12 md4>
                  <v-card flat class="transparent">
                    <v-card-text class="text-center">
                      <v-icon x-large class="blue--text text--lighten-2">mdi-wrench</v-icon>
                    </v-card-text>
                    <v-card-title primary-title class="layout justify-center">
                      <div class="headline text-center">Completamente Open Source</div>
                    </v-card-title>
                    <v-card-text>
                      La ventaja principal del software open source es la posibilidad de compartir, modificar y estudiar el 
                      código fuente de un sistema. Por otro lado, el código abierto promueve la colaboración entre
                      usuarios. Esta característica supone el desarrollo rápido y variado de multitud de herramientas. 
                    </v-card-text>
                  </v-card>
                </v-flex>
              </v-layout>
            </v-container>
          </v-flex>
        </v-layout>
      </section>

      <section>
        <v-parallax :src="require('../../image/section.jpg')" height="380">
          <v-layout column align-center justify-center>
            <div class="headline white--text mb-4 text-center">Web development has never been easier</div>
            <em>Kick-start your application today</em>
            <v-btn class="mt-12" color="blue lighten-2" dark large href="/pre-made-themes"> Get Started</v-btn>
          </v-layout>
        </v-parallax>
      </section>

      <section>
        <v-container grid-list-xl>
          <v-layout row wrap justify-center class="my-12">
            <v-flex xs12 sm4>
              <v-card flat class="transparent">
                <v-card-title primary-title class="layout justify-center">
                  <div class="headline">Informacion</div>
                </v-card-title>
                <v-card-text>
                  
                </v-card-text>
              </v-card>
            </v-flex>
            <v-flex xs12 sm4 offset-sm1>
              <v-card flat class="transparent">
                <v-card-title primary-title class="layout justify-center">
                  <div class="headline">Contacto</div>
                </v-card-title>
                <v-card-text>
                  <!-- aasasa-->
                </v-card-text>
                <v-list class="transparent">
                  <v-list-item>
                    <v-list-item-action>
                      <v-icon class="blue--text text--lighten-2">mdi-phone</v-icon>
                    </v-list-item-action>
                    <v-list-item-content>
                      <v-list-item-title><!-- aasasa--></v-list-item-title>
                    </v-list-item-content>
                  </v-list-item>
                  <v-list-item>
                    <v-list-item-action>
                      <v-icon class="blue--text text--lighten-2">mdi-map-marker</v-icon>
                    </v-list-item-action>
                    <v-list-item-content>
                      <v-list-item-title><!-- aasasa--></v-list-item-title>
                    </v-list-item-content>
                  </v-list-item>
                  <v-list-item>
                    <v-list-item-action>
                      <v-icon class="blue--text text--lighten-2">mdi-email</v-icon>
                    </v-list-item-action>
                    <v-list-item-content>
                      <v-list-item-title><!-- aasasa--></v-list-item-title>
                    </v-list-item-content>
                  </v-list-item>
                </v-list>
              </v-card>
            </v-flex>
          </v-layout>
        </v-container>
      </section>

      <v-footer color="blue darken-2">
        <v-layout row wrap align-center>
          <v-flex xs12>
            <div class="white--text ml-4">
              Made with
              <v-icon class="red--text">mdi-heart</v-icon>
              by <a class="white--text" href="https://vuetifyjs.com" target="_blank">Vuetify</a>
              and <a class="white--text" href="https://github.com/vwxyzjn">Costa Huang</a>
            </div>
          </v-flex>
        </v-layout>
      </v-footer>
    </v-content>
  </v-app>
</template>

