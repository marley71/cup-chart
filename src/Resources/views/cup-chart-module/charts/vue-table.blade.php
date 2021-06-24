<template id="vue-table-template">
    <div class="container d-flex flex-column min-h-75vh">
        <hr class="w-100 mb--20"/>
        <div class="row">

            <div class="col-12 col-lg-12 col-sm-12 mb-3">
                <div class="row" v-if="Object.keys(context).length > 0">
                    <div class="col-6" v-for="(ctx,key) in context">
                        <div>@{{key}}</div>
                        <select class="form-control" :name="key" v-on:change="changeContext($event)">
                            <option v-for="(label,keyLabel) in ctx.domainValues" :value="keyLabel">@{{label}}</option>
                        </select>
                    </div>

                </div>
                <div class="row">
                    <div class="col-12 bg-white shadow-primary-xs rounded p-2">
                        <div :id="chart_id" class="w-100 rounded p--10">

                            <table class=" rounded w-100">
                            </table>
                        </div>

                    </div>
                </div>
            </div>
{{--            <div class="col-2 col-lg-2 col-sm-12 d-flex flex-column">--}}
{{--                <div class="" v-if="Object.keys(seriesContext).length > 0">--}}
{{--                    <h6>Mostra</h6>--}}
{{--                    <ul class="list-group rounded overflow-auto">--}}
{{--                        <li v-for="(serie,serieName) in seriesContext" class="list-group-item pt-3 pb-3" :key="serieName">--}}
{{--                            <div>@{{serieName}}</div>--}}
{{--                            <div class="d-flex" v-for="(serieLabel,serieValue) in serie.domainValues">--}}

{{--                                <div class="badge badge-success badge-soft badge-ico-sm rounded-circle float-start"></div>--}}
{{--                                    <i class="fi fi-check"></i>--}}
{{--                                <label class="form-radio form-radio-success">--}}
{{--                                    <input type="radio" :name="serieName" :value="serieValue" v-model="serie.value"  v-on:change="changeMisura($event)">--}}
{{--                                    <i></i> <img src="">--}}
{{--                                </label>--}}


{{--                                <div class="pl--12">--}}
{{--                                    <p class="text-dark font-weight-medium m-0">--}}
{{--                                        @{{serieLabel}}--}}
{{--                                    </p>--}}
{{--                                </div>--}}

{{--                            </div>--}}
{{--                        </li>--}}

{{--                    </ul>--}}
{{--                    <!-- /Aero List -->--}}

{{--                </div>--}}
{{--            </div>--}}

            <div class="flex-grow-1">

            </div>
        </div>
    </div>
</template>

<script>
    function vueTableInit(container,data) {
        console.log('vueTableInit',data);
        //var c = new Vue.options.components['vue-map']();
        var id = 'vue-chart-' + Math.floor(Math.random() * 10000);
        //console.log('conatiner',container);//jQuery(container).length,jQuery(container).html())
        jQuery(container).attr('id',id)
        var vTable = new Vue({
            el : '#'+id,
            template : '#vue-table-template',
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
                    type : 'table',
                    filters : data.filters || {},
                    json : {},
                    name : null,
                    chart_id : id + '_chart',
                    colors : {},
                    context : {},
                    seriesContext : {},
                    primaVolta : true,
                }
                return Object.assign(d,defaultData);
            },
            methods : {
                imageData() {
                    return null; //this.gMap.map.getCanvas().toDataURL();
                },
                showTable() {
                    var that = this;
                    var json = that.json;
                    //console.log(that.chart_id,'JSON CHART',json);
                    var graphicData = [];
                    var values = json.result.values;
                    var keys = Object.keys(values);
                    for (var i in keys) {
                        //console.log('i',i)
                        var rowCount = 0;
                        for (var k in values[ keys[i] ]) {
                            //console.log('k',k,values[ keys[i] ])
                            if (!graphicData[rowCount]) {
                                graphicData.push(that._getLeftValues(k))
                            }
                            graphicData[rowCount].push(parseFloat(parseFloat(values[ keys[i] ][k]['total']).toFixed(2)))
                            rowCount++;
                        }

                    }
                    //console.log('graphicData',graphicData);
                    var columns = [];
                    for (var key in json.result.leftSeries) {
                        columns.push({title:key,orderable: false})
                    }
                    //var columns = [{title:'infered filters'}];
                    for (var  i in keys) {
                        columns.push({
                            title : keys[i],
                            type : 'num',
                            orderable: true
                        })
                        if (keys[i] == 'totale')
                            columns[columns.length-1].orderable = true;
                    }
                    jQuery('#'+that.chart_id).find('table').DataTable( {
                        paging : false,
                        data: graphicData,
                        columns: columns,
                        //scrollY:500,
                        scrollX:true,
                        scrollCollapse: true,
                        info: false,
                        paging:         false,
                        title : ' caption',
                        messageTop : 'top caption',
                        aaSorting : [],
                        //fixedHeader:true,
                        fixedColumns:   {
                            leftColumns: 1,
                        },
                        //pageLength : 50,
                        // "formatNumber": function ( toFormat ) {
                        //     return toFormat.toString().replace(
                        //         /\B(?=(\d{3})+(?!\d))/g, "'"
                        //     );
                        // }
                    } );
                    //jQuery('caption').html(that.json.result.description);
                    return ;
                },
                load() {
                    var that = this;
                    //
                    // var contextValue = {};
                    // for (var k in that.context) {
                    //     contextValue[k] = jQuery('select[name="'+k+'"]').val();
                    // }

                    console.log('filters',that.filters,'series',that.series);
                    jQuery.get('/distribuzione/'+that.resourceId+'/'+that.resourceType,{filters : that.filters,series:that.series},function (json) {
                        if (json.error) {
                            console.log('errore',json.message);
                            return ;
                        }
                        that.json = json;
                        if (that.primaVolta) {
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
                        that.showTable();
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
                    console.log('serie name',target.name,target.value);
                    //that.context[target.name] = target.value;
                    that.series[target.name] = target.value;
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
                    if (Object.keys(that.json.result.leftSeries).length > 1) {
                        return compactValue.split(that.json.result.separatoreLeft);
                    }
                    return [compactValue]
                }
            }
        })
        return vTable;
    }
</script>
