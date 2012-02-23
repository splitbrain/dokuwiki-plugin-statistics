/**
 * Statistics script
 */
var plugin_statistics = {
    data: {},

    /**
     * initialize the script
     *
     * @param id string - urlencoded page id
     */
    init: function(){

        // load visitor cookie
        var now = new Date();
        var uid   = DokuCookie.getValue('plgstats');
        if(!uid){
            uid = now.getTime()+'-'+Math.floor(Math.random()*32000);
            DokuCookie.setValue('plgstats',uid);
        }
        plugin_statistics.data = {
            uid: uid,
            p: JSINFO['id'],
            r: document.referrer,
            sx: screen.width,
            sy: screen.height,
            vx: window.innerWidth,
            vy: window.innerHeight,
            js: 1,
            rnd: now.getTime()
        };

        // log access
        if(JSINFO['act'] == 'show'){
            plugin_statistics.log_view('v');
        }else{
            plugin_statistics.log_view('s');
        }

        // attach outgoing event
        jQuery('a.urlextern').click(plugin_statistics.log_external);

        // attach unload event
        jQuery(window).bind('beforeunload',plugin_statistics.log_exit);
    },

    /**
     * Log a view or session
     *
     * @param string act 'v' = view, 's' = session
     */
    log_view: function(act){
        var params = jQuery.param(plugin_statistics.data);
        var img = new Image();
        img.src = DOKU_BASE+'lib/plugins/statistics/log.php?do='+act+'&'+params;
    },

    /**
     * Log clicks to external URLs
     */
    log_external: function(){
        var params = jQuery.param(plugin_statistics.data);
        var img = new Image();
        img.src = DOKU_BASE+'lib/plugins/statistics/log.php?do=o&ol='+encodeURIComponent(this.href)+'&'+params;
        plugin_statistics.pause(500);
        return true;
    },

    /**
     * Log any leaving action as session info
     */
    log_exit: function(){
        var params = jQuery.param(plugin_statistics.data);
        var url = DOKU_BASE+'lib/plugins/statistics/log.php?do=s&'+params;
        jQuery.ajax(url,{async: false});
    },

    /**
     * Pause the script execution for the given time
     */
    pause: function(ms){
        var now = new Date();
        var exitTime = now.getTime()+ms;
        while(true){
            now = new Date();
            if(now.getTime()>exitTime){
                return;
            }
        }
    }
};


jQuery(plugin_statistics.init);
