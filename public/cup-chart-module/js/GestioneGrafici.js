var GestioneGrafici = {
    params : {},
    token_split_filters:'###',
    destroy() {
        console.log('remove',jQuery(this.params.htmlElement),this.params.htmlElement);
        jQuery(this.params.htmlElement).remove();

    },
    getParamsFromJquery(element) {
        var leftContext = {};
        var s = jQuery(element).attr('cup-filters') || false;
        var ctx = s?s.split(this.token_split_filters): [];
        if (ctx.length > 0) {
            for (var i in ctx) {
                let tmp = ctx[i].split(':');
                leftContext[tmp[0]] = tmp[1];
            }
        }
        var topContext = {};
        var s = jQuery(element).attr('cup-series') || false;
        var ctx = s?s.split(this.token_split_filters): [];
        if (ctx.length > 0) {
            for (var i in ctx) {
                let tmp = ctx[i].split(':');
                topContext[tmp[0]] = tmp[1];
            }
        }
        console.log('CUPPARI SERIEW',jQuery(element).attr('cup-series'),topContext)
        var params = {
            resourceId : jQuery(element).attr('cup-grafico'),
            schemaColor : jQuery(element).attr('cup-colors')?jQuery(element).attr('cup-colors'):'default',
            resourceType : jQuery(element).attr('cup-type')?jQuery(element).attr('cup-type'):'chart',
            chartType : jQuery(element).attr('cup-chart-type')?jQuery(element).attr('cup-chart-type'):'chart',
            htmlElement : element,
            // filters : context,
            // series: serieContext,
            leftContext: leftContext,
            topContext:topContext,
            confName: jQuery(element).attr('cup-conf')?jQuery(element).attr('cup-conf'):'',
            //titolo:jQuery(element).attr('cup-titolo')
        }
        if (jQuery(element)[0].hasAttribute('cup-titolo')) {
            params.titolo = jQuery(element).attr('cup-titolo');
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
            case 'pie':
                grafico = vuePieInit(params.htmlElement,params);
                break;
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
