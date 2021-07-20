pimcore.registerNS("pimcore.plugin.PimcoreConfigurationBundle");

pimcore.plugin.PimcoreConfigurationBundle = Class.create(pimcore.plugin.admin, {
    getClassName: function () {
        return 'pimcore.plugin.PimcoreConfigurationBundle';
    },

    initialize: function () {
        pimcore.plugin.broker.registerPlugin(this);
    },

    pimcoreReady: function (params, broker) {
        // alert("PimcoreConfigurationBundle ready!");
    }
});

var PimcoreConfigurationBundlePlugin = new pimcore.plugin.PimcoreConfigurationBundle();
