$j = jQuery.noConflict();

jQuery(window).on('load', function () {
    var $updateLink = $j('table.plugins #updatePlugin.ok.need_update');
    $updateLink.on('click', function(e) {
        e.preventDefault();
        $j(this).removeClass('need_update').parent().parent().addClass('disabled');
        updatePlugin($j(this));
        });


    var updatePlugin = function (plugin) {
        var $data = {
            'action': "plugin_update",
            'namePluginForUpdate': plugin.attr('name')
        };
        $j.post(ajaxurl, $data, function (data) {
            $j('#bulk-action-form').load(location.href + " #bulk-action-form");
        }, 'html');
    };

});