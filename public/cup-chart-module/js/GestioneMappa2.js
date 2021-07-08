var commonMap = {
    id : 'map',
    map : null,
    mode : 'regioni',
    accessToken : null,
    center : [12.4829321,41.8933203],
    distribuzione : null,
    range : {},
    layers : {},
    colors : {},
    zoom : 4.9,
    basePath : '/geo-shapes/italy/',
    labelValore : 'Numero',
    suffissoValore : '',
    placesKey: [],
    layoutProperties : {},
    data : {},
    sourceName : null,
    geojson : null,
    fieldId : '',  // nome utilizzato nelle properties  per i test nei layers,
    popups: [], // vettore delle popups create per poterle rimuovere quando cambiano i dati
    sources : [],

    init(callback) {
        var that = this;

        for (var layerId in that.layers) {
            that.map.removeLayer(layerId);
        }
        that.layers = {};
        jQuery('body').off('popup-created');
        jQuery('body').on('popup-created',function (event,popup) {
            //console.log('popup create',popup);
            that.popups.push(popup);
        })
        that.caricaMapJson(function () {
            if (callback)
                return callback();
        });

    },

    removePopups() {
        var that = this;
        for(var i in that.popups) {
            that.popups[i].remove();
        }
        that.popups = [];
    },
    showMap() {
        var that = this;
        console.log('creo mapp',that.center,that.zoom);
        mapboxgl.accessToken = that.accessToken; //'pk.eyJ1IjoiY2l1bGxvIiwiYSI6ImNra3dteXB6azI4YW0zMHFuMzgzaDI3NnAifQ.xTToI9E2YbuletQYFOTbcQ';
        that.map = new mapboxgl.Map({
            container: that.id,
            style: 'mapbox://styles/mapbox/dark-v10',
            center : that.center,
            zoom : that.zoom,
            //zoomOffset: 0,
            //minZoom : 0,
        });
    },

    rimuoviDistribuzione(currentKey) {
        var that = this;
        var keys = Object.keys(that.data.values);
        var selected = currentKey?currentKey:keys[0];
        if (that.distribuzione) {
            // that.map.eachLayer(function (layer) {
            //     that.map.off('click',layer,that._createPopup);
            //     that.map.removeLayer(layer);
            // });


            for (var ri in that.range[selected]) {
                var r = that.range[selected][ri];
                var layerId = that.distribuzione + "_place_" + r;
                if (that.map.getLayer(layerId)) {
                    that.map.off('click',layerId,that._createPopup);
                    that.map.removeLayer(layerId);
                }

                delete that.layers[layerId];
            }
            //that.map.remove();
            that.distribuzione = null;
        }
    },

    caricaDistribuzione(name,data,currentKey) {
        var that = this;
        that.removePopups();
        that.data = data;
        that.suffissoValore = data.extra.suffisso;
        var keys = Object.keys(data.values);
        var selected = currentKey?currentKey:keys[0];
        console.log('keys',keys,selected,data.values)
        console.log('range2',that.range);
        //var range = [];
        var layoutProperties = {};
        var colors = that.colors;
        console.log('colors',colors);
        for (var c in colors) {
            layoutProperties[c] =  {
                bgColor : colors[c],
                opacity : 1,
            };
        }


        that.layoutProperties = layoutProperties;
        //console.log('range',that.layoutProperties);
        that.distribuzione = name

        var normalizzato = {};
        for (var k in data.values[selected]) {
            normalizzato[k.toUpperCase()] = data.values[selected][k];
        }


        console.log('normalizzato',normalizzato);
        var geojson = that.geojson; // that.regioni_geojson; // that.regioni[regione].geojson;

        for(var place in that.placesKey) {

            for (var js in geojson.features) {
                var s = new String(geojson.features[js].properties[that.fieldId]);
                var cIstat = s.toUpperCase();
                //console.log('cIstat',cIstat,geojson.features[js].properties.NOME_REG,normalizzato[cIstat])
                if (!normalizzato[cIstat]) {
                    geojson.features[js].properties.total = that.range[selected][0];
                    geojson.features[js].properties.layoutProperties = layoutProperties[0];
                } else {
                    var total = normalizzato[cIstat].total;
                    var index = 0;
                    for (var ii in that.range[selected]) {
                        if (total >= that.range[selected][ii])
                            index = ii;
                    }
                    geojson.features[js].properties.distretto = that.range[selected][index]; //Math.floor(Math.random() * 10);
                    geojson.features[js].properties.total = that.valueFormat(total); //that.euroFormat(total);
                    geojson.features[js].properties.layoutProperties = layoutProperties[that.range[selected][index] ];
                    geojson.features[js].properties.labelValore = that.labelValore;
                }

            }
        }
        console.log('RANGE SELEZIONATO',that.range[selected]);
        // istanzio i layers
        //for(var regione in that.regioni) {
        for (var ri in that.range[selected] ) {
            var r = that.range[selected][ri];
            //console.log('aggiungo layer',comune + "_" +r, layoutProperties[r]);
            var layerId = that.distribuzione + "_place_" + r;
            //console.log(layerId,'add layer properties',layoutProperties[r])
            if (!that.map.getLayer(layerId)) {
                console.log('kkkkkk',layerId)
                that.map.addLayer({
                    'id': layerId,
                    'type': 'fill',
                    'source': that.sourceName,
                    //'source-layer': 'countries_polygons',
                    // 'layout': {
                    //     'line-join': 'round',
                    //     'line-cap': 'round',
                    //     //'background' : '#FF0000'
                    // },
                    'paint': {
                        //'line-color': '#ff69b4',
                        //'line-width': 2,
                        'fill-color': layoutProperties[ri].bgColor,
                        'fill-opacity' : layoutProperties[ri].opacity,
                        'fill-outline-color': 'red'
                    },
                    filter : ['==', 'distretto', r]
                });
                that.layers[layerId] = layerId;
                that.map.on('click', layerId, that._createPopup);
                that.map.on('mouseenter', layerId, function () {
                    that.map.getCanvas().style.cursor = 'pointer';
                });
            }




// Change it back to a pointer when it leaves.
            that.map.on('mouseleave', layerId, function () {
                that.map.getCanvas().style.cursor = '';
            });
        }
        //}
        console.log('layers',that.layers)
        console.log('features',geojson.features)
        that.map.getSource(that.sourceName).setData(geojson);

    },

    _gradient(startColor,endColor,steps) {
        var _hex = function  (c) {
            var s = "0123456789abcdef";
            var i = parseInt (c);
            if (i == 0 || isNaN (c))
                return "00";
            i = Math.round (Math.min (Math.max (0, i), 255));
            return s.charAt ((i - i % 16) / 16) + s.charAt (i % 16);
        }

        /* Convert an RGB triplet to a hex string */
        var _convertToHex = function  (rgb) {
            return _hex(rgb[0]) + _hex(rgb[1]) + _hex(rgb[2]);
        }

        /* Remove '#' in color hex string */
        var _trim =  function  (s) { return (s.charAt(0) == '#') ? s.substring(1, 7) : s }

        /* Convert a hex string to an RGB triplet */
        var _convertToRGB = function  (hex) {
            var color = [];
            color[0] = parseInt ((_trim(hex)).substring (0, 2), 16);
            color[1] = parseInt ((_trim(hex)).substring (2, 4), 16);
            color[2] = parseInt ((_trim(hex)).substring (4, 6), 16);
            return color;
        }



        // The beginning of your gradient
        var start = _convertToRGB (startColor);

        // The end of your gradient
        var end   = _convertToRGB (endColor);

        // The number of colors to compute
        var len = steps;

        //Alpha blending amount
        var alpha = 0.0;

        var saida = [];

        for (i = 0; i < len; i++) {
            var c = [];
            alpha += (1.0/len);

            c[0] = start[0] * alpha + (1 - alpha) * end[0];
            c[1] = start[1] * alpha + (1 - alpha) * end[1];
            c[2] = start[2] * alpha + (1 - alpha) * end[2];

            saida.push(_convertToHex (c));

        }

        return saida;


    },
    euroFormat(value,decimal) {
        return new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' ,minimumFractionDigits: 0}).format(Math.floor(value))
    },
    valueFormat(value) {
        var that = this;
        var val = value;
        switch (that.data.extra.tipo_valore) {
            case 'numero':
            case 'percentuale':
                val =  value.toFixed(2);
                break;
        }
        return val + ' ' + that.data.extra.suffisso;
    }

}

