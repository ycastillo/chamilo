// Arrow settings

var debug = 1;

var exampleDropOptions = {
    tolerance:'touch',
    hoverClass:'dropHover',
    activeClass:'dragActive'
};

var connectorPaintStyle = {
    lineWidth:5,
    strokeStyle:"#deea18",
    joinstyle:"round"
};

// .. and this is the hover style. 
var connectorHoverStyle = {
    lineWidth:7,
    strokeStyle:"#2e2aF8"
};    

//Admin arrows
var edit_arrow_color = '#ccc';        

var editEndpointOptions = {  
    //connectorStyle:connectorPaintStyle,
    connector:[ "Flowchart", { stub:28 } ],
    hoverPaintStyle:connectorHoverStyle,
    connectorHoverStyle:connectorHoverStyle,
    anchors: ['BottomCenter','TopCenter'],                
    endpoint:"Rectangle",
    paintStyle:{ width:10, height:10, fillStyle:edit_arrow_color },
    isSource:true,
    scope:'blue rectangle',
    maxConnections:10,
    connectorStyle : {
        gradient:{
            stops:[[0, edit_arrow_color], [0.5, edit_arrow_color], [1, edit_arrow_color]]
        }, //gradient stuff
        lineWidth:2,
        strokeStyle: edit_arrow_color
    },
    isTarget:true,
    dropOptions : exampleDropOptions,
    
      // default to blue at one end and green at the other
    EndpointStyles : [{ fillStyle:'#225588' }, { fillStyle:'#558822' }],
    // blue endpoints 7 px; green endpoints 11.
    Endpoints : [ [ "Dot", { radius:7} ], 
                  [ "Dot", { radius:11 } ]
                ],
    // the overlays to decorate each connection with.  note that the label overlay uses a function to generate the label text; in this
    // case it returns the 'labelText' member that we set on each connection in the 'init' method below.
    ConnectionOverlays : [
        [ "Arrow", { location:0.9 } ],
        [ "Label", { 
            location:0.1,
            id:"label",
            cssClass:"aLabel"
        }]
    ]                
    
};


//Student arrows    

//If user does not completed the skill
var default_arrow_color = '#ccc';     //gray  
var defaultEndpoint = {
    connector:[ "Flowchart", { stub:28 } ],
    anchors: ['BottomCenter','TopCenter'],            
    endpoint:"Rectangle",
    paintStyle:{ width:1, height:1, fillStyle:default_arrow_color },
    isSource:false,
    scope:'blue rectangle',
    maxConnections:10,
    connectorStyle : {
        gradient:{ stops:[[0, default_arrow_color], [0.5, default_arrow_color], [1, default_arrow_color]] },
        lineWidth:5,
        strokeStyle:default_arrow_color
    },
    isTarget:false,          
    setDraggableByDefault : false,      
};            

// If user completed the skill 
var done_arrow_color = '#73982C'; //green   
var doneEndpointOptions = {                
    connector:[ "Flowchart", { stub:28 } ],
    anchors: ['BottomCenter','TopCenter'],    
    endpoint:"Rectangle",
    paintStyle:{ width:1, height:1, fillStyle:done_arrow_color},
    isSource:false,
    scope:'blue rectangle',
    maxConnections:10,
    connectorStyle : {
        gradient:{ stops:[[0, done_arrow_color], [0.5, done_arrow_color], [1, done_arrow_color]] },
        lineWidth:5,
        strokeStyle:done_arrow_color
    },
    isTarget:false,
    setDraggableByDefault : false,                         
};

//Functions   

/* Clean window block classes*/
function cleanclass(obj) {
    obj.removeClass('first_window');
    obj.removeClass('second_window');
    obj.removeClass('third_window');
}

/* When clicking the red block */
function open_parent(parent_id, id) {   
    if (debug) console.log("open_parent call : id " + id + " parent_id:" + parent_id);                
    var numeric_parent_id = parent_id.split('_')[1];
    var numeric_id = id.split('_')[1];        
    load_parent(numeric_parent_id, numeric_id);
}

/* 
 *  
 *  When clicking a children block 
    @param  string block id i.e "block_1" 
    @param  int load user data or not
    
*/

function open_block(id, load_user_data) {
    if (debug) console.log("open_block id : " + id+" load_user_data: " +load_user_data);      
    var numeric_id = id.split('_')[1];    
    
    for (var i = 0; i < skills.length; i++) {
        //Remove everything except parents
        if (jQuery.inArray(skills[i].element, parents) == -1) {
            if (debug) console.log('deleting this skill '+ skills[i].element + " id: " + i);
            jsPlumb.deleteEndpoint(skills[i].endp);
            $("#"+skills[i].element).remove();                 
        }
    }        

    //Modifying current block position
    pos = $('#'+id).position();        
    left_value  = center_x; 

    if (parents.length == 2) { 
        top_value  = space_between_blocks_y + offset_y;
    } else {
        top_value = 100;
    }       
    
    jsPlumb.animate(id, { left: left_value, top:top_value }, { duration:duration_value });       

    //Modifying root block position
    pos_parent = $('#'+parents[0]).position();
    jsPlumb.animate(parents[0], { left: center_x, top:offset_y }, { duration:duration_value });       

    top_value = 2*space_between_blocks_y +offset_y ; 
    load_children(numeric_id, top_value, load_user_data);   
}

