var GestioneGrafici = {
    params : {},
    destroy() {
        console.log('remove',jQuery(this.params.htmlElement),this.params.htmlElement);
        jQuery(this.params.htmlElement).remove();

    },
    getParamsFromJquery(element) {
        var context = {};
        var s = jQuery(element).attr('cup-filters') || false;
        var ctx = s?s.split(','): [];
        if (ctx.length > 0) {
            for (var i in ctx) {
                let tmp = ctx[i].split(':');
                context[tmp[0]] = tmp[1];
            }
        }
        var serieContext = {};
        var s = jQuery(element).attr('cup-series') || false;
        var ctx = s?s.split(','): [];
        if (ctx.length > 0) {
            for (var i in ctx) {
                let tmp = ctx[i].split(':');
                serieContext[tmp[0]] = tmp[1];
            }
        }
        console.log('CUPPARI SERIEW',jQuery(element).attr('cup-series'))
        var params = {
            resourceId : jQuery(element).attr('cup-grafico'),
            schemaColor : jQuery(element).attr('cup-colors')?jQuery(element).attr('cup-colors'):'default',
            resourceType : jQuery(element).attr('cup-type')?jQuery(element).attr('cup-type'):'chart',
            chartType : jQuery(element).attr('cup-chart-type')?jQuery(element).attr('cup-chart-type'):'chart',
            htmlElement : element,
            filters : context,
            series: serieContext,
            titolo:jQuery(element).attr('cup-titolo')
        }
        return params;
    },
    render(params) {
        this.params = params;
        params.colors = schema_colori[params.schemaColor];
        console.log('params',params);
        var grafico = null;
        switch (params.resourceType) {
            case 'map':
                grafico = vueMapInit(params.htmlElement,params);
                break;
            case 'chart':
                if (params.chartType == 'chart-o')
                    params.gChartType = 'BarChart'
                grafico = vueChartInit(params.htmlElement,params);
                break;
            // case 'chart-o':
            //     params.gChartType = 'BarChart'
            //     vueChartInit(params.htmlElement,params);
            //     break;
            case 'table':
                //params.gChartType = 'table'
                grafico = vueTableInit(params.htmlElement,params);
                break;
            default:
                throw "tipo grafico " + params.resourceType + " non gestito";
        }
        return grafico;
    }
}