// -- Gestione Mappa Regioni -----
var GestioneMappaRegioni = Object.create(commonMap);
GestioneMappaRegioni.basePath = '/geo-shapes/italy/';
GestioneMappaRegioni.zoom = 4.9;
GestioneMappaRegioni.center = [12.4829321,41.8933203];
GestioneMappaRegioni.sourceName = 'regioni';
GestioneMappaRegioni.fieldId = 'NOME_REG';

GestioneMappaRegioni.caricaMapJson = function (callback) {
    var that = this;
    if (that.map.getSource(that.sourceName) ) {
        return callback();
    }
    $.getJSON(that.basePath + 'regions_special.geojson', function (geojson) {
        //console.log('caricato',comune);
        console.log('caricato',geojson);
        var keys = [];
        for (var i in geojson.features) {
            keys.push(geojson.features[i].properties.NOME_REG.toUpperCase());
        }
        console.log('regioni keys',keys);
        //var keys = Object.keys(that.regioni);
        that.geojson = geojson;
        that.map.addSource(that.sourceName,{
            type : 'geojson',
            data : geojson
        })
        for (var i in keys) {
            var layerId = keys[i];
            //console.log('aggiungo layer',layerId);
            that.map.addLayer({
                'id': layerId,
                'type': 'fill',
                'source': that.sourceName,
                //'source-layer': 'countries_polygons',
                // 'layout': {
                //     'line-join': 'round',
                //     'line-cap': 'round',
                //     //'background' : '#FF0000'
                // },
                'paint': {
                    //'line-color': '#ff69b4',
                    //'line-width': 2,
                    'fill-color': 'red',
                    'fill-opacity' : 0,
                    //'fill-opacity' : layoutProperties[r].opacity,
                    'fill-outline-color': 'red'
                },
                filter : ['==', 'NOME_REG', keys[i]]
            });
            that.layers[layerId] = layerId;
        }
        that.placesKey = keys;
        return callback();
    });
};

