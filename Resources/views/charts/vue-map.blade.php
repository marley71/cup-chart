<style type="text/css">
    .map-title {
        display: table;
        position: relative;
        margin: 0px auto;
        word-wrap: anywhere;
        white-space: pre-wrap;
        padding: 10px;
        border: none;
        border-radius: 3px;
        font-size: 12px;
        text-align: center;
        color: #222;
        background: #fff;
    }
</style>
<template id="vue-map-template">
    <div v-if="!loading" class="container d-flex flex-column min-h-75vh">
        <hr class="w-100 mb--20"/>
        <div class="row">
{{--            <div class="col-12">--}}
{{--                <h4>@{{ description }}</h4>--}}
{{--            </div>--}}

            <div class="col-12 col-lg-9 mb-3">
                <div class="row" v-if="Object.keys(context).length > 0">
                    <div class="col-6" v-for="(ctx,key) in context">
                        <div>@{{key}}</div>
                        <select class="form-control" :name="key" v-on:change="changeContext($event)">
                            <option v-for="(label,keyLabel) in ctx.domainValues" :value="keyLabel">@{{label}}</option>
                        </select>
                    </div>

                </div>
                <div class="row">
                    <div class="col-12 bg-white shadow-primary-xs rounded p-0 mt-4 ">
                        <div :id="title_id" class="d-none map-title">@{{ description }}</div>
                        <div :id="map_id" class="h--600 w-100 rounded">

                        </div>
                        <div>
                            <label>Stile mappa</label>
                            <input map-style  type="radio" name="rtoggle" value="mapbox://styles/mapbox/satellite-v9">
                            <!-- See a list of Mapbox-hosted public styles at -->
                            <!-- https://docs.mapbox.com/api/maps/styles/#mapbox-styles -->
                            <label for="satellite-v9">satellite</label>
                            <input map-style  type="radio" name="rtoggle" value="mapbox://styles/mapbox/light-v10">
                            <label for="light-v10">light</label>
                            <input map-style  type="radio" name="rtoggle" value="mapbox://styles/mapbox/dark-v10">
                            <label for="dark-v10">dark</label>
                            <input map-style  type="radio" name="rtoggle" value="mapbox://styles/mapbox/streets-v11">
                            <label for="streets-v11">streets</label>
                            <input map-style type="radio" name="rtoggle" value="mapbox://styles/mapbox/outdoors-v11">
                            <label for="outdoors-v11">outdoors</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-3 d-flex flex-column">
                <div class="">
                    <h6>Legenda</h6>
                    <ul class="list-unstyled bg-gray-200 p-2">
                        <li v-for="(filter,index) in legend" class="p--2 small mb--5" :key="index">
                            <div class="row">
                                <template v-if="index==0">
                                    <span class="col-12 text-right">
                                        minore di <b>@{{valueFormat(legend[index+1])}}</b>
                                        <span class="pl--2">
                                            <span class="w--15 h--15 d-inline-block" :style="'background-color:'+gMap.layoutProperties[index].bgColor"></span>
{{--                                            <i class="fi fi-shape-abstract-dots" :style="'color:'+gMap.layoutProperties[index].bgColor"></i>--}}
                                            {{--                                            <i class="fa fa-square border-black" :style="'color:'+gMap.layoutProperties[index].bgColor"></i>--}}
                                        </span>
                                    </span>
                                </template>
                                <template v-if="index>0 && index<3">
                                    <span class="col-12 text-right" >

                                    <span class="pb-1">
                                    da <b>@{{valueFormat(filter)}}</b>
                                        a <b>@{{valueFormat(legend[index+1])}}</b></span>
                                        <span class="pl--2">
                                            <span class="w--15 h--15 d-inline-block" :style="'background-color:'+gMap.layoutProperties[index].bgColor"></span>
{{--                                            <i class="fi fi-shape-abstract-dots border-black" :style="'color:'+gMap.layoutProperties[index].bgColor"></i>--}}
                                        </span>
                                    </span>
                                </template>
                                <template v-if="index==3">
                                    <span class="col-12 text-right" >


                                    maggiore di <b>@{{valueFormat(filter)}}</b>
                                        <span class="pl--2">
                                            <span class="w--15 h--15 d-inline-block" :style="'background-color:'+gMap.layoutProperties[index].bgColor"></span>

