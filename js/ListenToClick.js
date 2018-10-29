OpenLayers.Control.ListenToClick = OpenLayers.Class(OpenLayers.Control, {

    defaultHandlerOptions: {
        'single': true,
        'pixelTolerance': 0,
        'stopSingle': false
    },

    initialize: function(options) {

        this.handlerOptions = OpenLayers.Util.extend(
            {}, this.defaultHandlerOptions
        );

        OpenLayers.Control.prototype.initialize.apply(
            this, arguments
        );

        this.handler = new OpenLayers.Handler.Click(
            this, {
                'click': this.onClick
            }, this.handlerOptions
        );
    },

    onClick: function(evt) {
        document.getElementById("#info").innerHTML = '<p>' + data[i].title + '<br />' + data[i].addr + '</p>';
    }
});