GestioneMappaRegioni._createPopup = function (e) {
    var that = this;  // questo this e' map non l'oggetto GestioneMappa
    console.log('THAT',that.parent);
    var coordinates = coordinates = [e.lngLat.lng,e.lngLat.lat];
    var properties = e.features[0].properties;
    console.log('cliccato',properties);
    var description = '<b>' + properties.NOME_REG + '</b>';
    var value = properties.total;
    description += "<br>" + properties.labelValore + ":<br> " + value;



// Ensure that if the map is zoomed out such that multiple
    // copies of the feature are visible, the popup appears
    // over the copy being pointed to.
    // while (Math.abs(e.lngLat.lng - coordinates[0]) > 180) {
    //     coordinates[0] += e.lngLat.lng > coordinates[0] ? 360 : -360;
    // }

    //console.log('coordinate',coordinates)
    var p = new mapboxgl.Popup()
        .setLngLat(coordinates)
        .setHTML(description)
        .addTo(that);
    //console.log('properties', e.features[0].properties,popup)
    jQuery('.mapboxgl-popup-content').css('min-width','180px');
    jQuery('body').trigger('popup-created',[p]);
};

// -- Gestione Mappa Province -----
var GestioneMappaProvince = Object.create(commonMap);
GestioneMappaProvince.basePath = '/geo-shapes/italy/';
GestioneMappaProvince.zoom = 4.9;
GestioneMappaProvince.center = [12.4829321,41.8933203];
GestioneMappaProvince.sourceName = 'province';
GestioneMappaProvince.fieldId = 'NOME_PRO';

GestioneMappaProvince.caricaMapJson = function (callback) {
    var that = this;
    if (that.map.getSource(that.sourceName) ) {
        return callback();
    }
    $.getJSON(that.basePath + 'provinces.geojson', function (geojson) {
        console.log('caricato',geojson);
        var keys = [];
        for (var i in geojson.features) {
            keys.push(geojson.features[i].properties[that.fieldId].toUpperCase());
        }
        //var keys = Object.keys(that.regioni);
        //var tmp = Object.keys(that.json.result.values)[0];
        //keys  = Object.keys(that.json.result.values[tmp]);
        console.log('province keys',keys);
        that.geojson = geojson;
        that.map.addSource(that.sourceName,{
            type : 'geojson',
            data : geojson
        })
        for (var i in keys) {
            var layerId = keys[i];
            that.map.addLayer({
                'id': layerId,
                'type': 'fill',
                'source': that.sourceName,
                //'source-layer': 'countries_polygons',
                // 'layout': {
                //     'line-join': 'round',
                //     'line-cap': 'round',
                //     //'background' : '#FF0000'
                // },
                'paint': {
                    //'line-color': '#ff69b4',
                    //'line-width': 2,
                    'fill-color': 'red',
                    'fill-opacity' : 0,
                    //'fill-opacity' : layoutProperties[r].opacity,
                    'fill-outline-color': 'red'
                },
                filter : ['==', 'name', keys[i]]
            });
            that.layers[layerId] = layerId;
        }
        that.placesKey = keys;
        return callback();
    });
};

GestioneMappaProvince._createPopup = function (e) {
    var that = this;  // questo this e' map non l'oggetto GestioneMappa
    console.log('THAT',that.parent);
    var coordinates = coordinates = [e.lngLat.lng,e.lngLat.lat];
    var properties = e.features[0].properties;
    console.log('cliccato',properties);
    var description = '<b>' + properties.NOME_PRO + '</b>';
    var value = properties.total;
    description += "<br>" + properties.labelValore + ":<br> " + value;



// Ensure that if the map is zoomed out such that multiple
    // copies of the feature are visible, the popup appears
    // over the copy being pointed to.
    // while (Math.abs(e.lngLat.lng - coordinates[0]) > 180) {
    //     coordinates[0] += e.lngLat.lng > coordinates[0] ? 360 : -360;
    // }

    //console.log('coordinate',coordinates)
    var p = new mapboxgl.Popup()
        .setLngLat(coordinates)
        .setHTML(description)
        .addTo(that);
    //console.log('properties', e.features[0].properties,popup)
    jQuery('.mapboxgl-popup-content').css('min-width','180px');
    jQuery('body').trigger('popup-created',[p]);
};