{{--                                            <i class="fi fi-shape-abstract-dots" :style="'color:'+gMap.layoutProperties[index].bgColor"></i>--}}
                                        </span>
                                </span>
                                </template>

                            </div>


                        </li>
                    </ul>

                    <div class="" v-if="Object.keys(seriesContext).length > 0">
                        <h6>Mostra</h6>
                        <ul class="list-group rounded overflow-auto">

                            <li v-for="(serie,serieName) in seriesContext" class="list-group-item pt-3 pb-3" :key="serieName">
                                <h6>@{{serieName}}</h6>
                                <div class="d-flex" v-for="(serieLabel,serieValue) in serie.domainValues">

                                    <div class="badge badge-success badge-soft badge-ico-sm rounded-circle float-start"></div>

                                    <label class="form-radio form-radio-success">
                                        <input type="radio" :serie-name="serieName" :value="serieValue" v-model="serie.value"  v-on:change="changeMisura($event)">
                                        <i></i> <img src="">
                                    </label>


                                    <div class="pl--12">
                                        <p class="text-dark font-weight-medium m-0">
                                            @{{serieLabel}}
                                        </p>
                                    </div>

                                </div>
                            </li>

                        </ul>
                        <!-- /Aero List -->

                    </div>

{{--                    <h6>Mostra:</h6>--}}
{{--                    <ul class="list-group rounded overflow-auto">--}}

{{--                        <li v-for="(serieName,index) in series" class="list-group-item p--0" :key="index">--}}
{{--                            <div class="d-flex">--}}

{{--                                --}}{{--                                            <div class="badge badge-success badge-soft badge-ico-sm rounded-circle float-start"></div>--}}
{{--                                --}}{{--                                                <i class="fi fi-check"></i>--}}
{{--                                <label class="form-radio form-radio-success">--}}
{{--                                    <input type="radio" name="serie" :value="serieName" v-model="serie"  v-on:change="changeMisura($event)">--}}
{{--                                    <i></i> <img src="">--}}
{{--                                </label>--}}


{{--                                <div class="pl--12">--}}
{{--                                    <p class="text-dark font-weight-medium m-0">--}}
{{--                                        @{{serieName}}--}}
{{--                                    </p>--}}
{{--                                </div>--}}

{{--                            </div>--}}
{{--                        </li>--}}

{{--                    </ul>--}}
                    <!-- /Aero List -->

                </div>

{{--                <div class="flex-grow-1"></div>--}}
{{--                <div class="pb-4 mt--10">--}}
{{--                    <label class="form-switch form-switch-pill form-switch-danger d-block">--}}
{{--                        <input type="checkbox" value="1" v-on:change="toggleLayer('zone')">--}}
{{--                        <i data-on="&#10004;" data-off="&#10005;"></i>--}}
{{--                        <span>Mostre tutte le zone interdette</span>--}}
{{--                    </label>--}}
{{--                </div>--}}
            </div>

            <div class="flex-grow-1">

            </div>

        </div>
    </div>
</template>

