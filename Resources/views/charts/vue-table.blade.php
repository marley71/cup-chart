<template id="vue-table-template">
    <div v-if="!loading" class="container-fluid d-flex flex-column ">
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
                        <h6 class="m--3 p--3 text-center w-100">@{{description}}</h6>
                        <div :id="chart_id" class="w-100 rounded p--10">

                            <table class=" rounded w-100">
                            </table>
                        </div>
                    </div>
                    <div class="col-12 pt--2" v-for="nota in getNote()">
                        <i>@{{nota}}</i>
                    </div>
                </div>
            </div>
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
        var vChart = new Vue({
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
                    loading:true,
                    comuni: [],
                    description:'',
                    filters : data.filters || {},
                    json : {},
                    name : null,
                    chart_id : id + '_table',
                    colors : {},
                    context : {},
                    seriesContext : {},
                    primaVolta : true,
                    table:null,
                }
                var mergedData = Object.assign(defaultData,d);
                console.log('mergedData',mergedData)
                return mergedData;
            },
            methods : {
                showChart() {
                    var that = this;
                    var json = that.json;
                    console.log('JSON CHART',that.chart_id);

                    function __drawVisualization() {
                        // Some raw data (not necessarily accurate)



                        var leftSeries = json.result.leftSeries;
                        var leftKeys = Object.keys(leftSeries);
                        var topSeries = json.result.topSeries;
                        //console.log(topSeries,leftSeries,'graphicData',graphicData)
                        var data = new google.visualization.DataTable();
                        var values = json.result.values;
                        var keys = Object.keys(values);
                        var extra = json.result.extra;

                        for (var left in leftSeries) {
                            data.addColumn('string',left);
                        }

                        let tableType = extra.tipo == 'string'?'string':'number';
                        console.log('tableType',tableType);
                        for (var i=0;i<keys.length;i++) {
                            var columName = keys[i].split(that.separatoreTop).join('<br>');
                            data.addColumn(tableType,columName);
                        }
                        var rows = [];
                        for (var k1 in values[keys[0]]) {
                            rows.push([k1]);
                        }
                        var rowKeys = Object.keys(values[keys[0]]);
                        //console.log('keys',keys,'rowKeys',rowKeys);
                        for (var j=0;j<rowKeys.length;j++) {
                            for (var col in keys) {
                                let v = tableType=='string'?values[ keys[col] ][ rowKeys[j] ].total+"":values[ keys[col] ][ rowKeys[j] ].total;
                                rows[j].push(v);
                            }

                        }

                        console.log('rows',rows);
                        data.addRows(rows);


                        var domContainer = jQuery('#'+that.chart_id)[0];

                        var options = {
                            allowHtml:true,
                            showRowNumber: false,
                            width: '100%',
                            height: '100%',
                            cssClassNames : {
                                headerRow: 'bigAndBoldClass',
                                hoverTableRow: 'highlightClass'
                            }
                        };
                        console.log('OPTIONS',options);
                        var table = new google.visualization.Table(domContainer);

                        that.table = table;


                        switch (json.result.extra.tipo_valore) {
                            // case 'percentuale':
                            // case 'valore':
                            default:

                                var pattern = '0.';


                                if (extra.tipo != 'string') {

                                    if (extra.tipo_valore.toLowerCase() == 'percentuale') {
                                        pattern = pattern.padEnd(2 + extra.decimali, '0');
                                    } else {
                                        if (extra.tipo == 'integer' || extra.decimali === 0) {
                                            pattern = '0'
                                        } else {
                                            pattern = pattern.padEnd(2 + extra.decimali, '0');
                                        }
                                    }
                                    console.log('EXTRA', json.result.extra, pattern);
                                    //alert(pattern + json.result.extra.decimali);
                                    var formatter = new google.visualization.NumberFormat({
                                        pattern: pattern,
                                        suffix: extra.suffisso,
                                        prefix: extra.prefisso,
                                    });

                                    // format number columns
                                    for (var i = 1; i < data.getNumberOfColumns(); i++) {
                                        formatter.format(data, i);
                                    }
                                }
                        }

                        table.draw(data, options);

                    }


                    google.charts.load('current', {'packages':['table']});
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
                            that.separatoreLeft = json.result.separatoreLeft;
                            that.separatoreTop = json.result.separatoreTop;
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
                        that.loading = false;
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
                },
                getNote() {
                    if (this.json.result) {
                        return this.json.result.extra.note;
                    }
                    return [];
                },
                imageData() {
                    return null;
                },
            }
        })
        return vChart;
    }




    // function vueTableInit(container,data) {
    //     console.log('vueTableInit',data);
    //     //var c = new Vue.options.components['vue-map']();
    //     var id = 'vue-chart-' + Math.floor(Math.random() * 10000);
    //     //console.log('conatiner',container);//jQuery(container).length,jQuery(container).html())
    //     jQuery(container).attr('id',id)
    //     var vTable = new Vue({
    //         el : '#'+id,
    //         template : '#vue-table-template',
    //         mounted() {
    //             var that = this;
    //             that.load(function () {
    //                 that.loading = false;
    //                 setTimeout(function () {
    //                     that.showTable();
    //                     var jTable = jQuery('#'+that.chart_id).find('table');
    //                     console.log('jTable','#'+id,jTable.length);
    //                     // aggiungo padding alle colonne dei metadati
    //
    //                     var cols = Object.keys(that.json.result.leftSeries).length;
    //                     for (var c=1;c<=cols;c++) {
    //                         //console.log('c',c,jTable.find('td:nth-child(' + c +')').length);
    //                         jTable.find('td:nth-child(' + c +')').css('padding-right','15px');
    //                         jTable.find('th:nth-child(' + c +')').css('padding-right','15px');
    //                         //jTable.find('td:nth-child(' + c +')').css('border-right','1px solid #000');
    //                     }
    //
    //
    //                     // jTable.find('td').css('border-right','1px solid #000')
    //                     // jTable.find('td').css('padding-right','4px')
    //                 },200)
    //
    //             });
    //         },
    //         data() {
    //             var d = {}
    //             for (var k in data) {
    //                 d[k] = data[k];
    //             }
    //             var defaultData = {
    //                 type : 'table',
    //                 filters : data.filters || {},
    //                 json : {},
    //                 name : null,
    //                 chart_id : id + '_chart',
    //                 colors : {},
    //                 context : {},
    //                 seriesContext : {},
    //                 primaVolta : true,
    //                 loading:true,
    //             }
    //             var mergedData = Object.assign(defaultData,d);
    //             console.log('mergedData',mergedData)
    //             return mergedData;
    //         },
    //         methods : {
    //             getNote() {
    //                 if (this.json.result) {
    //                     return this.json.result.extra.note;
    //                 }
    //                 return [];
    //             },
    //             imageData() {
    //                 return null; //this.gMap.map.getCanvas().toDataURL();
    //             },
    //             showTable() {
    //                 var that = this;
    //                 var json = that.json;
    //                 //console.log(that.chart_id,'JSON CHART',json);
    //                 var graphicData = [];
    //                 var values = json.result.values;
    //                 var keys = Object.keys(values);
    //                 for (var i in keys) {
    //                     //console.log('i',i)
    //                     var rowCount = 0;
    //                     for (var k in values[ keys[i] ]) {
    //                         var val = values[ keys[i] ][k]['total'];
    //                         //console.log('k',k,values[ keys[i] ])
    //                         if (!graphicData[rowCount]) {
    //                             //console.log('getleftvalues',that._getLeftValues(k))
    //                             graphicData.push(that._getLeftValues(k))
    //                         }
    //                         var extra = json.result.extra;
    //
    //
    //                         if (extra.tipo_valore == 'percentuale') {
    //                             val = val.toFixed(extra.decimali);
    //                             graphicData[rowCount].push(val);
    //                         } else {
    //                             if (json.result.extra.tipo === 'integer') {
    //                                 graphicData[rowCount].push(parseInt(val));
    //                             } else {
    //                                 //val = parseFloat(val.toFixed(json.result.extra.decimali) );
    //                                 val = val.toFixed(json.result.extra.decimali);
    //                                 //console.log('val',val,json.result.extra.decimali)
    //                                 graphicData[rowCount].push(val);
    //
    //                             }
    //                         }
    //
    //
    //                         //graphicData[rowCount].push(parseFloat(parseFloat(values[ keys[i] ][k]['total']).toFixed(2)))
    //                         rowCount++;
    //                     }
    //
    //                 }
    //                 //console.log('graphicData',graphicData);
    //                 var columns = [];
    //                 for (var key in json.result.leftSeries) {
    //                     columns.push({
    //                         title:key,
    //                         orderable: false,
    //                         //"width": 500
    //                     })
    //                 }
    //                 //var columns = [{title:'infered filters'}];
    //                 for (var  i in keys) {
    //                     columns.push({
    //                         title : keys[i],
    //                         type : 'num',
    //                         orderable: true,
    //                         //"width": 500
    //                     })
    //                     if (keys[i] == 'totale')
    //                         columns[columns.length-1].orderable = true;
    //                 }
    //
    //                 jQuery('#'+that.chart_id).find('table').DataTable( {
    //                     paging : false,
    //                     data: graphicData,
    //                     columns: columns,
    //                     //scrollY:500,
    //                     scrollX:true,
    //                     scrollCollapse: true,
    //                     info: false,
    //                     paging:         false,
    //                     title : ' caption',
    //                     messageTop : 'top caption',
    //                     aaSorting : [],
    //                     //fixedHeader:true,
    //                     fixedColumns:   {
    //                         leftColumns: 1,
    //                     },
    //                     //pageLength : 50,
    //                     // "formatNumber": function ( toFormat ) {
    //                     //     return toFormat.toString().replace(
    //                     //         /\B(?=(\d{3})+(?!\d))/g, "'"
    //                     //     );
    //                     // }
    //                 } );
    //                 //jQuery('caption').html(that.json.result.description);
    //                 return ;
    //             },
    //             load(callback) {
    //                 var that = this;
    //                 //
    //                 // var contextValue = {};
    //                 // for (var k in that.context) {
    //                 //     contextValue[k] = jQuery('select[name="'+k+'"]').val();
    //                 // }
    //
    //                 console.log('filters',that.filters,'series',that.series);
    //                 jQuery.get('/distribuzione/'+that.resourceId+'/'+that.resourceType,{filters : that.filters,series:that.series},function (json) {
    //                     if (json.error) {
    //                         console.error('errore',json.msg);
    //                         return ;
    //                     }
    //                     that.json = json;
    //                     if (that.primaVolta) {
    //                         //jQuery.extend( true,that.context , (that.json.result.context || {}),true);
    //                         that.context = that.json.result.context || {};
    //                         that.seriesContext =  json.result.seriesContext || {}; //Object.keys(that.json.result.values);
    //                         //that.series = Object.keys(that.json.result.values);
    //                         //that.serie = that.series[0];
    //                         //that.gMap.colors = schema_colori[that.schemaColor];
    //                         that.primaVolta = false;
    //                         //TODO da migliorare
    //                         setTimeout(function () {
    //                             var ctx = that.context;
    //                             for (var k in ctx) {
    //                                 jQuery('[name="' + k +'"').val(ctx[k].value);
    //                             }
    //                             return callback();
    //                         },500)
    //
    //                     } else
    //                         return callback();
    //                 }).fail(function (e) {
    //                     console.error(e);
    //                 })
    //             },
    //             changeContext(event) {
    //                 var that = this;
    //                 var target = event.target;
    //                 console.log('name',target.name,target.value);
    //                 //that.context[target.name] = target.value;
    //                 that.filters[target.name] = target.value;
    //                 that.load(function () {
    //                     that.showTable();
    //                 });
    //             },
    //             changeMisura(event) {
    //                 var that = this;
    //                 var target = event.target;
    //                 console.log('serie name',target.name,target.value);
    //                 //that.context[target.name] = target.value;
    //                 that.series[target.name] = target.value;
    //                 that.load(function () {
    //                     that.showTable();
    //                 });
    //             },
    //             /**
    //              * in caso la tabella excel ha piu' di una serie left va splittato il valore usando il separatoreLeft
    //              * @param compactValue
    //              * @return {*[]|*}
    //              * @private
    //              */
    //             _getLeftValues(compactValue) {
    //                 var that = this;
    //                 if (Object.keys(that.json.result.leftSeries).length > 1) {
    //                     return compactValue.split(that.json.result.separatoreLeft);
    //                 }
    //                 return [compactValue]
    //             },
    //             isInt (val) {
    //                 return Number(val) === val && val % 1 === 0;
    //             }
    //         }
    //     })
    //     return vTable;
    // }
</script>
