var commonMap = {
    id : 'map',
    mapStyle: 'mapbox://styles/mapbox/satellite-v9',
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
    labelValore : 'valore',
    suffissoValore : '',
    placesKey: [],
    layoutProperties : {},
    data : {},
    sourceName : null,
    geojson : null,
    fieldId : '',  // nome utilizzato nelle properties  per i test nei layers,
    popups: [], // vettore delle popups create per poterle rimuovere quando cambiano i dati
    sources : [],
    opacity : 1,
    allFeatures: [],

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
            if (callback) {
                return callback();
            }
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
            style: that.mapStyle,
            center : that.center,
            zoom : that.zoom,
            preserveDrawingBuffer: true
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
        that.allFeatures = [];
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
                opacity : that.opacity,
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
        that.allFeatures = geojson.features;
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
        var extra = that.data.extra;
        var decimali = extra.decimali;
        switch (extra.tipo_valore) {
            case 'percentuale':
                val =  value.toFixed(decimali);
                break;
            default:
                if (extra.tipo == 'integer') {
                    val = Number(value).toLocaleString("it-IT", {minimumFractionDigits: 0,maximumFractionDigits:0});
                } else
                    val = Number(value).toLocaleString("it-IT", {minimumFractionDigits: decimali,maximumFractionDigits:decimali});
                break;
        }
        var formatted = extra.prefisso?extra.prefisso+' ':'';
        formatted += (extra.suffisso?val+ ' ' +extra.suffisso:val);
        return formatted;
        //return that.data.extra.suffisso val + ' ' + that.data.extra.suffisso;
    },

    fitMapBound() {
        var that = this;
        var coordinates = [];
        var features = that.allFeatures;
        setTimeout(function () {
            //console.log('features',features);
            for (var i=0;features.length;i++) {
                //console.log('aaa',features[i])
                if (features[i]) {
                    var cord = features[i].geometry.coordinates[0];
                    for (var c in cord) {
                        if (Array.isArray(cord[c][0])) {
                            for (var j in cord[c]) {
                                coordinates.push([
                                    cord[c][j][0],
                                    cord[c][j][1],
                                ])
                            }
                        } else {
                            coordinates.push([
                                cord[c][0],
                                cord[c][1]
                            ])
                        }

                    }
                } else
                    break;


            }
            //console.log('coordinate fit',coordinates);
            that.findBoundingBoxForGivenLocations(coordinates);
        },100);

    },

    findBoundingBoxForGivenLocations (coordinates) {
        var that = this;
        var west = 0.0;
        var east = 0.0;
        var north = 0.0;
        var south = 0.0;

        for (var lc = 0; lc < coordinates.length; lc++) {
            var tmp = coordinates[lc];
            var loc = new mapboxgl.LngLat(tmp[0],tmp[1]);
            if (lc == 0) {
                north = loc.lat;
                south = loc.lat;
                west = loc.lng;
                east = loc.lng;
            }
            else
            {
                if (loc.lat > north)
                {
                    north = loc.lat;
                }
                else if (loc.lat < south)
                {
                    south = loc.lat;
                }
                if (loc.lng < west)
                {
                    west = loc.lng;
                }
                else if (loc.lng > east)
                {
                    east = loc.lng;
                }
            }
        }

        // OPTIONAL - Add some extra "padding" for better map display
        var padding = 0.01;
        north = north + padding;
        south = south - padding;
        west = west - padding;
        east = east + padding;
        console.log(west,north,east,south);
        var sup = new mapboxgl.LngLat(west,north);
        var giu = new mapboxgl.LngLat(east,south);

        //var llb = new mapboxgl.LngLatBounds(sw, ne);
        var llb = new mapboxgl.LngLatBounds(sup, giu);
        that.map.fitBounds(llb);
        //return new BoundingBox(north, east, south, west);
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
        that.allFeatures = geojson.features;
        that.fitMapBound();
        //that.fitMapBound();
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
        that.allFeatures = geojson.features;
        that.fitMapBound();
        //that.fitMapBound();
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
        that.allFeatures = geojson.features;
        that.fitMapBound();
        //that.fitMapBound();
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
    var keys = that.regioni_ids;
    console.log('regioni ids',that.regioni_ids);
    var __caricaComuni = function (i) {
        var regioneId = that.regioni_ids[i].id;
        // se e' gia' stata caricata non carico piu' la sorgente
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
            that.allFeatures = that.allFeatures.concat(geojson.features);
            if ( (i +1) <keys.length) {
                //console.log('mergion geojson feature',geojson.features);
                __caricaComuni(i + 1);
            }
            else {
                //console.log('allFeatures',that.allFeatures)
                that.fitMapBound();
                callback();
            }

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
    //that.allFeatures = [];
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
            opacity : that.opacity,
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
        //console.log('features',geojson.features)
        that.allFeatures = that.allFeatures.concat(geojson.features);
        //console.log('allfeatuares',that.allFeatures);
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
