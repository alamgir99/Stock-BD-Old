(function(){
    Highcharts.extend( Highcharts.Chart.prototype, {
        setTitle: function( additionalOptions ) {
         var options = Highcharts.merge(this.options, additionalOptions)
            var title = options.title, titleAlign = title.align, subtitle = options.subtitle, subtitleAlign = subtitle.align,
                anchorMap = { left: 0, center: this.chartWidth / 2, right: this.chartWidth };
            // title
            if (title && title.text) {
            //jQuery(this.container).find('.highcharts-title').remove();//text('');
            jQuery('.highcharts-title').remove();//text('');
                this.renderer.text( title.text, anchorMap[titleAlign] + title.x, title.y, title.style, 0, titleAlign ).attr({
                    'class': 'highcharts-title'
                }).add();
            }
            // subtitle
            if (subtitle && subtitle.text) {
            //jQuery(this.container).find('.highcharts-subtitle').remove();//text('');
            jQuery('.highcharts-subtitle').remove();//text('');
                this.renderer.text( subtitle.text, anchorMap[subtitleAlign] + subtitle.x, subtitle.y, subtitle.style, 0, subtitleAlign ).attr({
                    'class': 'highcharts-subtitle'
                }).add();
            }
        }
    });
})();