<script>
    function vueMapInit(container,data) {
        //var c = new Vue.options.components['vue-map']();
        var id = 'vue-map-' + Math.floor(Math.random() * 10000);
        //console.log('conatiner',container);//jQuery(container).length,jQuery(container).html())
        jQuery(container).attr('id',id);

        var vMap =new Vue({
            el : '#'+id,
            template : '#vue-map-template',
            mounted() {
                var that = this;
                console.log('tipo di mappa',that.chartType)
                if (that.chartType == 'regioni') {
                    console.log('creo mappa  regioni')
                    that.gMap = Object.create(GestioneMappaRegioni);
                } else if (that.chartType == 'nazioni') {
                    console.log('creo mappa  nazioni')
                    that.gMap = Object.create(GestioneMappaNazioni);
                } else if (that.chartType == 'province') {
                    console.log('creo mappa  province')
                    that.gMap = Object.create(GestioneMappaProvince);
                }
                else
                    that.gMap = Object.create(GestioneMappaComuni);
                that.gMap.accessToken = '{{ env('MAPBOX_KEY') }}';
                that.gMap.id = that.map_id;
                that.gMap.mapStyle = data.conf.mapStyle;
                //that.gMap.zoom = 8;
                // that.gMap.scuole = [];
                // that.gMap.slots = [];
                // that.gMap.tipologie = [];
                // that.gMap.tipologieGioco = [];
                that.gMap.opacity = .8;
                // that.gMap.center = [
                //     14,
                //     42.29];
                that.loading = false;
                // lo faccio per poter agganciare l'evento e devo essere sicuro che html e' stato disegnato
                setTimeout(function () {
                    jQuery(that.$el).find('[map-style][value="' + that.gMap.mapStyle + '"]').prop('checked',true);
                    console.log('found default check',jQuery(that.$el).find('[map-style][val="' + that.gMap.mapStyle + '"]').length,that.gMap.mapStyle)
                    jQuery(that.$el).find('[map-style]').click(function () {
                        jQuery('body').addClass('loading');
                        console.log('map style',jQuery(this).val());
                        var style = jQuery(this).val();
                        console.log('style',style)
                        that.gMap.mapStyle = style;
                        that.gMap.zoom = that.gMap.map.getZoom();
                        that.gMap.center = that.gMap.map.getCenter();
                        that.gMap.showMap();
                        that.gMap.map.on('load', () => {
                            that.load();
                        })
                        //that.load();
                    })
                    jQuery('body').addClass('loading');
                    that.gMap.showMap();
                    that.gMap.map.on('load', () => {
                        that.load();
                        if (data.showTitle) {
                            jQuery('#'+that.title_id).removeClass('d-none');
                        }
                    })
                },100)

            },
            data() {
                console.log('dati esterni',data);
                var d = {}
                for (var k in data) {
                    d[k] = data[k];
                }
                var defaultData =  {
                    loading: true,
                    type : 'map',
                    comuni: [],
                    filters : data.filters || {},
                    description:'',
                    json : {},
                    name : null,
                    map_id : id + '_map',
                    title_id : 'id' + '_title',
                    gMap : null,
                    legend : [],
                    context : {},
                    seriesContext:{},
                    primaVolta : true,
                    //series : [],
                    serie : null,
                    labelValore : 'Numero',
                    selectedSerie : null,
                }
                var mergedData = Object.assign(defaultData,d);
                console.log('mergedData',mergedData)
                return mergedData;
                //return Object.assign(d,defaultData);
            },
            methods : {
                imageData() {
                    var dpi = 300;
                    Object.defineProperty(window, 'devicePixelRatio', {
                        get: function() {return dpi / 96}
                    });

                    return this.gMap.map.getCanvas().toDataURL();
                },
                toggleLayer(name) {
                    this.gMap.toggleLayer(name)
                },
                toggleComune(comune) {
                    this.gMap.toggleComune(comune)
                },
                changeMisura(event) {

                    var that = this;
                    jQuery('body').addClass('loading');
                    var target = event.target;
                    var serieName = jQuery(event.target).attr('serie-name');
                    console.log('serie name',serieName,target.value);
                    //that.context[target.name] = target.value;
                    that.series[serieName] = target.value;
                    that.load();


                    // console.log('eventgggg',event.target.value);
                    // that.legend = that.json.result.range[event.target.value];
                    // console.log('legendaaaa',that.legend);
                    // that.gMap.rimuoviDistribuzione(that.selectedSerie);
                    // that.gMap.caricaDistribuzione(that.resourceId,that.json.result,event.target.value);
                    // that.selectedSerie = event.target.value;
                    // //that.legend = Object.keys(that.gMap.layoutProperties);

                    return ;
                },
                reloadMap() {
                    var that = this;
                    that.gMap.init(function () {
                        console.log('contesto json',that.json.result.context);

                        //that.comuni = that.gMap.comuni;
                        that.gMap.range = that.json.result.range;
                        //that.context = that.json.result.context;

                        that.name = that.json.result.description;
                        console.log('json',that.name,that.json);
                        that.gMap.caricaDistribuzione(that.resourceId,that.json.result);
                        that.legend = that.gMap.range[Object.keys(that.gMap.range)[0]]; //Object.keys(that.gMap.layoutProperties);
                        console.log('legeng',that.legend);
                        jQuery('body').removeClass('loading');
                    });
                },
                load() {
                    var that = this;

                    // var contextValue = {};
                    // for (var k in that.context) {
                    //     contextValue[k] = jQuery('select[name="'+k+'"]').val();
                    // }
                    //console.log('context',contextValue);
                    console.log('load distribuzione filters',that.filters,'series',that.series);
                    jQuery.get('/distribuzione/'+that.resourceId+'/'+that.resourceType,{filters : that.filters,series:that.series},function (json) {
                        console.log('distribuzione json',json);
                        if (json.error) {
                            console.error('errore',json.msg);
                            return ;
                        }
                        that.json = json;
                        if (that.primaVolta) {
                            //jQuery.extend( true,that.context , (that.json.result.context || {}),true);
                            that.description = ('titolo' in that)?that.titolo:that.json.result.description;
                            // if (that.titolo)
                            // that.description = that.json.result.description;
                            that.context = that.json.result.context || {};
                            that.seriesContext =  json.result.seriesContext || {};
                            //that.series = Object.keys(that.json.result.values);
                            //that.serie = that.series[0];
                            that.gMap.colors = schema_colori[that.schemaColor] || schema_colori['default'];
                            that.primaVolta = false;
                            //TODO da migliorare
                            setTimeout(function () {
                                var ctx = that.context;
                                for (var k in ctx) {
                                    console.log('setto selecte',k,ctx[k].value);
                                    jQuery('[name="' + k +'"').val(ctx[k].value);
                                }
                            },500)

                        }
                        that.reloadMap();
                    }).fail(function (e) {
                        console.error(e);
                    })
                },
                changeContext(event) {
                    var that = this;
                    var target = event.target;
                    console.log('name',target.name,target.value);
                    //that.context[target.name] = target.value;
                    that.filters[target.name] = target.value;
                    jQuery('body').addClass('loading');
                    that.load();
                },
                valueFormat(value) {
                    return this.gMap.valueFormat(value);
                }
            }
        })
        return vMap
    }
</script>
