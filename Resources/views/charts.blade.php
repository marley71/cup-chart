@include('modules.cup-chart.charts.vue-map')
@include('modules.cup-chart.charts.vue-chart')
@include('modules.cup-chart.charts.vue-pie')
@include('modules.cup-chart.charts.vue-table')
<script>
    const MAP_BOX_KEY = '{{ env('MAPBOX_KEY') }}';
</script>
<script src='/cup-chart-module/js/SchemaColori.js'></script>
<script src="/cup-chart-module/js/GraficiConfs.js"></script>

<script src='/cup-chart-module/js/GestioneMappa2.js'></script>
<script src="/cup-chart-module/js/GestioneGrafici.js"></script>
<script src="/cup-chart-module/js/GraficiMixin.js"></script>

<script src="https://unpkg.com/bootstrap-multiselect@1.1.0/dist/js/bootstrap-multiselect.js"></script>
<link rel="dns-prefetch" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.15/css/bootstrap-multiselect.css">

<script>
    jQuery(function () {
        jQuery('[cup-grafico]').each(function () {
            var gs = Object.create(GestioneGrafici);
            var params = gs.getParamsFromJquery(jQuery(this));
            // aggiungo il parametro conf
            var conf = window[params['confName']] || {};
            var cupType = params['cup-type'];
            // console.log('chartConf',chartConf);
            var defaultConf = Object.create(chartConf);
            if (cupType == 'map')
                defaultConf = Object.create(mapConf);
            params.conf = {};
            params.conf = jQuery.extend(params.conf, defaultConf);
            params.conf = jQuery.extend(params.conf, conf);
            gs.render(params);
        })
    })
    function parseGraph(jQe) {
        var objs = [];
        jQe.find('[cup-grafico]').each(function () {
            var gs = Object.create(GestioneGrafici);
            var params = gs.getParamsFromJquery(jQuery(this));
            // aggiungo il parametro conf
            var conf = window[params['confName']] || {};
            var cupType = params['cup-type'];
            // console.log('chartConf',chartConf);
            var defaultConf = Object.create(chartConf);
            if (cupType == 'map')
                defaultConf = Object.create(mapConf);
            params.conf = {};
            params.conf = jQuery.extend(params.conf, defaultConf);
            params.conf = jQuery.extend(params.conf, conf);
            // console.log('paramsssssss',params);
            // console.log('defaultConf',defaultConf);
            // console.log('confsssssss',params.conf)
            var gf = gs.render(params);
            objs.push(gf);
        })
        return objs;
        // console.log('grafico',jQe.attr('cup-grafico'),jQe.html())
        // var gs = Object.create(GestioneGrafici);
        // var params = gs.getParamsFromJquery(jQe);
        // gs.render(params);
    }
</script>
