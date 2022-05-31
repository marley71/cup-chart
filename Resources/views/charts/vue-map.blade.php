<template id="vue-map-template">
    <div v-if="!loading" class="container-fluid d-flex flex-column min-h-75vh">
        <hr class="w-100 mb--20"/>
        <div class="row">
{{--            <div class="col-12">--}}
{{--                <h4>@{{ description }}</h4>--}}
{{--            </div>--}}

            <div class="col-12 col-lg-9 mb-3">
                <div class="row border border-primary mb-2" v-if="Object.keys(filtersContext).length > 0">
                    <div class="col col-12 text-center">
                        <h6 class="my-2 font-weight-bold">Filtri (Asse x)</h6>
                    </div>
                    <div class="col col-12 col-lg-6 mb-2" v-for="(ctx,key) in filtersContext">
                        <div class="text-center font-weight-medium">@{{key}}</div>
                        <select class="form-control" :name="key" v-model="filters[key]" v-on:change="changeMisura($event)"
                                filtro-type="left" :multiple="isMultidimensionale('left',key)">
                            <option v-for="(label,keyLabel) in ctx.domainValues" :value="keyLabel">@{{label}}</option>
                        </select>
                    </div>

                </div>
                <div class="row">
                    <div class="col-12 bg-white shadow-primary-xs rounded p-2">
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
                    <div class="col-12 pt--2" v-for="nota in getNote()">
                        <i>@{{nota}}</i>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-3 d-flex flex-column">
                <div class="border border-success">
                    <h6 class="text-center my-2 font-weight-bold">Legenda</h6>
                    <ul class="list-group overflow-none border-none dpa-chart">
                        <li class="list-group-item pt-3 pb-4" >
                            <div class="d-flex"  v-for="(filter,index) in legend" :key="index">
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
                </div>
                <div class="border border-success mt--3" v-if="Object.keys(seriesContext).length > 0">
                    <h6 class="text-center my-2 font-weight-bold">Serie visualizzate</h6>
                    <ul class="list-group overflow-auto border-none dpa-chart">

                        <li v-for="(serieContext,serieName) in seriesContext" class="list-group-item pt-3 pb-3" :key="serieName">
                            <h6 class="text-center mb-2 pb-1 border-bottom">@{{serieName}}</h6>
                            <template v-if="isMultidimensionale('top',serieName)">
                                <div class="d-flex" v-for="(serieLabel,serieValue) in serieContext.domainValues">

                                    <div class="badge badge-success badge-soft badge-ico-sm rounded-circle float-start"></div>

                                    <label class="form-checkbox form-checkbox-success">
                                        <input type="checkbox" :name="serieName"  :value="serieValue"  v-on:change="changeMisura($event)" v-model="series[serieName]" filtro-type="top">
                                        <i></i> <img src="">
                                    </label>


                                    <div class="pl--12">
                                        <p class="text-dark font-weight-medium m-0">
                                            @{{serieLabel}}
                                        </p>
                                    </div>

                                </div>
                            </template>
                            <template v-else>
                                <div class="d-flex" v-for="(serieLabel,serieValue) in serieContext.domainValues">

                                    <div class="badge badge-success badge-soft badge-ico-sm rounded-circle float-start"></div>

                                    <label class="form-radio form-radio-success">
                                        <input type="radio" :serie-name="serieName" :value="serieValue" v-model="series[serieName]"  v-on:change="changeMisura($event)" :filtro-type="top">
                                        <i></i> <img src="">
                                    </label>


                                    <div class="pl--12">
                                        <p class="text-dark font-weight-medium m-0">
                                            @{{serieLabel}}
                                        </p>
                                    </div>

                                </div>
                            </template>

                        </li>

                    </ul>
                    <!-- /Aero List -->

                </div>
                <div class="border border-success mt--3" v-else-if="Object.keys(series).length > 0">
                    <h6 class="text-center my-2 font-weight-bold">Serie visualizzate</h6>
                    <ul class="list-group overflow-auto border-none dpa-chart">

                        <li v-for="(serieContext,serieName) in series" class="list-group-item pt-3 pb-3" :key="serieName">
                            <h6 class="text-center mb-2 pb-1 border-bottom">@{{serieName}}</h6>
                            <div>@{{serieContext}}</div>
                        </li>

                    </ul>
                </div>
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
            mixins: [GraficiMixin],
            mounted() {
                var that = this;
                console.log('tipo di mappa',that.chartType,data)
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
                        //if (data.showTitle) {
                            jQuery('#'+that.title_id).removeClass('d-none');
                        //}
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
                    filters : {},
                    series: {},
                    description:'',
                    json : {},
                    name : null,
                    map_id : id + '_map',
                    title_id : 'id' + '_title',
                    gMap : null,
                    legend : [],
                    filtersContext : {},
                    seriesContext : {},
                    primaVolta : true,
                    serie : null,
                    labelValore : 'valore',
                    selectedSerie : null,
                    cardinalitaSerie:{},
                    cardinalitaFiltri:{},
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
                showChart() {
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
                        that.enableMultiSelect();
                    });
                },
                valueFormat(value) {
                    return this.gMap.valueFormat(value);
                },

                initObjectData() {
                    var that = this;
                    var json = that.json;
                    that.description = that.titolo?that.titolo:json.result.description;
                    that.filtersContext = json.result.filtersContext || {};
                    that.seriesContext = json.result.seriesContext || {}; //Object.keys(that.json.result.values);
                    for (var k in that.seriesContext) {
                        that.cardinalitaSerie[k] = that.seriesContext[k].cardinalita;
                    }
                    for (var k in that.filtersContext) {
                        that.cardinalitaFiltri[k] = that.filtersContext[k].cardinalita;
                    }
                    that.series = {};
                    // topContext e leftContext sono i filtri che mi arrivano dall'attributo del div
                    for (var k in that.topContext) {
                        k = k.toLowerCase();
                        console.log('k',k)
                        if (that.isMultidimensionale('top',k))
                            that.series[k] = json.result.currentSeries[k]
                        else
                            that.series[k] = json.result.currentSeries[k][0]
                    }
                    for (var k in that.leftContext) {
                        k = k.toLowerCase();
                        console.log('k',k)
                        if (that.isMultidimensionale('left',k))
                            that.filters[k] = json.result.currentFilters[k]
                        else
                            that.filters[k] = json.result.currentFilters[k][0]
                    }

                    that.gMap.colors = schema_colori[that.schemaColor] || schema_colori['default'];
                    that.gMap.labelValore = that.json.result.extra.tipo_valore
                    that.description = ('titolo' in that)?that.titolo:that.json.result.description;

                    that.primaVolta = false;



                    // vecchio codice
                    // that.context = that.json.result.context || {};
                    // that.seriesContext =  json.result.seriesContext || {};

                    // that.primaVolta = false;
                    // //TODO da migliorare
                    // setTimeout(function () {
                    //     var ctx = that.context;
                    //     for (var k in ctx) {
                    //         console.log('setto selecte',k,ctx[k].value);
                    //         jQuery('[name="' + k +'"').val(ctx[k].value);
                    //     }
                    // },500)
                }
            }
        })
        return vMap
    }
</script>