// -- Gestione Mappa Nazioni -----
var GestioneMappaNazioni = Object.create(commonMap);
GestioneMappaNazioni.basePath = '/geo-shapes/';
GestioneMappaNazioni.zoom = 0;
GestioneMappaNazioni.center = [0,0];
GestioneMappaNazioni.sourceName = 'nazioni';
GestioneMappaNazioni.fieldId = 'name';

GestioneMappaNazioni._createPopup = function (e) {
    var that = this;  // questo this e' map non l'oggetto GestioneMappa
    console.log('THAT',that.parent);
    var coordinates = coordinates = [e.lngLat.lng,e.lngLat.lat];
    var properties = e.features[0].properties;
    console.log('cliccato',properties);
    var description = '<b>' + properties.name + '</b>';
    var value = properties.total;
    description += "<br>" + properties.labelValore + ":<br> " + value;



// Ensure that if the map is zoomed out such that multiple
    // copies of the feature are visible, the popup appears
    // over the copy being pointed to.
    // while (Math.abs(e.lngLat.lng - coordinates[0]) > 180) {
    //     coordinates[0] += e.lngLat.lng > coordinates[0] ? 360 : -360;
    // }

    //console.log('coordinate',coordinates)
    var p = new mapboxgl.Popup()
        .setLngLat(coordinates)
        .setHTML(description)
        .addTo(that);
    //console.log('properties', e.features[0].properties,popup)
    jQuery('.mapboxgl-popup-content').css('min-width','180px');
    jQuery('body').trigger('popup-created',[p]);
}

GestioneMappaNazioni.caricaMapJson = function (callback) {
    var that = this;
    if (that.map.getSource(that.sourceName) ) {
        return callback();
    }
    $.getJSON(that.basePath + 'nazioni.geojson', function (geojson) {
        console.log('caricato',geojson);
        var keys = [];
        for (var i in geojson.features) {
            keys.push(geojson.features[i].properties.name.toUpperCase());
        }
        //var keys = Object.keys(that.regioni);
        //var tmp = Object.keys(that.json.result.values)[0];
        //keys  = Object.keys(that.json.result.values[tmp]);
        console.log('nazioni keys',keys);
        that.geojson = geojson;
        that.map.addSource(that.sourceName,{
            type : 'geojson',
            data : geojson
        })
        for (var i in keys) {
            var layerId = keys[i];
            //console.log('aggiungo layer',layerId);
            that.map.addLayer({
                'id': layerId,
                'type': 'fill',
                'source': that.sourceName,
                //'source-layer': 'countries_polygons',
                // 'layout': {
                //     'line-join': 'round',
                //     'line-cap': 'round',
                //     //'background' : '#FF0000'
                // },
                'paint': {
                    //'line-color': '#ff69b4',
                    //'line-width': 2,
                    'fill-color': 'red',
                    'fill-opacity' : 0,
                    //'fill-opacity' : layoutProperties[r].opacity,
                    'fill-outline-color': 'red'
                },
                filter : ['==', 'name', keys[i]]
            });
            that.layers[layerId] = layerId;
        }
        that.placesKey = keys;
        return callback();
    });
}



// -- Gestione Mappa Comuni -----
var GestioneMappaComuni = Object.create(commonMap);
GestioneMappaComuni.basePath = '/geo-shapes/italy/regions/';
GestioneMappaComuni.zoom = 4.9;
GestioneMappaComuni.center = [12.4829321,41.8933203];
GestioneMappaComuni.sourceName = 'comuni';
GestioneMappaComuni.fieldId = 'NOME_COM';
GestioneMappaComuni.regioni_ids = [{
    id: 13,
    geojson : null,
    bgColor : '#FFFFFF'
}];

GestioneMappaComuni.caricaMapJson = function (callback) {
    var that = this;
    // var map = that.map;
    // //var lastLayer = '';
    // var keys = Object.keys(that.comuni);
    // if (that.map.getSource(keys[0])) {
    //     return callback();
    // }
    var keys = that.regioni_ids;
    var __caricaComuni = function (i) {
        var regioneId = that.regioni_ids[i].id;
        if (that.map.getSource(that.sourceName + regioneId) ) {
            if ( (i +1) <keys.length)
                __caricaComuni(i+1);
            else
                callback();
            return ;
        }
        $.getJSON(that.basePath + regioneId + '/municipalities.geojson', function (geojson) {
            //console.log('caricato',comune);
            that.regioni_ids[i].geojson = geojson;
            that.map.addSource(that.sourceName + regioneId,{
                type : 'geojson',
                data : geojson
            })
            var layerId = that.sourceName + regioneId;
            that.map.addLayer({
                'id': layerId,
                'type': 'fill',
                'source': that.sourceName + regioneId,
                //'source-layer': 'countries_polygons',
                // 'layout': {
                //     'line-join': 'round',
                //     'line-cap': 'round',
                //     //'background' : '#FF0000'
                // },
                'paint': {
                    //'line-color': '#ff69b4',
                    //'line-width': 2,
                    'fill-color': that.regioni_ids[i].bgColor,
                    'fill-opacity' : 0,
                    //'fill-opacity' : layoutProperties[r].opacity,
                    'fill-outline-color': 'red'
                },
                //filter : ['==', 'distretto', r]
            });
            that.layers[layerId] = layerId;
            if ( (i +1) <keys.length)
                __caricaComuni(i+1);
            else
                callback();
        });

    }
    __caricaComuni(0);
};


