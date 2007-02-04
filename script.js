/**
 * Statistics script
 */
var plugin_statistics = {
    id: null,

    /**
     * initialize the script
     *
     * @param id string - urlencoded page id
     */
    init: function(id){
        plugin_statistics.id = id;
        var now = new Date();

        // load visitor cookie
        var uid   = DokuCookie.getValue('plgstats');
        if(!uid){
            uid = now.getTime()+'-'+Math.floor(Math.random()*32000);
            DokuCookie.setValue('plgstats',uid);
            if(!DokuCookie.getCookie(DokuCookie.name)){
                uid = '';
            }
        }

        // log the visit
        var img = new Image();
        img.src = DOKU_BASE+'lib/plugins/statistics/log.php'+
                            '?rnd='+now.getTime()+
                            '&p='+id+
                            '&r='+encodeURIComponent(document.referrer)+
                            '&sx='+screen.width+
                            '&sy='+screen.height+
                            '&vx='+window.innerWidth+
                            '&vy='+window.innerHeight+
                            '&uid='+uid+
                            '&js=1';

        // attach event
        addInitEvent(function(){
            var links = getElementsByClass('urlextern',null,'a');
            for(var i=0; i<links.length; i++){
                addEvent(links[i],'click',function(e){plugin_statistics.logExternal(e)});
            }
        });
    },

    /**
     * Log clicks to external URLs
     */
    logExternal: function(e){
        var now = new Date();
        var img = new Image();
        img.src = DOKU_BASE+'lib/plugins/statistics/log.php'+
                            '?rnd='+now.getTime()+
                            '&ol='+encodeURIComponent(e.target.href)+
                            '&p='+plugin_statistics.id;
        plugin_statistics.pause(500);
        return true;
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
}