function open_block_student(id) {
    open_block(id, 1)
}

function load_children(my_id, top_value, load_user_data) {
    if (debug) console.log("load_children : my_id " + my_id + ", top_value:" + top_value +", load_user_data: "+load_user_data);

    //Loading children
    var ix = 0;
    
    $('body').append('<div id="block_'+my_id+ '" class=" window " >Root </div>');
    
       
    jsPlumb.AnchorPositionFinders.MyFinder = function(dp, ep, es, params) {
        console.log('MyFinder');
        console.log('dp: ' +dp);
        return [100, 200];	
    };

    
    var endpointOptions2 = { 
  isTarget:true, 
  maxConnections:5,
  endpoint:"Rectangle", 
  paintStyle:{ fillStyle:"gray" },
  anchor:[ "Assign", { 
        position:"MyFinder",
        myCustomParameter:"foo",
        anInteger:5
  }]  
};
    
    jsPlumb.makeSource("block_" + my_id, {
                anchor:"Continuous",
                endpoint:["Rectangle", { width:200, height:50 }]
            });



    /*$('#block_'+my_id).css({ 
                    position: 'absolute',
                    zIndex: 5000,
                    left: '100px', 
                    top: '100px'
                });*/
    

    
    $.getJSON(url+'&a=load_children&load_user_data='+load_user_data+'&id='+my_id, {},         
        function(json) {              
            console.log('getJSON reponse: ' + json)
            $.each(json,function(i, item) {
                if (debug) console.log('Loading children: #' + item.id + " " +item.name);
                left_value  = ix*space_between_blocks_x +  center_x/2 - block_size / 2;
                //top_value   = 300;
                //Display::url($skill['name'], '#', array('id'=>'edit_block_'.$block_id, 'class'=>'edit_block'))
                item.name = '<a href="#" class="edit_block" id="edit_block_'+item.id+'">'+item.name+'</a>';                    

                var status_class = ' ';
                my_edit_point_options = editEndpointOptions;

                if (item.passed == 1) {
                    my_edit_point_options = doneEndpointOptions;
                    status_class = 'done_window';
                }

                $('body').append('<div id="block_'+item.id+ '" class="third_window open_block window '+status_class+'" >'+item.name+'</div>');
                if (debug) console.log('Append block: '+item.id);

                /*$('#block_'+item.id).css({ 
                    position: 'absolute',
                    zIndex: 5000,
                    left: '10', 
                    top: '10'
                });*/


                //var es = prepare("block_" + item.id,  my_edit_point_options);
                //var e2 = prepare("block_" + my_id,  my_edit_point_options);
                
                jsPlumb.makeTarget("block_" + item.id, endpointOptions2);
                
   
                jsPlumb.connect({source: "block_" + my_id, target:"block_" + item.id, anchor:"Continuous"});
                //console.log('connect sources');

                /*jsPlumb.animate("block_" + item.id, { 
                    left: left_value, top : top_value
                }, { duration : duration_value });
                ix++;   */
            });
        }
    );
}

/* Loads parent blocks */
function load_parent(parent_id, id) {
    if (debug) console.log("load_parent call : id " + id + " parent_id:" + parent_id);
    var ix= 0;         
    $.ajax({
        url: url+'&a=load_direct_parents&id='+id,
        async: false, 
        success: function(json) {                
            var json = jQuery.parseJSON(json);

            $.each(json,function(i,item) {
                left_value  = center_x + space_between_blocks_x * ix;
                top_value   = offset_y;

                $('body').append('<div id="block_'+item.id+ '" class="open_block window " >'+item.name+'</div>');                
                var es  = prepare("block_" + item.id,  editEndpoint);
                var es2 = prepare("block_" + id,  editEndpoint);

                jsPlumb.connect({
                    source: es, target:es2 
                });                    
                jsPlumb.animate("block_" + item.id, { left: left_value, top : top_value }, { duration : duration_value});

                if (item.parent_id) {
                    if (debug) console.log('setting hidden_parent '+item.parent_id);
                    hidden_parent = "block_" + item.parent_id;                        
                } else {
                    if (debug) console.log('setting NO--- hidden_parent ');
                }
                ix++;   
            });                
        }
        });
}



function checkLength( o, n, min, max ) {
    if ( o.val().length > max || o.val().length < min ) {
        o.addClass( "ui-state-error" );
        updateTips( "Length of " + n + " must be between " +min + " and " + max + "." );
        return false;
    } else {
        return true;
    }
}

function updateTips( t ) {
    tips = $( ".validateTips" )
    tips
        .text( t )
        .addClass( "ui-state-highlight" );
    setTimeout(function() {
        tips.removeClass( "ui-state-highlight", 1500 );
    }, 500 );
}