GestioneMappaComuni._createPopup = function (e) {
    var that = this;  // questo this e' map non l'oggetto GestioneMappa
    //console.log('THAT',that);
    var coordinates = coordinates = [e.lngLat.lng,e.lngLat.lat];
    var properties = e.features[0].properties;
    console.log('cliccato',properties);
    var description = '<b>' + properties.NOME_COM + '</b>';
    var value = e.features[0].properties.total;
    description += "<br>Importo:<br> " + value;



// Ensure that if the map is zoomed out such that multiple
    // copies of the feature are visible, the popup appears
    // over the copy being pointed to.
    // while (Math.abs(e.lngLat.lng - coordinates[0]) > 180) {
    //     coordinates[0] += e.lngLat.lng > coordinates[0] ? 360 : -360;
    // }

    //console.log('coordinate',coordinates)
    new mapboxgl.Popup()
        .setLngLat(coordinates)
        .setHTML(description)
        .addTo(that);
    //console.log('properties', e.features[0].properties,popup)
    jQuery('.mapboxgl-popup-content').css('min-width','180px');
};

GestioneMappaComuni.caricaDistribuzione = function (name,data,currentKey) {
    var that = this;
    that.removePopups();
    that.data = data;
    console.log('extra',data.extra);
    that.suffissoValore = data.extra.suffisso;


    var keys = Object.keys(data.values);
    var selected = currentKey?currentKey:keys[0];
    console.log('keys',keys,selected,data.values)
    console.log('range2',that.range);
    //var range = [];
    var layoutProperties = {};
    var colors = that.colors;
    console.log('colors',colors);
    for (var c in colors) {
        layoutProperties[c] =  {
            bgColor : colors[c],
            opacity : 1,
        };
    }

    that.layoutProperties = layoutProperties;
    //console.log('range',that.layoutProperties);
    that.distribuzione = name

    var normalizzato = {};
    for (var k in data.values[selected]) {
        normalizzato[k.toUpperCase()] = data.values[selected][k];
    }


    console.log('normalizzato',normalizzato);
    //var geojson = that.geojson; // that.regioni_geojson; // that.regioni[regione].geojson;

    for(var cc in that.regioni_ids) {
        var geojson = that.regioni_ids[cc].geojson;
        var regioneId = that.regioni_ids[cc].id;
        for (var js in geojson.features) {
            var s = new String(geojson.features[js].properties[that.fieldId]);
            var cIstat = s.toUpperCase();
            //console.log('cIstat',cIstat,geojson.features[js].properties.NOME_REG,normalizzato[cIstat])
            if (!normalizzato[cIstat]) {
                geojson.features[js].properties.total = that.range[selected][0];
                geojson.features[js].properties.layoutProperties = layoutProperties[0];
            } else {
                var total = normalizzato[cIstat].total;
                var index = 0;
                for (var ii in that.range[selected]) {
                    if (total >= that.range[selected][ii])
                        index = ii;
                }
                geojson.features[js].properties.distretto = that.range[selected][index]; //Math.floor(Math.random() * 10);
                geojson.features[js].properties.total = that.valueFormat(total); //that.euroFormat(total);
                geojson.features[js].properties.layoutProperties = layoutProperties[that.range[selected][index] ];
                geojson.features[js].properties.labelValore = that.labelValore;
            }

        }
        for (var ri in that.range[selected] ) {
            var r = that.range[selected][ri];
            //console.log('aggiungo layer',comune + "_" +r, layoutProperties[r]);
            var layerId = that.distribuzione + "_place_" + r;
            //console.log(layerId,'add layer properties',layoutProperties[r])
            if (!that.map.getLayer(layerId)) {
                console.log('kkkkkk',layerId)
                that.map.addLayer({
                    'id': layerId,
                    'type': 'fill',
                    'source': that.sourceName + regioneId,
                    //'source-layer': 'countries_polygons',
                    // 'layout': {
                    //     'line-join': 'round',
                    //     'line-cap': 'round',
                    //     //'background' : '#FF0000'
                    // },
                    'paint': {
                        //'line-color': '#ff69b4',
                        //'line-width': 2,
                        'fill-color': layoutProperties[ri].bgColor,
                        'fill-opacity' : layoutProperties[ri].opacity,
                        'fill-outline-color': 'red'
                    },
                    filter : ['==', 'distretto', r]
                });
                that.layers[layerId] = layerId;
                that.map.on('click', layerId, that._createPopup);
                that.map.on('mouseenter', layerId, function () {
                    that.map.getCanvas().style.cursor = 'pointer';
                });
            }




            // Change it back to a pointer when it leaves.
            that.map.on('mouseleave', layerId, function () {
                that.map.getCanvas().style.cursor = '';
            });
        }
        console.log('layers',that.layers)
        console.log('features',geojson.features)
        that.map.getSource(that.sourceName + regioneId).setData(geojson);
    }
    console.log('RANGE SELEZIONATO',that.range[selected]);
    // istanzio i layers
    //for(var regione in that.regioni) {

};

