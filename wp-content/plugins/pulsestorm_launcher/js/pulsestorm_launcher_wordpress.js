jQuery(function(){
    var $ = jQuery;

    window.pulsestorm_launcher_temp_ajax = function(){
        var data = {
            'action':'pulsestorm_launcher_search',
            'terms':'test terms'
        };    
        $.post(ajaxurl, data, function(r){
            if(!r['links']){return;}
            $.each(r.links, function(k,link){
                addLinkToLauncherUi(link.href, link.label);
            });
            console.log(r);
        });
    };
    
    var searchForLinks = function(terms, objectToSearch){
        var found = {};
        $.each(objectToSearch, function(key, value){
            if(key.indexOf(terms) !== -1){
                found[key] = value.label;
                return;
            }
            if(value.terms.indexOf(terms) !== -1){
                found[key] = value.label;
                return;
            }                
        });
        return found;
    };

    var renderResults = function(results){
        $('#pulsestorm_launcher_links').html('');
        $.each(results, function(key, value){
            addLinkToLauncherUi(key, value);            
        });
        
    };

    var addLinkToLauncherUi = function(href, label, div){
        div = div ? div : 'pulsestorm_launcher_links';
        $('#pulsestorm_launcher_links').append('<li><a href="'+href+'">'+label+'</a></li>');        
    };
            
    var setFirstItemActive = function(){
        $('#pulsestorm_launcher_results ul li').first().addClass('active');          
    };
        
    var setLastItemActive = function(){
        $('#pulsestorm_launcher_results ul li').last().addClass('active');
    };
                
    var loopActive = function(direction){
        if(direction === 'prev'){                
            setLastItemActive();
            return;
        }
        setFirstItemActive();
    };
         
    var resultListHasItems = function(){
        return $('#pulsestorm_launcher_results ul li.active').length === 0;
    };
           
    var getFirstActiveResultNode = function(){
        return $('#pulsestorm_launcher_results ul li.active').first();
    };
                 
    var removeActiveFromCurrentItemAndSetOnNextOrPrev = function(direction){
        var currentElement = getFirstActiveResultNode();
        currentElement.removeClass('active');          
        if(currentElement[direction]().length !== 0){
            currentElement[direction]().addClass('active');
            return true;
        }        
        return false;
    };
                                       
    var setActiveItem = function(direction){
        if(resultListHasItems()){
            setFirstItemActive();                
            return;
        }
        
        if(!removeActiveFromCurrentItemAndSetOnNextOrPrev(direction)){
            loopActive(direction);            
        }            
    };                
    
    var resetWordpressHacks = function(){        
        //remove these styles from body if they exist
        var classes = ['about-php','plugin-install-php','import-php',
            'plugins-php','update-core-php','index-php'];
        var removed = [];
        $.each(classes, function(k,v){
            if(!$('body').hasClass(v)) { return; }
            removed.push(v);
            $('body').removeClass(v);
        });                
        
        var tb_position_original = window.tb_position;
        
        //some wordpress pages redefine this function which breaks
        //the thickbox, so we need to reset it here.  
        window.tb_position = function() {
            var isIE6 = typeof document.body.style.maxHeight === "undefined";
            jQuery("#TB_window").css({marginLeft: '-' + parseInt((TB_WIDTH / 2),10) + 'px', width: TB_WIDTH + 'px'});
            if ( ! isIE6 ) { // take away IE6
                jQuery("#TB_window").css({marginTop: '-' + parseInt((TB_HEIGHT / 2),10) + 'px'});
            }
        }

        var tb_remove_original = window.tb_remove;
        window.tb_remove = function()
        {
            $.each(removed, function(k,v){
                $('body').addClass(v);
                window.tb_position = tb_position_original;
            });
            tb_remove_original();
        } 
    }
    
    var handlerForOpeningLauncher = function(){
        var openLauncher = function(){
            resetWordpressHacks();
            tb_show(null,'#TB_inline?height=480&width=480&inlineId=pulsestorm_launcher_thickbox',false);
            $('#pulsestorm_launcher_input').focus();                    
        }    
        if(!pulsestorm_launcher_settings.pulsestorm_launcher_trigger_code){
            return;
        }
        $(document).keydown(function(e){        
            if(!e.ctrlKey) { return; }
            if(e.keyCode !== pulsestorm_launcher_settings.pulsestorm_launcher_trigger_code){
                return;
            }
            openLauncher();
            //tb_remove();
        });        
        
        $('#wp-admin-bar-pulsestorm_launcher_link').click(function(){
            openLauncher();
        });
    };
    
    var handlerForUpAndDownArrows = function(){
        $(document).keydown(function(e){
            if([40,38].indexOf(e.which) === -1){
                return;
            }          
            if(e.which === 38){
                setActiveItem('prev');
                return;
            }
            
            setActiveItem('next');
        });        
    };
    
    var handlerForTextEntry = function(){
        $('#pulsestorm_launcher_input').keyup(function(e){
            if([38,40,13].indexOf(e.which) !== -1){return;}
            var results = searchForLinks(
                $('#pulsestorm_launcher_input').val(),
                pulsestorm_launcher_quicksearch
            );
            renderResults(results);            
        });        
    };
    
    var isLauncherWindowUp = function(){
        return jQuery('#TB_ajaxContent #pulsestorm_launcher_results').length !== 0;
    };
    
    var goToActiveResultHref = function(){
        var url = getFirstActiveResultNode().find('a').attr('href');
        if(!url){return;}
        window.location = url;
    };
    
    var handlerForEnter = function(){
        $(document).keypress(function(e) {
            if(e.which !== 13) { return; }            
            if(!isLauncherWindowUp()) { return; }
            goToActiveResultHref();
        });                
    };
    
    var handlerForAjaxSearch = function(){
        var isSearching = false;    
        $(document).keyup(function(e) {     
            if(isSearching) { return; }
            if(!isLauncherWindowUp()) { return; }
            if([40,38,13].indexOf(e.which) !== -1){return;} //arrows or enter/return             
            var terms = jQuery('#pulsestorm_launcher_input').val();
            if(terms.length < 3) { return; }
            isSearching = true;
            
            var data = {
                'action':'pulsestorm_launcher_search',
                'terms':terms
            };    
            $.post(ajaxurl, data, function(r){
                if(!r['links']){return;}
                $.each(r.links, function(k,link){
                    addLinkToLauncherUi(link.href, link.label, 'pulsestorm_launcher_ajax');
                });
                isSearching = false;
            });
                    
            console.log(terms);
        });     
    };
    
    handlerForOpeningLauncher();
    handlerForUpAndDownArrows();
    handlerForTextEntry();
    handlerForEnter();
    handlerForAjaxSearch();
});
