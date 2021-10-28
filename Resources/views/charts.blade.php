@include('modules.cup-chart.charts.vue-map')
@include('modules.cup-chart.charts.vue-chart')
@include('modules.cup-chart.charts.vue-table')

<script src='/cup-chart-module/js/SchemaColori.js'></script>
<script src="/cup-chart-module/js/GraficiConfs.js"></script>

<script src='/cup-chart-module/js/GestioneMappa2.js'></script>
<script src="/cup-chart-module/js/GestioneGrafici.js"></script>

<script>
    jQuery(function () {
        jQuery('[cup-grafico]').each(function () {
            var gs = Object.create(GestioneGrafici);
            var params = gs.getParamsFromJquery(jQuery(this));
            gs.render(params);
        })
    })
    function parseGraph(jQe) {
        var objs = [];
        jQe.find('[cup-grafico]').each(function () {
            var gs = Object.create(GestioneGrafici);
            var params = gs.getParamsFromJquery(jQuery(this));
            // aggiungo il parametro conf
            var confName = window[params['confName']] || {};
            var cupType = params['cup-type'];
            // console.log('chartConf',chartConf);
            var defaultConf = Object.create(chartConf);
            if (cupType == 'map')
                defaultConf = Object.create(mapConf);
            params.conf = {};
            params.conf = jQuery.extend(params.conf, defaultConf);
            params.conf = jQuery.extend(params.conf, confName);
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
