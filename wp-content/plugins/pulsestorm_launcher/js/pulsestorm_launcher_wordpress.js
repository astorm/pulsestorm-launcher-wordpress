jQuery(function(){
    var $ = jQuery;
    
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
            $('#pulsestorm_launcher_links').append('<li><a href="'+key+'">'+value+'</a></li>');        
        });
        
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
    
    var handlerForOpeningLauncher = function(){
        var openLauncher = function(){
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
    
    handlerForOpeningLauncher();
    handlerForUpAndDownArrows();
    handlerForTextEntry();
    handlerForEnter();
    
});
