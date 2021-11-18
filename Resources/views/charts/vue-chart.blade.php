<template id="vue-chart-template">
    <div class="container d-flex flex-column min-h-75vh">
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
                    <div class="col-12 bg-white shadow-primary-xs rounded p-2 ">

                        <div :id="chart_id" class="h--500 w-100 rounded">

                        </div>

                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-3 d-flex flex-column">
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
</template>

<script>
    function vueChartInit(container,data) {
        console.log('vueChartInit',data);
        //var c = new Vue.options.components['vue-map']();
        var id = 'vue-chart-' + Math.floor(Math.random() * 10000);
        //console.log('conatiner',container);//jQuery(container).length,jQuery(container).html())
        jQuery(container).attr('id',id)
        var vChart = new Vue({
            el : '#'+id,
            template : '#vue-chart-template',
            mounted() {
                var that = this;
                that.load();
            },
            data() {
                var d = {}
                for (var k in data) {
                    d[k] = data[k];
                }
                var defaultData = {
                    type : 'chart',
                    comuni: [],
                    description:'',
                    filters : data.filters || {},
                    json : {},
                    name : null,
                    chart_id : id + '_chart',
                    colors : {},
                    context : {},
                    seriesContext : {},
                    primaVolta : true,
                }
                var mergedData = Object.assign(defaultData,d);
                console.log('mergedData',mergedData)
                return mergedData;
            },
            methods : {
                imageData() {
                    return this.chart.getImageURI();
                },
                showChart() {
                    var that = this;
                    var json = that.json;
                    console.log('JSON CHART',json);
                    var graphicData = [];
                    var keys = Object.keys(json.result.values);
                    var title = ('titolo' in that)?that.titolo:json.result.description;
                    graphicData.push([''].concat(keys));
                    var keyValues = Object.keys(json.result.values[keys[0]]);
                    for (var kv=0;kv<keyValues.length;kv++) {
                        graphicData.push(that._getLeftValues(keyValues[kv]))
                        //graphicData.push([keyValues[kv]]);
                        for (var k in json.result.values) {
                            graphicData[kv+1].push(parseFloat(json.result.values[k][keyValues[kv]].total));
                        }
                    }

                    var height = jQuery('#'+that.chart_id).css('height');

                    function __drawVisualization() {
                        // Some raw data (not necessarily accurate)

                        var leftSeries = json.result.leftSeries;
                        var leftKeys = Object.keys(leftSeries);
                        var topSeries = json.result.topSeries;
                        console.log(topSeries,leftSeries,'graphicData',graphicData)
                        var data = google.visualization.arrayToDataTable(graphicData);



                        // var options = {
                        //     title: title,
                        //     hAxis: {title: hAxis},
                        //     vAxis: {title: vAxis},
                        //     legend: 'none',
                        //     trendlines: { 0: {} },    // Draw a trendline for data series 0.
                        //     height:height
                        // };



                        var domContainer = jQuery('#'+that.chart_id)[0];
                        //that.jQe(that.container).css('width',w).css('height',h);
                        var googleChartType = {
                            'chart-o':'BarChart',
                            'chart' : 'ComboChart',
                            'line' : 'ComboChart'
                        }
                        var chartType = that.chartType; //that.gChartType || 'ComboChart';
                        var vAxis = json.result.extra.tipo_valore;
                        var hAxis = leftKeys[0] ;
                        var seriesType = 'bars';

                        switch (chartType) {
                            case 'chart-o':
                                var tmp = vAxis;
                                vAxis = hAxis;
                                hAxis = tmp
                                break;
                            case 'line':
                                seriesType = 'line'
                                break;
                        }
                        console.log('google chart type ',googleChartType[chartType],seriesType)
                        console.log('hAixs',hAxis,'vAx',vAxis);
                        var options = {
                            title: title,
                            vAxis: {
                                title: vAxis,
                                minValue : (json.result.min+"")?json.result.min:0
                            },
                            hAxis: {
                                title: hAxis
                            },
                            seriesType: seriesType,
                            //curveType : 'function',
                            //series: {5: {type: 'bar'}},
                            //width : w,
                            height : height,
                            colors : schema_colori[that.schemaColor] || schema_colori['default']

                        };

                        if (json.result.max) {
                            options.vAxis.maxValue = json.result.max
                        }
                        console.log('OPTIONS',options);
                        var chart = new google.visualization[googleChartType[chartType]](domContainer);

                        that.chart = chart;


                        switch (json.result.extra.tipo_valore) {
                            case 'percentuale':
                            case 'valore':
                                var pattern = '0.0';
                                if (json.result.extra.decimali === 0) {
                                    pattern = '0'
                                }
                                var formatter = new google.visualization.NumberFormat({
                                    pattern: pattern,
                                    suffix: json.result.extra.suffisso,
                                    prefix : json.result.extra.prefisso,
                                });

                                // format number columns
                                for (var i = 1; i < data.getNumberOfColumns(); i++) {
                                    formatter.format(data, i);
                                }
                        }

                        chart.draw(data, options);

                        that.imageLink = chart.getImageURI();
                    }


                    google.charts.load('current', {'packages':['corechart']});
                    google.charts.setOnLoadCallback(__drawVisualization);
                },
                load() {
                    var that = this;
                    //
                    // var contextValue = {};
                    // for (var k in that.context) {
                    //     contextValue[k] = jQuery('select[name="'+k+'"]').val();
                    // }

                    console.log('load distribuzione filters',that.filters,'series',that.series);
                    jQuery.get('/distribuzione/'+that.resourceId+'/'+that.resourceType,{filters : that.filters,series:that.series},function (json) {
                        if (json.error) {
                            console.error('errore',json.msg);
                            return ;
                        }
                        that.json = json;
                        if (that.primaVolta) {
                            that.description = that.titolo?that.titolo:that.json.result.description;
                            //jQuery.extend( true,that.context , (that.json.result.context || {}),true);
                            that.context = that.json.result.context || {};
                            that.seriesContext =  json.result.seriesContext || {}; //Object.keys(that.json.result.values);
                            //that.series = Object.keys(that.json.result.values);
                            //that.serie = that.series[0];
                            //that.gMap.colors = schema_colori[that.schemaColor];
                            that.primaVolta = false;
                            //TODO da migliorare
                            setTimeout(function () {
                                var ctx = that.context;
                                for (var k in ctx) {
                                    jQuery('[name="' + k +'"').val(ctx[k].value);
                                }
                            },500)

                        }
                        that.showChart();
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
                    that.load();
                },
                changeMisura(event) {
                    var that = this;
                    var target = event.target;
                    var serieName = jQuery(event.target).attr('serie-name');
                    console.log('serie name',serieName,target.value);
                    //that.context[target.name] = target.value;
                    that.series[serieName] = target.value;
                    that.load();
                },
                /**
                 * in caso la tabella excel ha piu' di una serie left va splittato il valore usando il separatoreLeft
                 * @param compactValue
                 * @return {*[]|*}
                 * @private
                 */
                _getLeftValues(compactValue) {
                    var that = this;
                    var tmp= compactValue.split(that.json.result.separatoreLeft);
                    var val = "";
                    for (var i in tmp) {
                        val += tmp[i] + ' ';
                    }
                    return [val];
                },
                getSelectedSeries() {
                    var that = this;
                    // nei casi iniziali i valori presenti nelle serie potrebbero non corrispondere a quelli che si vedono in video
                    var s = that.series || {};
                    for (var k in that.series) {
                        var value = that.series[k];
                        if (value.substring(0,1) == '*' || value.substring(0,1) == '?') {
                            s[k] = jQuery(that.$el).find('input[serie-name="' + k +'"]:checked').val()
                        } else {
                            s[k] = value;
                        }
                    }
                    return s;
                }
            }
        })
        return vChart;
    }
</script>
