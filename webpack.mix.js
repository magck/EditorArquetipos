const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */


mix.js('resources/js/app.js', 'public/js')
   .sass('resources/sass/app.scss', 'public/css')
   .scripts(['node_modules/jsmind/js/jsmind.js',
   'node_modules/jsmind/js/jsmind.draggable.js',
   'node_modules/jsmind/js/jsmind.screenshot.js'],'public/js/jsmind.js')