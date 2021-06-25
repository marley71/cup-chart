@include('cup-chart-module.charts.vue-map')
@include('cup-chart-module.charts.vue-chart')
@include('cup-chart-module.charts.vue-table')

<script src='/cup-chart-module/js/SchemaColori.js'></script>
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