GestioneMappaComuni.rimuoviDistribuzione = function (currentKey) {
    var that = this;
    var keys = Object.keys(that.data.values);
    var selected = currentKey?currentKey:keys[0];
    console.log('rimuovo distribuzione',that.distribuzione,selected)
    if (that.distribuzione) {
        // that.map.eachLayer(function (layer) {
        //     that.map.off('click',layer,that._createPopup);
        //     that.map.removeLayer(layer);
        // });


        for (var ri in that.range[selected]) {
            var r = that.range[selected][ri];
            var layerId = that.distribuzione + "_place_" + r;
            console.log('rimuovo layer',layerId,that.map.getLayer(layerId));
            if (that.map.getLayer(layerId)) {
                that.map.off('click',layerId,that._createPopup);
                that.map.removeLayer(layerId);
            }

            delete that.layers[layerId];
        }
        //that.map.remove();
        that.distribuzione = null;
    }
};

// var GestioneMappaComuni = {
//     id : 'map',
//     map : null,
//     mode : 'regioni',
//     accessToken : null,
//     center : [14,42.4],
//     luoghi_sensibili : [],
//     luoghi_gioco : [],
//     distribuzione : null,
//     range : [0],
//     maxRange : 1,
//     distibuzioneLayers:[],
//     macroTipologie :[],
//     macroTipologieGioco : [],
//     tipologie : [],
//     tipologieGioco : [],
//     layers : {},
//     colors : {},
//     zoom : 9,
//     basePath : '/geo-shapes/italy/',
//     icons : {
//         'scuola' : '/abruzzo/demo.files/gmap_icons/college-15.png'//'/abruzzo/demo.files/gmap_icons/mapbox-icon.png'
//     },
//     // layoutProperties : {
//     //     0: {
//     //         bgColor : '#AAAAAA',
//     //         opacity : 1,
//     //     },
//     // },
//     layoutProperties : {},
//     comuni :  {
//         'aquila' : {
//             bgColor : '#FFFFFF',
//             geojson : {},
//         },
//         'chieti' : {
//             bgColor : '#FFAA00',
//             geojson : {},
//         },
//         'pescara' : {
//             bgColor : '#0000FF',
//             geojson : {},
//         },
//         'teramo' : {
//             bgColor : '#00FF00',
//             geojson : {},
//         },
//     },
//     showMap() {
//         var that = this;
//         mapboxgl.accessToken = that.accessToken; //'pk.eyJ1IjoiY2l1bGxvIiwiYSI6ImNra3dteXB6azI4YW0zMHFuMzgzaDI3NnAifQ.xTToI9E2YbuletQYFOTbcQ';
//         that.map = new mapboxgl.Map({
//             container: that.id,
//             style: 'mapbox://styles/mapbox/dark-v10',
//             center : that.center,
//             zoom : that.zoom,
//
//         });
//         //that.p = new mapboxgl.geocoder();
//     },
//     init(callback) {
//         var that = this;
//         for (var layerId in that.layers) {
//             that.map.removeLayer(layerId);
//         }
//         that.layers = {};
//         if (that.mode == 'regioni') {
//             that.caricaRegioni(function () {
//                 if (callback)
//                     return callback();
//             });
//         } else {
//             that.caricaComuni(function () {
//                 that.caricaIcons(function () {
//                     if (callback)
//                         return callback();
//                 })
//             });
//         }
//
//
//     },
//
//
//
//     toggleMacroTipologia(macro_id,gioco) {
//         var that = this;
//         var t = gioco?that.macroTipologieGioco:that.macroTipologie;
//         var baseName = gioco?'luoghi_gioco_':'luoghi_sensibili_';
//         //console.log('t',t)
//         for (var i in t) {
//             //console.log('id',t[i].id,macro_id)
//             if (t[i].id == macro_id) {
//                 for (var j in t[i].tipi) {
//                     that.toggleLayer(baseName+t[i].tipi[j].id);
//                 }
//             }
//         }
//     },
//     toggleLayer(clickedLayer) {
//         var that = this;
//         //var clickedLayer = 'luoghi_sensibili';
//         var visibility = that.map.getLayoutProperty(clickedLayer, 'visibility');
//         //console.log('visibile',visibility)
//         // toggle layer visibility by changing the layout object's visibility property
//         if (!visibility || visibility === 'visible') {
//             that.map.setLayoutProperty(clickedLayer, 'visibility', 'none');
//             this.className = '';
//         } else {
//             this.className = 'active';
//             that.map.setLayoutProperty(clickedLayer, 'visibility', 'visible');
//         }
//     },
//     toggleComune(comune) {
//         var that = this;
//         if (!that.distribuzione) {
//             that.toggleLayer(comune);
//             return ;
//         }
//
//
//         for (var i in that.range) {
//             that.toggleLayer(that.distribuzione + "_" + comune+'_'+that.range[i]);
//         }
//     },
//
//     filterTipologia(layerId,values) {
//         var that = this;
//         that.map.setFilter(layerId,null);
//         values = values.map(function (item) {return parseInt(item,10)});
//         console.log('filterTipologia',layerId,values[0],values)
//         var filter = ['in','tipo_id'].concat(values);
//         console.log('filter',filter);
//         that.map.setFilter(layerId,filter);
//         // for (var i in values) {
//         //     that.map.setFilter(layerId,['match','tipo_id',values[i]]);
//         // }
//
//
//     },
//
//     rimuoviDistribuzione() {
//         var that = this;
//         if (that.distribuzione) {
//             if (that.mode == 'regioni') {
//                 for(var regione in that.regioni) {
//                     for (var ri in that.range) {
//                         var r = that.range[ri];
//                         var layerId = that.distribuzione + '_' + regione + "_" + r;
//                         that.map.off('click',layerId,that._createPopup);
//                         that.map.removeLayer(layerId);
//                         delete that.layers[layerId];
//                     }
//                 }
//             } else {
//                 for(var comune in that.comuni) {
//                     for (var ri in that.range) {
//                         var r = that.range[ri];
//                         var layerId = that.distribuzione + '_' + comune + "_" + r;
//                         that.map.off('click',layerId,that._createPopup);
//                         that.map.removeLayer(layerId);
//                         delete that.layers[layerId];
//
//                     }
//                 }
//             }
//
//             //that.map.remove();
//             that.distribuzione = null;
//         }
//     },
//
//     caricaDistribuzione(name,data,currentKey) {
//         var that = this;
//
//         var keys = Object.keys(data.values);
//         var selected = currentKey?currentKey:keys[0];
//         console.log('keys',keys,selected,data.values)
//         //calcolo intervalli
//         var bgColor = '#AA2222';
//         //var min = data.min;
//         //var max = data.max;
//         console.log('range2',that.range);
//         //var range = [];
//         var layoutProperties = {};
//         //range = [min];
//         //var colors = that._gradient('#FFFFFF','#eb9c2f',4);
//         var colors = that.colors;
//         console.log('colors',colors);
//         for (var c in colors) {
//             layoutProperties[c] =  {
//                 bgColor : colors[c],
//                 opacity : 1,
//             };
//         }
//
//         //that.range = range;
//         that.layoutProperties = layoutProperties;
//         //console.log('range',that.layoutProperties);
//         that.distribuzione = name
//
//
//
//         if (that.mode == 'regioni') {
//             //aggiorno la sorgente dati dei layer
//             // for (var k in data.values[selected]) {
//             //     var found = false;
//             //     for (var js in geojson.features) {
//             //         //console.log(k,regione);
//             //         var regione = new String(geojson.features[js].properties.NOME_REG);
//             //         if (k.toUpperCase() == regione) {
//             //             console.log('trovata',k);
//             //             found = k;
//             //             break;
//             //         }
//             //     }
//             //     if (found) {
//             //
//             //     } else {
//             //         geojson.features[js].properties.total = that.range[0];
//             //         geojson.features[js].properties.layoutProperties = layoutProperties[0];
//             //     }
//             // }
//             // return ;
//             var normalizzato = {};
//             for (var k in data.values[selected]) {
//                 normalizzato[k.toUpperCase()] = data.values[selected][k];
//             }
//
//
//             console.log('normalizzato',normalizzato);
//             var geojson = that.regioni_geojson; // that.regioni[regione].geojson;
//             for(var regione in that.regioni) {
//
//                 for (var js in geojson.features) {
//                     var s = new String(geojson.features[js].properties.NOME_REG);
//                     var cIstat = s.toUpperCase();
//                     //console.log('cIstat',cIstat,geojson.features[js].properties.NOME_REG,normalizzato[cIstat])
//                     if (!normalizzato[cIstat]) {
//                         geojson.features[js].properties.total = that.range[0];
//                         geojson.features[js].properties.layoutProperties = layoutProperties[0];
//                     } else {
//                         var total = normalizzato[cIstat].total;
//                         var index = 0;
//                         for (var ii in that.range) {
//                             if (total >= that.range[ii])
//                                 index = ii;
//                         }
//                         geojson.features[js].properties.distretto = that.range[index]; //Math.floor(Math.random() * 10);
//                         geojson.features[js].properties.total = that.euroFormat(total);
//                         geojson.features[js].properties.layoutProperties = layoutProperties[that.range[index] ];
//                     }
//
//                 }
//             }
//             console.log('RANGE',that.range);
//             // istanzio i layers
//             for(var regione in that.regioni) {
//                 for (var ri in that.range ) {
//                     var r = that.range[ri];
//                     //console.log('aggiungo layer',comune + "_" +r, layoutProperties[r]);
//                     var layerId = name + '_' + regione + "_" + r;
//                     //console.log(layerId,'add layer properties',layoutProperties[r])
//                     that.map.addLayer({
//                         'id': layerId,
//                         'type': 'fill',
//                         'source': 'regioni',
//                         //'source-layer': 'countries_polygons',
//                         // 'layout': {
//                         //     'line-join': 'round',
//                         //     'line-cap': 'round',
//                         //     //'background' : '#FF0000'
//                         // },
//                         'paint': {
//                             //'line-color': '#ff69b4',
//                             //'line-width': 2,
//                             'fill-color': layoutProperties[ri].bgColor,
//                             'fill-opacity' : layoutProperties[ri].opacity,
//                             'fill-outline-color': 'red'
//                         },
//                         filter : ['==', 'distretto', r]
//                     });
//                     that.layers[layerId] = layerId;
//                     that.map.on('click', layerId, that._createPopup);
//                     that.map.on('mouseenter', layerId, function () {
//                         that.map.getCanvas().style.cursor = 'pointer';
//                     });
//
// // Change it back to a pointer when it leaves.
//                     that.map.on('mouseleave', layerId, function () {
//                         that.map.getCanvas().style.cursor = '';
//                     });
//                 }
//             }
//             console.log('layers',that.layers)
//             console.log('features',geojson.features)
//             that.map.getSource('regioni').setData(geojson);
//         } else {
//             //aggiorno la sorgente dati dei layer
//             for(var comune in that.comuni) {
//                 var geojson = that.comuni[comune].geojson;
//                 for (var js in geojson.features) {
//                     var s = new String(geojson.features[js].properties.PRO_COM);
//                     var cIstat = s.padStart(6,'0');
//                     //console.log('cIstat',cIstat,geojson.features[js].properties)
//                     if (!data.values[selected][cIstat]) {
//                         geojson.features[js].properties.total = that.range[0];
//                         geojson.features[js].properties.layoutProperties = layoutProperties[0];
//                     } else {
//                         var total = data.values[selected][cIstat].total;
//                         var index = 0;
//                         for (var ii in that.range) {
//                             if (total >= that.range[ii])
//                                 index = ii;
//                         }
//                         //
//                         // var index=that.range.findIndex(function(number) {
//                         //     //console.log('check ',number,total);
//                         //     return number > total;
//                         // });
//                         // if (index < 0)
//                         //     index = 4;
//                         //console.log('total',total,'index',index,'range index',range[index],layoutProperties[range[index]])
//                         geojson.features[js].properties.distretto = that.range[index]; //Math.floor(Math.random() * 10);
//                         geojson.features[js].properties.total = that.euroFormat(total);
//                         geojson.features[js].properties.layoutProperties = layoutProperties[that.range[index] ];
//                     }
//
//                 }
//             }
//             for(var comune in that.comuni) {
//                 that.map.getSource(comune).setData(that.comuni[comune].geojson);
//             }
//             var lastLayer = that.tipologieGioco.length?'luoghi_gioco_' + that.tipologieGioco[that.tipologieGioco.length-1].id:null;
//
//             console.log('RANGE',that.range);
//             // istanzio i layers
//             for(var comune in that.comuni) {
//                 for (var ri in that.range ) {
//                     var r = that.range[ri];
//                     //console.log('aggiungo layer',comune + "_" +r, layoutProperties[r]);
//                     var layerId = name + '_' + comune + "_" + r;
//                     //console.log(layerId,'add layer properties',layoutProperties[r])
//                     that.map.addLayer({
//                         'id': layerId,
//                         'type': 'fill',
//                         'source': comune,
//                         //'source-layer': 'countries_polygons',
//                         // 'layout': {
//                         //     'line-join': 'round',
//                         //     'line-cap': 'round',
//                         //     //'background' : '#FF0000'
//                         // },
//                         'paint': {
//                             //'line-color': '#ff69b4',
//                             //'line-width': 2,
//                             'fill-color': layoutProperties[ri].bgColor,
//                             'fill-opacity' : layoutProperties[ri].opacity,
//                             'fill-outline-color': 'red'
//                         },
//                         filter : ['==', 'distretto', r]
//                     },lastLayer);
//                     that.layers[layerId] = layerId;
//                     that.map.on('click', layerId, that._createPopup);
//                     that.map.on('mouseenter', layerId, function () {
//                         that.map.getCanvas().style.cursor = 'pointer';
//                     });
//
// // Change it back to a pointer when it leaves.
//                     that.map.on('mouseleave', layerId, function () {
//                         that.map.getCanvas().style.cursor = '';
//                     });
//                 }
//             }
//         }
//
//
//
//
//
//     }
// }



